<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UniAd\StoreRequest;
use App\Http\Requests\UniAd\UpdateRequest;
use App\Http\Resources\UniAd\UniAdMapResource;
use App\Http\Resources\UniAd\UniAdPaginateResource;
use App\Http\Resources\UniAd\UniAdResource;
use App\Http\Resources\UniAd\UniAdEditResource;
use App\Models\AdsFilter;
use App\Models\AdsFiltersAlias;
use App\Models\AdsFiltersItem;
use App\Models\AdsFiltersVariant;
use App\Models\UniAd;
use App\Models\UniCategoryBoard;
use App\Services\AdService;
use App\Services\FilterService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Tymon\JWTAuth\Facades\JWTAuth;


class UniAdController extends Controller
{

    protected AdService $adService;

    protected FilterService  $filterService;

    public function __construct(AdService $adService, FilterService $filterService)
    {
        $this->adService = $adService;
        $this->filterService = $filterService;

    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $ads = UniAd::paginate(10);
        return UniAdResource::collection($ads);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {

        $data = $request->validated();

        $data['ads_alias'] = Str::slug($data['ads_title']);
        $data['ads_period_publication'] = now()->addMonth()->format('Y-m-d H:i:s');
        $data['ads_status'] = 0;
        $data['ads_period_day'] = 30;
        $data['ads_country_id'] = 12;

        $imageUrls = [];
        $absolutePath = 'D:/OSPanel/domains/joymee/media/images_boards';
        // $absolutePath = '/Home/web/joymee.uz/public_html/media/images_boards';



        if ($request->hasFile('ads_images')) {
            foreach ($request->file('ads_images') as $file) {
                $imageName = $file->getClientOriginalName();
                $imagePath = $absolutePath;

                $image = Image::make($file);
                $image->save($imagePath . '/big/' . $imageName, 90); // 70 - уровень качества (меньше значение - больше сжатие)

                // Уменьшенный размер с сжатием
                $smallImage = Image::make($file); // Измените размер на свой выбор
                $smallImage->save($imagePath . '/small/' . $imageName, 50); // 50 - уровень качества

                // Сохранение файла по абсолютному пути
                $imageUrls[] = $imageName; // Generate URL for the saved image
            }
        }

        $data['ads_images'] = $imageUrls;

        if(isset($data['filters'])){
            $filterData = json_decode($data['filters']);
            unset($data['filters']);
        }


        $ads = UniAd::firstOrCreate($data);


        if(isset($filterData)){
            $adsFilterTags = [];
            // Обработка данных фильтров
            foreach ($filterData as $filterItem) {
                $filterId = $filterItem->filter_id;
                $filterTitle = $filterItem->title; // Добавляем заголовок фильтра
                $filterType = $filterItem->type;   // Добавляем тип фильтра

                $items = $filterItem->items;

                if($filterType === 'input') {
                    $itemId = $items;
                    $itemValue = $items;
                } elseif ($filterType === 'select') {
                    $itemId = $items->id;
                    $itemValue = $items->value;
                } elseif ($filterType === 'select_multi') {
                    foreach ($items as $item) {
                        $itemId = $item->id;
                        $itemValue = $item->value;

                        // Создаем запись в таблице uni_ads_filters_variants
                        $adsFiltersVariants = new AdsFiltersVariant();
                        $adsFiltersVariants->ads_filters_variants_id_filter = $filterId;
                        $adsFiltersVariants->ads_filters_variants_val = $itemId;
                        $adsFiltersVariants->ads_filters_variants_product_id = $ads->ads_id;
                        $adsFiltersVariants->save();

                        $adsFilterTags[] = $itemValue;
                    }
                    continue;
                }
                // Создаем запись в таблице uni_ads_filters_variants
                $adsFiltersVariants = new AdsFiltersVariant();
                $adsFiltersVariants->ads_filters_variants_id_filter = $filterId;
                $adsFiltersVariants->ads_filters_variants_val = $itemId;
                $adsFiltersVariants->ads_filters_variants_product_id = $ads->ads_id;
                $adsFiltersVariants->save();

                $adsFilterTags[] = $itemValue;
            }
        }
        if(isset($adsFilterTags)){
            $ads->ads_filter_tags = implode(';', $adsFilterTags);
            $ads->save();
        }



        return UniAdResource::make($ads);
    }

    /**
     * Display the specified resource.
     */
    public function show($id) {
        $uniAd = UniAd::find($id);

        if (!$uniAd) {
            return response()->json(['error' => 'Ad not found'], 404);
        }
        $token = JWTAuth::parseToken();
        $user = $token->authenticate();



        if ($uniAd->ads_id_user) {
            DB::table('uni_ads_views')->insert([
                'ads_views_id_ad' => $id,
                'ads_views_date' => now(),
                'ads_views_id_user' => $uniAd->ads_id_user,
            ]);

        }

        // Получаем родительскую категорию
        $parentCategory = UniCategoryBoard::where('category_board_id', $uniAd->ads_id_cat)->first();
        if (!$parentCategory) {
            return response()->json(['error' => 'Category not found'], 404);
        }
        $adData = $this->adService->prepareAdData($uniAd);

        $adData->category_parent_id = $parentCategory->category_board_id_parent;
        $filters = $this->filterService->load_filters_ad($uniAd->ads_id_cat, $this->filterService->getVariants($id), $id);
        $adData->filters_selected = $filters;
        return new UniAdEditResource($adData);
    }



    public function update(UpdateRequest $request, $uni_id)
    {
        $data = $request->validated();



        $uniAd = UniAd::findOrFail($uni_id);


        $data['ads_alias'] = Str::slug($data['ads_title']);
        $data['ads_period_publication'] = now()->addMonth()->format('Y-m-d H:i:s');
        $data['ads_status'] = 0;
        $data['ads_period_day'] = 30;
        $data['ads_country_id'] = 12;


        $existingImages = $uniAd->ads_images;

        $absolutePath = 'D:/OSPanel/domains/joymee/media/images_boards';

        if(isset($data["image_names"])) {

            $imageNames = explode(',', $data['image_names']);
            // Determine new images to add
            $newImages = array_diff($imageNames, $existingImages);
            // Determine images to remove
            $imagesToRemove = array_diff($existingImages, $imageNames);
            // Determine existing images that are unchanged (if needed)
            $unchangedImages = array_intersect($imageNames, $existingImages);
            // dd('new images ', $newImages, 'to remove ', $imagesToRemove, 'unchanged ' ,$unchangedImages);

            foreach ($imagesToRemove as $imageName) {
                $imagePathBig = $absolutePath .'/big/'. $imageName;
                $imagePathSmall = $absolutePath .'/small/'. $imageName;
                if (file_exists($imagePathBig )) {
                    unlink($imagePathBig ); // This will delete the image file
                }
                if (file_exists($imagePathSmall )) {
                    unlink($imagePathSmall); // This will delete the image file
                }
                $uniAd::where('ads_images', $imageName)->delete();
            }

        }



        $imageUrls = [];

        if ($request->hasFile('ads_images')) {
            foreach ($request->file('ads_images') as $file) {
                $imageName = $file->getClientOriginalName();
                $imagePath = $absolutePath;

                $image = Image::make($file);
                $image->save($imagePath . '/big/' . $imageName, 90);

                $smallImage = Image::make($file);
                $smallImage->save($imagePath . '/small/' . $imageName, 50);

                $imageUrls[] = $imageName;
            }
        }
        if(isset($data["image_names"])) {
            $imageNames = explode(',', $data['image_names']);
            // Determine new images to add
            $newImages = array_diff($imageNames, $existingImages);
            // Determine images to remove
            $imagesToRemove = array_diff($existingImages, $imageNames);
            // Determine existing images that are unchanged (if needed)
            $unchangedImages = array_intersect($imageNames, $existingImages);
            // dd('new images ', $newImages, 'to remove ', $imagesToRemove, 'unchanged ' ,$unchangedImages);
            foreach ($unchangedImages as $imageName) {
                $imageUrls[] = $imageName;
            }
        }


        $data['ads_images'] = $imageUrls;

        if(isset($data['filters'])){
            $filterData = json_decode($data['filters']);
            unset($data['filters']);
        }

        unset($data["image_names"]);


        $uniAd->update($data);

        if (isset($filterData)) {
            $adsFilterTags = [];
            foreach ($filterData as $filterItem) {
                $filterId = $filterItem->filter_id;
                $filterTitle = $filterItem->title;
                $filterType = $filterItem->type;

                $items = $filterItem->items;

                if($filterType === 'input') {
                    $itemId = $items;
                    $itemValue = $items;
                } elseif ($filterType === 'select') {
                    $itemId = $items->id;
                    $itemValue = $items->value;
                } elseif ($filterType === 'select_multi') {
                    foreach ($items as $item) {
                        $itemId = $item->id;
                        $itemValue = $item->value;

                        $adsFiltersVariants = AdsFiltersVariant::where('ads_filters_variants_product_id', $uniAd->ads_id)
                            ->where('ads_filters_variants_id_filter', $filterId)
                            ->first();

                        if ($adsFiltersVariants) {
                            $adsFiltersVariants->ads_filters_variants_val = $itemId;
                            $adsFiltersVariants->save();
                        } else {
                            $adsFiltersVariants = new AdsFiltersVariant();
                            $adsFiltersVariants->ads_filters_variants_id_filter = $filterId;
                            $adsFiltersVariants->ads_filters_variants_val = $itemId;
                            $adsFiltersVariants->ads_filters_variants_product_id = $uniAd->ads_id;
                            $adsFiltersVariants->save();
                        }

                        $adsFilterTags[] = $itemValue;
                    }
                    continue;
                }

                $adsFiltersVariants = AdsFiltersVariant::where('ads_filters_variants_product_id', $uniAd->ads_id)
                    ->where('ads_filters_variants_id_filter', $filterId)
                    ->first();

                if ($adsFiltersVariants) {
                    $adsFiltersVariants->ads_filters_variants_val = $itemId;
                    $adsFiltersVariants->save();
                } else {
                    $adsFiltersVariants = new AdsFiltersVariant();
                    $adsFiltersVariants->ads_filters_variants_id_filter = $filterId;
                    $adsFiltersVariants->ads_filters_variants_val = $itemId;
                    $adsFiltersVariants->ads_filters_variants_product_id = $uniAd->ads_id;
                    $adsFiltersVariants->save();
                }

                $adsFilterTags[] = $itemValue;
            }
        }
        if(isset($adsFilterTags)) {
            $uniAd->ads_filter_tags = implode(';', $adsFilterTags);
            $uniAd->save();
        }

        $this->adService->prepareAdData($uniAd);

        return UniAdResource::make($uniAd);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UniAd $uniAd)
    {
        $uniAd->delete();
        return response()->json([
            'message' => 'done'
        ]);
    }

    public function getAdsByCategory(Request $request, \App\Models\UniCategoryBoard $category)
    {

        $ads = UniAd::where('ads_id_cat', $category->category_board_id)->get();


        if ($ads->isEmpty()) {
            return response()->json(['message' => 'Объявления не найдены'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($ads);
    }


    public function getAdsByCityAndCategory(Request $request,  $cityId, $ad_category)
    {

        $ads = UniAd::where('ads_id_cat', $ad_category)
            ->whereHas('city', function ($query) use ($cityId) {
                $query->where('ads_city_id', $cityId);
            })
            ->get();
        if ($ads->isEmpty()) {
            return response()->json(['message' => 'Объявления не найдены'], ResponseAlias::HTTP_NOT_FOUND);
        }

        $baseUrl = 'https://joymee.uz/media/images_boards/big/'; // Базовый URL для изображений

        $result = [];
        foreach ($ads as $ad) {
            if(isset($ad['ads_images'])) {
                $images = $ad['ads_images'];
                $fullImagePath = array_map(function ($image) use ($baseUrl) {
                    return $baseUrl . $image;
                }, $images);

                $ad['ads_images'] = $fullImagePath;
            }


            // Обработка ads_price
            if ($ad['ads_price'] >= 1000000) {
                $ad['short_price'] = number_format($ad['ads_price'] / 1000000, 2) . ' млн';
            } elseif ($ad['ads_price'] >= 10000) {
                $ad['short_price'] = number_format($ad['ads_price'] / 1000, 1) . ' тыс';
            }  elseif ($ad['ads_price'] < 9999) {
                $ad['short_price'] = $ad['ads_price'];
            }

            if ($ad['ads_currency'] === 'USD') {
                $ad['short_price'] = '$' . $ad['short_price'];
            } else {
                $ad['short_price'] = $ad['short_price'] . 'сум';
            }


            $filtersString = $ad['ads_filter_tags'];
            $filtersArray = explode(';', $filtersString);

            $filters = [];


            // Добавляем свойство filters в ассоциативный массив объявления
            $ad['filters'] = $filters;

            $adProperties = $this->outProductProperties($ad['ads_id'], $ad['ads_id_cat'], [], $ad['ads_city_id']);
            $updatedFilters = [];

            foreach ($adProperties as $propertyKey => $propertyValue) {
                $updatedFilters[] = [
                    'title' => $propertyKey,
                    'value' => $propertyValue
                ];
            }

            $ad['filters'] = array_merge($ad['filters'], $updatedFilters);
            $result[] = $ad;
        }

        return UniAdResource::collection(collect($result));
    }



    public function getAdsByCityAndCategoryTwo(Request $request, $cityId, $ad_category)
    {
        $ads = UniAd::where('ads_id_cat', $ad_category)
            ->whereHas('city', function ($query) use ($cityId) {
                $query->where('ads_city_id', $cityId);
            })
            ->get();

        if ($ads->isEmpty()) {
            return response()->json(['message' => 'Объявления не найдены'], ResponseAlias::HTTP_NOT_FOUND);
        }

        $result = $this->prepareAdsData($ads);

        return UniAdMapResource::collection(collect($result));
    }

    public function getAdsByCityAndCategoryWithPaginate(Request $request, $cityId, $ad_category)
    {
        // Define the number of ads per page
        $perPage = $request->input('per_page', 10); // You can adjust the default value as needed

        $parentCategory = UniCategoryBoard::where('category_board_id', $ad_category)->first();

        if ($parentCategory) {
            $categoryIds = [$ad_category];
            $categoryIds = array_merge($categoryIds, $this->getAllChildCategories($parentCategory));
        } else {
            $categoryIds = [$ad_category];
        }

        // Query to filter ads by category and city
        $query = UniAd::whereIn('ads_id_cat', $categoryIds)
            ->whereHas('city', function ($query) use ($cityId) {
                $query->where('ads_city_id', $cityId);
            });

        // Paginate the results
        $ads = $query->paginate($perPage);

        if ($ads->isEmpty()) {
            return response()->json(['message' => 'Объявления не найдены'], ResponseAlias::HTTP_NOT_FOUND);
        }

        // Prepare the data for the current page
        $result = $this->prepareAdsData($ads);

        // Create a new Paginator instance to include pagination information in the response
        $paginator = new Paginator($result, $perPage, $ads->currentPage(), [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);

        // Return the paginated data using UniAdMapResource
        return UniAdPaginateResource::collection($paginator);
    }



    public function showWithFilter()
    {
        $adsData = UniAd::all();

        $result = [];

        foreach ($adsData as $ad) {
            $filtersString = $ad['ads_filter_tags'];
            $filtersArray = explode(';', $filtersString);

            $filters = [];

            foreach ($filtersArray as $filterTag) {

                // Ищем фильтр в таблице uni_ads_filters_items по значению
                $filterItem = AdsFiltersItem::where('ads_filters_items_value', $filterTag)->first();

                if ($filterItem) {
                    $filterId = $filterItem->ads_filters_items_id_filter;
                    // Ищем соответствующий фильтр в таблице uni_ads_filters по ads_filters_id
                    $filter = AdsFilter::where('ads_filters_id', $filterId)->first();

                    if ($filter) {
                        $filters[$filter->ads_filters_name] = $filterTag;
                    }
                }
            }


            $ad['filters'] = $filters;
            $result[] = $ad;
        }

        return UniAdResource::collection(collect($result));
    }


    public function getVipAds()
    {
        $vipAds = UniAd::where('ads_vip', 1)
            ->inRandomOrder()
            ->take(16)
            ->get();

        $result = $this->prepareAdsData($vipAds);

        return UniAdResource::collection(collect($result));
    }

    public function searchAds(Request $request)
    {
        $regionId = $request->input('region');
        $cityId = $request->input('city');
        $districtId = $request->input('area');
        $category = $request->input('category');

        $query = UniAd::query()
            ->active()
            ->regionCityDistrict($regionId, $cityId, $districtId)
           ->category($category);

        $parentCategory = UniCategoryBoard::where('category_board_id', $category)->first();

        if ($parentCategory) {
            $categoryIds = [$category];
            $categoryIds = array_merge($categoryIds, $this->getAllChildCategories($parentCategory));
        } else {
            $categoryIds = [$category];
        }

        $query->whereIn('ads_id_cat', $categoryIds);

        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');

        if ($minPrice !== null) {
            $query->where('ads_price', '>=', $minPrice);
        }

        if ($maxPrice !== null) {
            $query->where('ads_price', '<=', $maxPrice);
        }


        $filters = explode(',', $request->input('filters'));

        $filterItemIds = [];
        foreach ($filters as $filter) {
            if (strpos($filter, ':') !== false) {
                list($filterId, $itemValue) = explode(':', $filter);

                // Получаем ID элемента фильтра
                $filterItemId = AdsFiltersVariant::where('ads_filters_variants_id_filter', $filterId)
                    ->where('ads_filters_variants_val', $itemValue)
                    ->pluck('ads_filters_variants_product_id')
                    ->first();

                if (isset($filterItemId)) {
                    $filterItemIds[] = $filterItemId;
                }
            }
        }

        // Если элементы фильтра найдены, добавляем условие в запрос
        if (!empty($filterItemIds)) {
            $query->whereIn('ads_id', $filterItemIds);
        }

        $ads = $query->get();

        if ($ads->isEmpty()) {
            return response()->json(['message' => 'Объявления не найдены'], 404);
        }

        $adsResult = $this->adService->prepareAdsData($ads);
        return UniAdMapResource::collection(collect($adsResult));
    }




    public function getVariants($ad_id = 0)
    {

        if ($ad_id) {
            $getVariants = AdsFiltersVariant::where('ads_filters_variants_product_id', $ad_id)
                ->orderBy('ads_filters_variants_id', 'asc')
                ->get();

            if ($getVariants->count() > 0) {
                $data = [
                    'items' => [],
                    'value' => [],
                ];

                foreach ($getVariants as $result) {
                    $data['items'][$result['ads_filters_variants_id_filter']][$result['ads_filters_variants_val']] = $result;
                    $data['value'][$result['ads_filters_variants_id_filter']][] = $result;
                }

                return $data;
            }
        }

        return [];
    }


    private function getAllChildCategories($category)
    {
        $childCategories = [];

        foreach ($category->childrenCategories as $childCategory) {
            $childCategories[] = $childCategory->category_board_id;
            $childCategories = array_merge($childCategories, $this->getAllChildCategories($childCategory));
        }

        return $childCategories;
    }

    public function outProductProperties($product_id = 0, $id_cat = 0, $category = [], $city_alias = "")
    {


        $out = array();

        $getVariants = $this->getVariants($product_id);

        if (isset($getVariants['items']) && is_array($getVariants['items']) && !empty($getVariants['items'])) {
            foreach ($getVariants["items"] as $id_filter => $array) {

                $value = array();

                // Ищем фильтр в таблице uni_ads_filters по ads_filters_id
                $getFilter = AdsFilter::where('ads_filters_id', intval($id_filter))->first();


                if ($getFilter) {
                    foreach ($array as $val => $result) {

                        if ($getFilter->ads_filters_type == "input" || $getFilter->ads_filters_type == "input_text") {
                            $value[] = $val;

                        } else {
                            // Ищем элемент фильтра в таблице uni_ads_filters_items по ads_filters_items_id
                            $getItem = AdsFiltersItem::where('ads_filters_items_id', $val)->first();

                            $value[] = $getItem->ads_filters_items_value;
                        }
                    }
                    $out[$getFilter->ads_filters_name] = implode(", ", $value);
                }
            }
        }

        return $out;
    }

    private function prepareAdsData($ads)
    {
        $baseUrl = 'https://joymee.uz/media/images_boards/big/';

        $result = [];

        foreach ($ads as $ad) {
            $images = $ad['ads_images'];
            $fullImagePath = array_map(function ($image) use ($baseUrl) {
                return $baseUrl . $image;
            }, $images);

            $ad['ads_images'] = $fullImagePath;

            $ad['short_price'] = $this->formatPrice($ad['ads_price'], $ad['ads_currency']);


            $filtersString = $ad['ads_filter_tags'];
            $filtersArray = explode(';', $filtersString);

            $filters = [];


            // Добавляем свойство filters в ассоциативный массив объявления
            $ad['filters'] = $filters;

            $adProperties = $this->outProductProperties($ad['ads_id'], $ad['ads_id_cat'], [], $ad['ads_city_id']);
            $updatedFilters = [];

            foreach ($adProperties as $propertyKey => $propertyValue) {
                $updatedFilters[] = [
                    'title' => $propertyKey,
                    'value' => $propertyValue
                ];
            }

            $ad['filters'] = array_merge($ad['filters'], $updatedFilters);
            $result[] = $ad;
        }

        return $result;
    }


    private function formatPrice($price, $currency)
    {
        if ($price >= 1000000) {
            return number_format($price / 1000000, 2) . ' млн';
        } elseif ($price >= 10000) {
            return number_format($price / 1000, 1) . ' тыс';
        } else {
            return $price . ($currency === 'USD' ? '$' : 'сум');
        }
    }

}



