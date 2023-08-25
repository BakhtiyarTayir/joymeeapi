<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function getAreaByCites($cityId)
    {
        $city = City::findOrFail($cityId);


        $area = $city->areas;

        return response()->json($area);
    }
}
