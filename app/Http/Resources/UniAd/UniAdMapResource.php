<?php

namespace App\Http\Resources\UniAd;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use Illuminate\Support\Str;

class UniAdMapResource extends JsonResource
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
            'short_price' => $this->short_price,
            'address' => $this->ads_address,
            'latitude' => $this->ads_latitude,
            'longitude' => $this->ads_longitude,
        ];
    }
}

