<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\UniAd\UniAdBaseStatusResource;
use App\Models\UniAd;
use App\Models\Client;
use App\Models\HistoryBalance;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateRequest;
use App\Http\Resources\UniAd\UniAdResource;
use App\Http\Resources\HistoryBalanceResource;
use App\Http\Resources\UniAd\UniAdStatusResource;
use App\Models\UniCategoryBoard;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\AdService;

class UserController extends Controller
{

    protected AdService $adService;

    public function __construct(AdService $adService)
    {
        $this->adService = $adService;
    }
    public function index()
    {
        $users = Client::all(); // Получаем всех пользователей
        return response()->json(['users' => $users]);
    }

    public function edit(Client $client)
    {
        return response()->json(['user' => $client]);
    }


    public function update(UpdateRequest $request, $clientId)
    {

        $data = $request->validated();

        $user = Client::findOrFail($clientId);


        $user->update($data);

        // Check if a new password is provided and hash it
        if ($request->has('clients_pass')) {
            $hashedPassword = password_hash($data['clients_pass'] . "4f7b37eac80ddaac99086dec1ff21a41", PASSWORD_DEFAULT);
            $user->update(['clients_pass' => $hashedPassword]);
        }

        return response()->json(['message' => 'User updated successfully']);
    }

    public function destroy(Client $client)
    {
        $client->delete(); // Удаляем пользователя
        return response()->json(['message' => 'User deleted successfully']);
    }

    public function getHistoryBalance($user_id)
    {


        $balance = HistoryBalance::where('id_user', $user_id)->get();

        return HistoryBalanceResource::collection($balance);
    }

    public function getBalance($user_id)
    {

        try {
            $client = Client::findOrFail($user_id);
            $message = ["message" => $client->clients_balance];
        } catch (ModelNotFoundException $e) {
            $message = ["message" => "Пользователь не найден"];
        }

        return response()->json($message);
    }

    public function getUserAds($userId)
    {
        $activeAds = UniAd::where('ads_id_user', $userId)
            ->where('ads_status', 1)
            ->where('ads_period_publication', '>', now())
            ->get();


        $archiveAds = UniAd::where('ads_id_user', $userId)
            ->where(function ($query) {
                $query->whereNotIn('ads_status', [0, 1, 4, 5, 7])
                    ->orWhere('ads_period_publication', '<', now());
            })
            ->where('ads_status', '!=', 8)
            ->get();

        $waitingAds = UniAd::where('ads_id_user', $userId)
            ->where('ads_status', 0)
            ->get();

        $moderatedAds = UniAd::where('ads_id_user', $userId)
            ->where('ads_status', 7)
            ->get();

        $activeAdsResult = $this->adService->prepareFilters($activeAds);
        $archiveAdsResult = $this->adService->prepareAdsFilterData($archiveAds);
        $waitingAdsResult = $this->adService->prepareAdsFilterData($waitingAds);
        $moderatedAdsResult = $this->adService->prepareAdsFilterData($moderatedAds);

        foreach ($activeAdsResult as &$ad) {
            $parentCategory = UniCategoryBoard::where('category_board_id', $ad['ads_id_cat'])->first();
            if ($parentCategory) {
                $ad['category_parent_id'] = $parentCategory->category_board_id_parent;
            }
        }

        foreach ($archiveAdsResult as &$ad) {
            $parentCategory = UniCategoryBoard::where('category_board_id', $ad['ads_id_cat'])->first();
            if ($parentCategory) {
                $ad['category_parent_id'] = $parentCategory->category_board_id_parent;
            }
        }

        foreach ($waitingAdsResult as &$ad) {
            $parentCategory = UniCategoryBoard::where('category_board_id', $ad['ads_id_cat'])->first();
            if ($parentCategory) {
                $ad['category_parent_id'] = $parentCategory->category_board_id_parent;
            }
        }

        foreach ($moderatedAdsResult as &$ad) {
            $parentCategory = UniCategoryBoard::where('category_board_id', $ad['ads_id_cat'])->first();
            if ($parentCategory) {
                $ad['category_parent_id'] = $parentCategory->category_board_id_parent;
            }
        }



        $adsByStatus = [
            'active' => UniAdBaseStatusResource::collection(collect($activeAdsResult)),
            'archive' => UniAdStatusResource::collection(collect($archiveAdsResult)),
            'waiting' => UniAdStatusResource::collection(collect($waitingAdsResult)),
            'moderated' => UniAdStatusResource::collection(collect($moderatedAdsResult)),
        ];


        return response()->json($adsByStatus);
    }

}
