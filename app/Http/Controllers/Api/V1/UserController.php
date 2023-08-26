<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateRequest;
use App\Http\Resources\HistoryBalanceResource;
use App\Models\Client;
use App\Models\HistoryBalance;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserController extends Controller
{
    public function index()
    {
        $users = Client::all(); // Получаем всех пользователей
        return response()->json(['users' => $users]);
    }

    public function edit(Client $client)
    {
        return response()->json(['user' => $client]);
    }


    public function update(UpdateRequest $request, $clientId) {

        $data = $request->validated();

        $user = Client::findOrFail($clientId);


        $user->update($data);

        // Check if a new password is provided and hash it
        if ($request->has('clients_pass')) {
            $hashedPassword = password_hash($data['clients_pass']."4f7b37eac80ddaac99086dec1ff21a41",PASSWORD_DEFAULT);
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
            $message = ["message" =>  $client->clients_balance ];
        } catch (ModelNotFoundException $e) {
            $message = ["message" => "Пользователь не найден"];
        }

        return response()->json($message);
    }
}
