<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UniAd\UniAdResource;
use App\Models\UniFavorite;
use App\Services\AdService;
use Illuminate\Http\Request;

class FavoritesController extends Controller
{

    protected AdService $adService;

    public function __construct(AdService $adService)
    {
        $this->adService = $adService;
    }
    public function index($user_id)
    {
        $favorites = UniFavorite::where('favorites_from_id_user', $user_id)
            ->with('ad')
            ->get()
            ->map(function ($favorite) {
                return $favorite->ad;
            });

        $favoritesResult = $this->adService->prepareAdsData($favorites);

        return UniAdResource::collection($favoritesResult);

    }
}

