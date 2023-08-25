<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UniAd\StoreRequest;
use App\Http\Requests\UniAd\UpdateRequest;
use App\Http\Resources\UniAd\UniAdResource;
use App\Models\AdsFilter;
use App\Models\AdsFiltersAlias;
use App\Models\AdsFiltersItem;
use App\Models\AdsFiltersVariant;
use App\Models\UniAd;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class UniAdController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $ads = UniAd::paginate(10);

        return UniAdResource::collection($ads);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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

        $imageUrls = [];
        $absolutePath = 'D:/OSPanel/domains/joymee/media/images_boards';
//        $absolutePath = '/Home/web/joymee.uz/public_html/media/images_boards';



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
//                File::put($imagePath, file_get_contents($file));
                $imageUrls[] = $imageName; // Generate URL for the saved image
            }
        }

        $data['ads_images'] = $imageUrls;

        $ads = UniAd::firstOrCreate($data);

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

        return new UniAdResource($uniAd);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UniAd $uniAd)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, UniAd $uniAd)
    {

        $data = $request->validated();

        $uniAd->update($data);

        $uniAd->fresh();

        return response()->json(['message' => 'ads updated successfully']);
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
            $images = $ad['ads_images'];
            $fullImagePath = array_map(function ($image) use ($baseUrl) {
                return $baseUrl . $image;
            }, $images);

            $ad['ads_images'] = $fullImagePath;

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

            // Используем функцию outProductProperties для преобразования значений

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

    public function showFilter()
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
                        // Получаем значение из таблицы uni_ads_filters_items_value
                        $filterValue = $filterItem->ads_filters_items_value;

                        // Добавляем фильтр и его значение в ассоциативный массив
                        $filters[$filter->ads_filters_name] = $filterValue;
                    }
                }
            }

            // Добавляем свойство filters в ассоциативный массив объявления
            $ad['filters'] = $filters;

            // Используем функцию outProductProperties для преобразования значений

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

    function outProductProperties($product_id = 0, $id_cat = 0, $category = [], $city_alias = "")
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


}



