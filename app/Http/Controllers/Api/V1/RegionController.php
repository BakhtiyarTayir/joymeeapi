<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UniRegion;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    public function getCitiesByRegion($regionId)
    {

        $region = UniRegion::findOrFail($regionId);
//        dd($region);
        $cities = $region->cities;

        return response()->json($cities);
    }
}
