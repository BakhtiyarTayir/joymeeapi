<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UniFavorite;
use Illuminate\Http\Request;

class FavoritesController extends Controller
{
    public function index($user_id)
    {
        $favorites = UniFavorite::where('favorites_from_id_user', $user_id)
            ->with(['ad' => function($query) {
                $query->select('ads_id', 'ads_title', 'ads_text', 'ads_id_cat', 'ads_images', 'ads_price', 'ads_id_user', 'ads_address', 'ads_latitude', 'ads_longitude', 'ads_city_id', 'ads_region_id', 'ads_currency', 'ads_period_day', 'ads_area_ids', 'ads_tel', 'ads_filter_tags');
            }])
            ->get();

        $filteredFavorites = $favorites->map(function ($favorite) {
            $ad = $favorite->ad;
            return [
                'id_user' => $favorite->favorites_from_id_user,
                'ad' => [
                    'ads_id' => $ad->ads_id,
                    'ads_title' => $ad->ads_title,
                    'ads_text' => $ad->ads_text,
                    'ads_id_cat' => $ad->ads_id_cat,
                    'ads_images' => $ad->ads_images,
                    'ads_price' => $ad->ads_price,
                    'ads_id_user' => $ad->ads_id_user,
                    'ads_address' => $ad->ads_address,
                    'ads_latitude' => $ad->ads_latitude,
                    'ads_longitude' => $ad->ads_longitude,
                    'ads_city_id' => $ad->ads_city_id,
                    'ads_region_id' => $ad->ads_region_id,
                    'ads_currency' => $ad->ads_currency,
                    'ads_period_day' => $ad->ads_period_day,
                    'ads_area_ids' => $ad->ads_area_ids,
                    'ads_tel' => $ad->ads_tel,
                    'ads_filter_tags' => $ad->ads_filter_tags,
                ],
            ];
        });

        return response()->json($filteredFavorites);

    }
}

