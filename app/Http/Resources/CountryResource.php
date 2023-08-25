<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
       return [
        'country_id' => $this->country_id,
        'country_name' => $this->country_name,
        'country_status' => $this->country_status,
        'country_alias' => $this->country_alias,
        'country_lat' => $this->country_lat,
        'country_lng' => $this->country_lng,
        'country_desc' => $this->country_desc,
        'country_format_phone' => $this->country_format_phone,
        'country_code_phone' => $this->country_code_phone,
        'country_image' => $this->country_image,
        'country_declination' => $this->country_declination,
        ];
    }
}
