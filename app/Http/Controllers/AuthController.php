<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerification;
use Illuminate\Http\Request;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login', 'registerStep1', 'registerStep2']]);
    }




    public function registerStep1(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type_is_email' => 'required|boolean',
            'clients_email' => 'required_if:type_is_email,true|string|email|max:100',
            'clients_phone' => 'required_if:type_is_email,false|string|max:30',
        ]);

        if ($validator->fails()) {
            $firstErrorMessage = $validator->errors()->first();
            return response()->json(['message' => $firstErrorMessage], 400);
        }

        // Проверка существования пользователя
        if ($request->type_is_email) {
            $existingUser = Client::where('clients_email', $request->clients_email)->first();
        } else {
            $existingUser = Client::where('clients_phone', $request->clients_phone)->first();
        }

        if ($existingUser) {
            return response()->json(['message' => 'User already exists'], 400);
        }

        if (!$request->type_is_email) {
            $phone = $this->formatPhone($request->clients_phone);
            $verificationCode = $this->smsVerificationCode($phone);

            return response()->json([
                'status' => true,
                'verification_code' => $verificationCode,
            ]);
        } else {
            $verificationCode = mt_rand(1000, 9999);

            try {
                Mail::to($request->input('clients_email'))->send(new EmailVerification($verificationCode));
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to send email verification'], 500);
            }

            return response()->json([
                'status' => true,
                'verification_code' => $verificationCode,
            ]);
        }
    }

    public function registerStep2(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clients_name' => 'required|string|between:2,100',
            'clients_email' => 'nullable|string|email|max:100',
            'clients_phone' => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            $firstErrorMessage = $validator->errors()->first();
            return response()->json(['message' => $firstErrorMessage], 400);
        }


        $userData = [
            'clients_name' => $request->clients_name,
            'clients_pass' => password_hash($request->clients_pass . "4f7b37eac80ddaac99086dec1ff21a41", PASSWORD_DEFAULT),
            'clients_id_hash' => md5($request->clients_email ?: $request->clients_phone),
            'clients_balance' => 10000,
        ];

        if ($request->clients_email) {
            $userData['clients_email'] = $request->clients_email;
        } else {
            $userData['clients_phone'] = $request->clients_phone;
        }

        $user = Client::create($userData);

        // Создание токена и возврат ответа
        $token = Auth::login($user);

        return $this->getToken($token);
    }


    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
//    public function login(Request $request)
//    {
//        $validator = Validator::make($request->all(), [
//            'clients_email' => 'required|email',
//            'clients_pass' => 'required|string',
//        ]);


//        $user = Client::where('clients_email', $request->clients_email)->first();

//        if (!$user || !password_verify($request->clients_pass."4f7b37eac80ddaac99086dec1ff21a41", $user->clients_pass)) {
//            return response()->json(['error' => 'Unauthorized'], 401);
//        }

//        $token = Auth::login($user);

//        return $this->createNewToken($token);
//    }




    public function login(Request $request)
    {
        $credentials = $request->only(['clients_email', 'clients_pass']);

        // Определите, является ли входом email или номер телефона
        $isEmail = filter_var($credentials['clients_email'], FILTER_VALIDATE_EMAIL);



        // Ищем пользователя по email или номеру телефона
        $user = $isEmail
            ? Client::where('clients_email', $credentials['clients_email'])->first()
            : Client::where('clients_phone', $credentials['clients_email'])->first();



        if (!$user || !password_verify($credentials['clients_pass'] . "4f7b37eac80ddaac99086dec1ff21a41", $user->clients_pass)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $token = Auth::login($user);

        return $this->createNewToken($token);
    }



    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() {
        auth()->logout();
        return response()->json(['message' => 'User successfully signed out']);
    }
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh() {
        return $this->createNewToken(auth()->refresh());
    }
    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile() {
        $user = auth()->user();

        // Если у пользователя нет clients_avatar, устанавливаем ссылку на заполнительное изображение
        if (empty($user->clients_avatar)) {
            $user->clients_avatar = 'https://joymee.uz/media/others/no_avatar.png';
        }

        $responseData = [
            'clients_id' => $user->clients_id,
            'clients_email' => $user->clients_email ?: '',
            'clients_status' => $user->clients_status,
            'clients_avatar' => $user->clients_avatar,
            'clients_phone' => $user->clients_phone ?: '',
            'clients_name' => $user->clients_name,
            'clients_surname' => $user->clients_surname ?: '',
            'clients_balance' => $user->clients_balance,
            'clients_type_person' => $user->clients_type_person,
            'clients_name_company' => $user->clients_name_company ?: '',
            'clients_city_id' => $user->clients_city_id,
            'clients_additional_phones' => $user->clients_additional_phones ?: '',
        ];


        return response()->json($responseData );
    }


    public function deleteAccount(Request $request)
    {
        $user = Auth::user(); // Получаем текущего аутентифицированного пользователя

        // Проверяем, существует ли пользователь
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Дополнительные проверки (например, подтверждение паролем или другие безопасные меры)

        // Удаление аккаунта
        $user->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
           //  'expires_in' => auth()->factory()->getTTL() * 60,
            'expires_in' => null,
            'user' => auth()->user()
        ]);
    }

    protected function getToken($token){
        return response()->json([
            'message' => 'User successfully registered',
            'access_token' => $token,
            'token_type' => 'bearer',
             //'expires_in' => auth()->factory()->getTTL() * 60,
            'expires_in' => null,
            'user' => auth()->user()
        ]);
    }






    public function smsVerificationCode($phone=''){
        $sms_prefix_confirmation_code = "joymee.uz. Код подтверждения:";

        if($phone){
            $code = mt_rand(1000,9999);
            $this->sms($phone, $sms_prefix_confirmation_code.$code, 'sms');
            return $code;
        }

    }




    public function sms($phone_to = "", $text = "", $method = "sms")
    {
        $sms_service_pass = 'Qz75@1%A1*c';
        $sms_service_login = 'kabirjanov';

        $params = [
            "messages" => [
                [
                    "recipient" => trim($phone_to, '+'),
                    "message-id" => mt_rand(1000000, 9000000),
                    "sms" => [
                        "originator" => '3700',
                        "content" => [
                            "text" => $text
                        ]
                    ]
                ]
            ]
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($sms_service_login . ":" . $sms_service_pass),
        ])->post('http://91.204.239.44/broker-api/send', $params);

        return $response->json();
    }

    public function formatPhone($phone=""){
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if($phone) return trim($phone);
    }




}
