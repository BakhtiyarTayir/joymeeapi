<?php

namespace App\Services;


use DateTime;
use App\Models\UniAd;
use App\Models\AdsView;
use App\Models\AdsFilter;
use App\Models\UniFavorite;
use App\Models\AdsFiltersItem;
use App\Models\ActionStatistic;
use App\Models\AdsFilterCategory;
use App\Models\AdsFiltersVariant;


class AdService
{
    public $service;

    public function getVariants($ad_id = 0)
    {

        if($ad_id) {
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


    public function getVariantsData($ad_id)
    {
        $variantsData = [];

        if ($ad_id) {
            $getVariants = AdsFiltersVariant::where('ads_filters_variants_product_id', $ad_id)
                ->whereHas('filter.categories', function ($query) use ($ad_id) {
                    $query->where('ads_filters_category_id_cat', $ad_id);
                })
                ->orderBy('ads_filters_variants_id', 'asc')
                ->get();

            if ($getVariants->count() > 0) {
                foreach ($getVariants as $result) {
                    $filterId = $result['ads_filters_variants_id_filter'];

                    $getFilter = AdsFilter::where('ads_filters_id', $filterId)->first();

                    if ($getFilter) {
                        // Получаем элементы фильтра
                        $items = AdsFiltersItem::where('ads_filters_items_id_filter', $filterId)->get()->toArray();

                        $variantsData[] = [
                            'filter_id' => $getFilter['ads_filters_id'],
                            'title' => $getFilter['ads_filters_name'],
                            'type' => $getFilter['ads_filters_type'],
                            'position' => $getFilter['ads_filters_position'],
                            'required' => $getFilter['ads_filters_required'],
                            'items' => $items  // Добавляем items в массив
                        ];
                    }
                }
            }
        }
        return $variantsData;
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
                           $ids[] = $result->ads_filters_variants_id_filter;
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




    public function prepareAdsData($ads)
    {
        $baseUrl = 'https://joymee.uz/media/images_boards/big/';

        $result = [];
        foreach ($ads as $ad) {
            $images = $ad['ads_images'];
            if(is_array($images)) {
                $fullImagePath = array_map(function ($image) use ($baseUrl) {
                    return $baseUrl . $image;
                }, $images);

            $ad['ads_images'] = $fullImagePath;
            }
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
            $ad['count_views'] = (string)$this->getCountView($ad['ads_id']);
            $ad['count_views_phone'] = (string)$this->getCountPhone($ad['ads_id']);
            $ad['in_favorite'] = (string)$this->getCountFavorites($ad['ads_id']);
            $ad['days'] = $this->difference_days($ad['ads_period_publication'], date('Y-m-d H:i:s'));
            $result[] = $ad;
        }

        return $result;
    }

    public function prepareAdsFilterData($ads)
    {
        $baseUrl = 'https://joymee.uz/media/images_boards/big/';

        $result = [];
        foreach ($ads as $ad) {
            $images = $ad['ads_images'];
            if(is_array($images)) {
                $fullImagePath = array_map(function ($image) use ($baseUrl) {
                    return $baseUrl . $image;
                }, $images);

            $ad['ads_images'] = $fullImagePath;
            }
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
                    'value' => $propertyValue,
                ];
            }

             $ad['filters'] = array_merge($ad['filters'], $updatedFilters);
            // $ad['filters'] = $this->getFiltersForCategory($ad['ads_id_cat']);
            $ad['count_views'] = (string)$this->getCountView($ad['ads_id']);
            $ad['count_views_phone'] = (string)$this->getCountPhone($ad['ads_id']);
            $ad['in_favorite'] = (string)$this->getCountFavorites($ad['ads_id']);
            $ad['days'] = $this->difference_days($ad['ads_period_publication'], date('Y-m-d H:i:s'));
            $result[] = $ad;
        }

        return $result;
    }

    public function getFiltersForCategory($id_cat)
    {
        // Получаем записи из uni_ads_filters_category, связанные с данным id_cat
        $categoryFilters = AdsFilterCategory::where('ads_filters_category_id_cat', $id_cat)->get();

        $filtersData = [];

        foreach ($categoryFilters as $categoryFilter) {
            $filterId = $categoryFilter->ads_filters_category_id_filter;

            // Получаем фильтр из таблицы uni_ads_filters по его ID
            $filter = AdsFilter::find($filterId);


            if ($filter) {
                $filterData = [
                    'filter_id' => $filter->ads_filters_id,
                    'name' => $filter->ads_filters_name,
                    'type' => $filter->ads_filters_type,
                    'position' => $filter->ads_filters_position,
                    'required' => $filter->ads_filters_required,
                ];

                $items = [];

                foreach ($filter->filterItems as $item) {

                    $items[] = [
                        'id' => $item->ads_filters_items_id, // Используйте нужное поле из вашей модели
                        'value' => $item->ads_filters_items_value,
                    ];
                }

                $filterData['items'] = $items;

                $filtersData[] = $filterData;
            }
        }

        usort($filtersData, function($a, $b) {
            return $a['position'] - $b['position'];
        });


        return $filtersData;
    }

    public function prepareFilters($ads)
    {

        $result = [];

        foreach($ads as $ad) {

            // Получите данные фильтров с помощью функции getVariantsData
            $variantsData = $this->getVariantsData($ad['ads_id'], $ad['ads_id_cat']);
            dd($variantsData);
            $updatedFilters = [];

            foreach ($variantsData as $variant) {
                $filterId = $variant['filter_id'];
                $type = $variant['type'];
                $position = $variant['position'];
                $required = $variant['required'];

                $items = [];
                if ($type === 'select_multi') {
                    // Если тип фильтра 'select_multi', то $propertyValue - это массив значений
                    foreach ($variant['items'] as $value) {
                        $items[] = [
                            'id' => $value['ads_filters_items_id'],
                            'value' => $value['ads_filters_items_value']
                        ];
                    }
                } else {
                    foreach($variant['items'] as $item){
                        $items = [
                            'id' => $item['ads_filters_items_id'],
                            'value' => $item['ads_filters_items_value']
                        ];
                    }

                }

                $updatedFilters[] = [
                    'filter_id' => $filterId,
                    'title' => $variant['title'],
                    'items' => $items,
                    'type' => $type,
                    'position' => $position,
                    'required' => $required,
                ];
            }

            $ad['filters'] =  $updatedFilters;
            $ad['count_views'] = (string)$this->getCountView($ad['ads_id']);
            $ad['count_views_phone'] = (string)$this->getCountPhone($ad['ads_id']);
            $ad['in_favorite'] = (string)$this->getCountFavorites($ad['ads_id']);
            $ad['days'] = $this->difference_days($ad['ads_period_publication'], date('Y-m-d H:i:s'));
            $result[] = $ad;
        }
        return $result;
    }
    public function prepareAdData($ad)
    {
        $baseUrl = 'https://joymee.uz/media/images_boards/big/';

        if(isset($ad['ads_images'])) {
            $images = $ad['ads_images'];
            $fullImagePath = array_map(function ($image) use ($baseUrl) {
                return $baseUrl . $image;
            }, $images);

            $ad['ads_images'] = $fullImagePath;
        }

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

        return $ad;
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

    public function getCountView($id, $date = "") {
        $query = AdsView::where('ads_views_id_ad', $id);
        if ($date) {
            $query->whereDate('ads_views_date', $date);
        }
        return $query->count();
    }

    public function getCountPhone($id, $date = "")
    {
        $query = ActionStatistic::where('action_statistics_ad_id', $id);
        if ($date) {
            $query->whereDate('action_statistics_date', $date);
        }
        return $query->count();
    }

    public function getCountFavorites($id_ad = 0)
    {
        return UniFavorite::where('favorites_id_ad', $id_ad)->count();
    }

    public function difference_days($date_max='',$date_min='')
    {
        if(strtotime($date_max) > strtotime($date_min)){
            $date_max = new DateTime($date_max);
            $date_min = new DateTime($date_min);
            return $date_max->diff($date_min)->format('%a');
        }else{
            return 0;
        }
    }



}
