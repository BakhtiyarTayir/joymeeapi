<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdsFilter;
use App\Models\AdsFilterCategory;
use Illuminate\Http\Request;

class AdsFilterController extends Controller
{
    public function index()
    {
        $filter = AdsFilter::all();
        return response()->json($filter);
    }

    public function getFilterFormData()
    {
        // Получаем все фильтры из таблицы uni_ads_filters
        $filters = AdsFilter::all();

        $formData = [];

        foreach ($filters as $filter) {
            $filterData = [
                'name' => $filter->ads_filters_name,
                'type' => $filter->ads_filters_type,
                'items' => $filter->filterItems->pluck('ads_filters_items_value')
            ];

            $formData[] = $filterData;
        }

        return response()->json($formData);
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
                    'id' => $filter->ads_filters_id,
                    'name' => $filter->ads_filters_name,
                    'type' => $filter->ads_filters_type,
                    'items' => $filter->filterItems->pluck('ads_filters_items_value'),
                    'position' => $filter->ads_filters_position,
                    'required' => $filter->ads_filters_required,
                ];

                $filtersData[] = $filterData;
            }
        }

        usort($filtersData, function($a, $b) {
            return $a['position'] - $b['position'];
        });


        return response()->json($filtersData);
    }


}
