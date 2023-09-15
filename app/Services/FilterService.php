<?php

namespace App\Services;

use App\Models\AdsFilter;
use App\Models\UniAdFilter;
use App\Models\AdsFiltersItem;
use App\Models\UniAdFilterItem;
use App\Models\UniCategoryBoard;
use App\Models\AdsFilterCategory;
use App\Models\AdsFiltersVariant;
use Illuminate\Support\Facades\Cache;


class FilterService
{

    public $filterService;
    function load_filters_ad($id_cat = 0, $getVariants = array(), $ad_id)
    {

        $filters_ids = $this->getCategory(["id_cat" => $id_cat]);

        $filters = [];

        if ($filters_ids) {
            $query = "ads_filters_visible='1' and ads_filters_id IN(" . implode(",", $filters_ids) . ")";
        } else {
            $query = "ads_filters_visible='1' and ads_filters_id IN(0)";
        }

        $getFilters = $this->getFilters($query);

        if ($getFilters["id_parent"][0]) {
            foreach ($getFilters["id_parent"][0] as $id_filter => $value) {
                $filter = [
                    "id" => $value["ads_filters_id"],
                    "name" => $value["ads_filters_name"],
                    "type" => $value["ads_filters_type"],
                    "required" => $value["ads_filters_required"] ? true : false,
                    "items" => [],
                ];

                $getItems = AdsFiltersItem::where('ads_filters_items_id_filter', $value["ads_filters_id"])->get();

                if ($value["ads_filters_type"] == "select" || $value["ads_filters_type"] == "select_multi" || $value["ads_filters_type"] == "checkbox") {
                    if ($getItems->count()) {
                        foreach ($getItems as $item_key => $item_value) {
                            $active = false;
                            $checked = false;
                            
                            if (isset($getVariants["items"][$value["ads_filters_id"]][$item_value["ads_filters_items_id"]])) {
                                $active = true;
                                $checked = true;
                            }

                            $filter["items"][] = [
                                "id" => $item_value["ads_filters_items_id"],
                                "label" => $item_value["ads_filters_items_value"],
                                "checked" => $checked,
                            ];
                        }
                    }
                } elseif ($value["ads_filters_type"] == "slider" || $value["ads_filters_type"] == "input") {

                    if ($getItems->count()) {
                        foreach($getItems as $input_item => $input_value){
                            $variantsVal = AdsFiltersVariant::where([
                                'ads_filters_variants_id_filter' => $value["ads_filters_id"],
                                'ads_filters_variants_product_id' => $ad_id,
                            ])->pluck('ads_filters_variants_val')->first();
                         }
                        $filter["items"][] =  [
                            "id" => $input_value["ads_filters_items_id"],
                            "label" => $variantsVal,
                        ];
                        $filter["min_value"] = intval($getItems[0]["ads_filters_items_value"]);
                        $filter["max_value"] = intval($getItems[1]["ads_filters_items_value"]);
                    }
                }

                $filters[] = $filter;
            }
        }
        return $filters;
    }



    function getCategory($param = [])
    {
        $ids = [];

        if (isset($param["id_filter"])) {
            $get = AdsFilterCategory::where("ads_filters_category_id_filter", $param["id_filter"])->get();

            if ($get->count() > 0) {
                $ids = $get->pluck("ads_filters_category_id_cat")->toArray();
            }
        } elseif (isset($param["id_cat"])) {
            $get = AdsFilterCategory::where("ads_filters_category_id_cat", $param["id_cat"])->get();

            if ($get->count() > 0) {
                $ids = $get->pluck("ads_filters_category_id_filter")->toArray();
            }
        }

        return $ids;
    }


    function getFilters($query = "")
    {
        $data = [];
        $CategoryBoard = new UniCategoryBoard();

        // Попытка получить данные из кэша
        if (Cache::has($query)) {
            return Cache::get($query);
        } else {
            $getFilters = AdsFilter::whereRaw($query)->orderBy('ads_filters_position', 'ASC')->get();

            if ($getFilters->count() > 0) {
                foreach ($getFilters as $value) {
                    $data['id_parent'][$value['ads_filters_id_parent']][$value['ads_filters_id']] = $value->toArray();
                    $data['id'][$value['ads_filters_id']]['ads_filters_name'] = $value['ads_filters_name'];
                    $data['id'][$value['ads_filters_id']]['ads_filters_type'] = $value['ads_filters_type'];
                }

                // Сохраняем данные в кэше
                Cache::put($query, $data, now()->addHours(1));
            }

            return $data;
        }
    }

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
        
        return array();
    }


}
