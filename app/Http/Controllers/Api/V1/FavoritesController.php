<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UniAd\UniAdResource;
use App\Models\UniAd;
use App\Models\UniFavorite;
use App\Services\AdService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;


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
    public function toggleFavorite(Request $request)
    {
        // Extract the parameters from the request
        $idAd = $request->input("id_ad");
        $userId = $request->input("user_id");


        $findAd = UniAd::where('ads_id', $idAd)->first();

        if ($findAd) {
            $findFavorite = UniFavorite::where('favorites_id_ad', $idAd)
                ->where('favorites_from_id_user', $userId)
                ->first();

            DB::beginTransaction();

            try {
                if ($findFavorite) {
                    $findFavorite->delete();
                    Log::info("Removed from favorites: User $userId, Ad $idAd");
                    $status = 0;
                } else {
                    $favorite = new UniFavorite();
                    $favorite->favorites_id_ad = $idAd;
                    $favorite->favorites_from_id_user = $userId;
                    $favorite->favorites_to_id_user = $findAd->ads_id_user;
                    $favorite->favorites_date = now();
                    $favorite->save();
                    Log::info("Added to favorites: User $userId, Ad $idAd");
                    $status = 1;
                }

                DB::commit();

                return response()->json([
                    "message" => __("Toggle Favorite"), // You can use localization for translations
                    "status" => $status,
                ]);
            } catch (\Exception $e) {
                DB::rollback();
                Log::error("Favorite toggle failed: " . $e->getMessage());

                return response()->json([
                    "message" => __("Toggle Favorite"), // You can use localization for translations
                    "status" => -1, // Indicates an error
                ]);
            }
        }

        return response()->json(["auth" => 0]);
    }

    private function authenticateUser($jwtToken, $userId)
    {
        try {
            // Attempt to verify the JWT token
            $user = JWTAuth::setToken($jwtToken)->authenticate();

            // Check if the user ID from the token matches the provided $userId
            if ($user && $user->clients_id == $userId) {
                return true;
            }

            return false;
        } catch (TokenExpiredException $e) {
            // Token has expired
            return false;
        } catch (TokenInvalidException $e) {
            // Token is invalid
            return false;
        } catch (JWTException $e) {
            // Other JWT exceptions
            return false;
        }

        return false;
    }

}

