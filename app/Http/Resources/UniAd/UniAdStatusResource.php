<?php

namespace App\Http\Resources\UniAd;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use Illuminate\Support\Str;

class UniAdStatusResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ads_id,
            'title' => $this->ads_title,
            'ads_alias' => $this->ads_alias,
            'ads_text' => $this->ads_text,
            'category_id' => $this->ads_id_cat,
            'ads_city_id' => $this->ads_city_id,
            'ads_id_user' => $this->ads_id_user,
            'ads_images' => $this->ads_images,
            'ads_price' => $this->ads_price,
            'short_price' => $this->short_price,
            'address' => $this->ads_address,
            'latitude' => $this->ads_latitude,
            'longitude' => $this->ads_longitude,
            'ads_period_publication' => $this->ads_period_publication,
            'ads_status' => $this->ads_status,
            'ads_region_id' => $this->ads_region_id,
            'ads_country_id' => $this->ads_country_id,
            'ads_currency' => $this->ads_currency,
            'ads_period_day' => $this->ads_period_day,
            'ads_area_ids'   => $this->ads_area_ids,
            'ads_tel' => $this->ads_tel,
            'ads_filter_tags' => $this->ads_filter_tags,
            'filters' => $this->filters,
            'ads_status_name' => $this->resource->getStatusName(),
            'ads_note' => $this->ads_note,
            'count_views' => $this->count_views,
            'count_views_phone' => $this->count_views_phone,
            'in_favorite' => $this->in_favorite,
            'days' => $this->days,
            'category_parent_id' => $this->category_parent_id,
        ];
    }
}

