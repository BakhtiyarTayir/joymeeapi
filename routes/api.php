<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group([

    'middleware' => 'api',
    'prefix' => 'auth'

], function () {


     Route::post('login', [\App\Http\Controllers\AuthController::class, 'login']);
     Route::post('logout', [\App\Http\Controllers\AuthController::class, 'logout']);
     Route::post('refresh', [\App\Http\Controllers\AuthController::class, 'refresh']);
     Route::post('me', [\App\Http\Controllers\AuthController::class, 'userProfile']);
     Route::post('register', [\App\Http\Controllers\AuthController::class, 'register']);
     Route::get('step2', [\App\Http\Controllers\AuthController::class, 'step2']);


});


Route::group([


], function () {


    Route::get('/ad_categories', [\App\Http\Controllers\Api\V1\CategoryBoardController::class, 'index']);
    Route::get('/ad_categories/{id}', [\App\Http\Controllers\Api\V1\CategoryBoardController::class, 'show']);
    Route::get('/ads_cat', [\App\Http\Controllers\Api\V1\CategoryBoardController::class, 'ads']);
    Route::get('/categories/{category}/ads', [\App\Http\Controllers\Api\V1\UniAdController::class, 'getAdsByCategory']);

    Route::get('/region', [\App\Models\UniRegion::class, 'region']);

    Route::get('/countries/{countryId}/regions', [\App\Http\Controllers\Api\V1\CountryController::class, 'getRegionsByCountry']);
    Route::get('/regions/{regionId}/cities', [\App\Http\Controllers\Api\V1\RegionController::class, 'getCitiesByRegion']);
    Route::get('/city/{cityId}/areas', [\App\Http\Controllers\Api\V1\CityController::class, 'getAreaByCites']);
    Route::get('/countries/{countryId}/regions/{regionId}/cities', [\App\Http\Controllers\Api\V1\CountryController::class, 'getCitiesByCountryAndRegion']);
    Route::get('/city_id/{city_id}/ad_category/{ad_category}', [\App\Http\Controllers\Api\V1\UniAdController::class, 'getAdsByCityAndCategory']);
    Route::get('/reels', [\App\Http\Controllers\Api\V1\ReelsController::class, 'index']);
    Route::get('/ads/show', [\App\Http\Controllers\Api\V1\UniAdController::class, 'showWithFilter']);
    Route::get('/ads/prop', [\App\Http\Controllers\Api\V1\UniAdController::class, 'showFilter']);
    Route::get('/filter/show', [\App\Http\Controllers\Api\V1\AdsFilterController::class, 'getFilterFormData']);
    Route::get('/ads_category/{id_cat}/filter', [\App\Http\Controllers\Api\V1\AdsFilterController::class, 'getFiltersForCategory']);

    Route::group(['namespace' => 'User', 'prefix'=>'users'], function() {
        Route::get('/', [App\Http\Controllers\Api\V1\UserController::class, 'index']);
//        Route::get('/create', [App\Http\Controllers\Api\V1\UserController::class, 'create']);
//        Route::post('/', [App\Http\Controllers\Api\V1\UserController::class, 'store']);
//        Route::get('/{user}', [App\Http\Controllers\Api\V1\UserController::class, 'show']);
//        Route::get('/{user}/edit', [App\Http\Controllers\Api\V1\UserController::class, 'edit']);
        Route::patch('/{user}', [App\Http\Controllers\Api\V1\UserController::class, 'update']);
        Route::delete('/{user}', [App\Http\Controllers\Api\V1\UserController::class, 'destroy']);
    });

});

Route::apiResource('countries', \App\Http\Controllers\Api\V1\CountryController::class);

Route::resource('ads', \App\Http\Controllers\Api\V1\UniAdController::class);