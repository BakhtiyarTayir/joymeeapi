<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CountryResource;
use App\Models\Country;
use App\Models\UniRegion;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $countries = Country::all();
        return CountryResource::collection($countries);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Country $country)
    {
        return CountryResource::make($country);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getRegionsByCountry($countryId)
    {


        $country = Country::findOrFail($countryId);
        $regions = $country->regions;

        return response()->json($regions);
    }

    public function getCitiesByCountryAndRegion($countryId, $regionId)
    {
        $country = Country::findOrFail($countryId);
        $region = $country->regions()->findOrFail($regionId);
        $cities = $region->cities;

        return response()->json($cities);
    }


}
