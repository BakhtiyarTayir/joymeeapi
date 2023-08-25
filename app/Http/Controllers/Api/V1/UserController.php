<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateRequest;
use App\Models\Client;

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
}
