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
        $this->middleware('auth:api', ['except' => ['login', 'register', 'step2']]);
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */

//    public function register(Request $request) {
//
//        $validator = Validator::make($request->all(), [
//            'clients_name' => 'required|string|between:2,100',
//            'clients_pass' => 'required|string|confirmed|min:8',
//            'type_is_email' => 'required|boolean',
//            'clients_email' => 'required_if:type_is_email,true|string|email|max:100|unique:uni_clients,clients_email', // Валидация email
//            'clients_phone' => 'required_if:type_is_email,false|string|max:30|unique:uni_clients,clients_phone', // Валидация телефона
//            'clients_id_hash' => 'string',
//        ]);
//
///*        if ($validator->fails()) {
//            return response()->json($validator->errors()->toJson(), 400);
//        }*/
//        if ($validator->fails()) {
//            $firstErrorMessage = $validator->errors()->first();
//            return response()->json(['message' => $firstErrorMessage], 400);
//        }
//
//        $userData = [
//            'clients_name' => $request->clients_name,
//            'clients_pass' => password_hash($request->clients_pass . "4f7b37eac80ddaac99086dec1ff21a41", PASSWORD_DEFAULT),
//            'clients_id_hash' => md5($request->input('clients_email') ?: $request->input('clients_phone')),
//        ];
//
//        // В зависимости от типа регистрации, добавляем соответствующие данные в массив
//        if ($request->input('type_is_email')) {
//            $userData['clients_email'] = $request->input('clients_email');
//        } else {
//            $userData['clients_phone'] = $request->input('clients_phone');
//        }
//
//        $user = Client::create($userData);
//
//
//        $token = Auth::login($user);
//
//        return $this->getToken($token);
//
//
//    }

    public function register(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'clients_name' => 'required|string|between:2,100',
            'type_is_email' => 'required|boolean',
            'clients_email' => 'required_if:type_is_email,true|string|email|max:100|unique:uni_clients,clients_email',
            'clients_phone' => 'required_if:type_is_email,false|string|max:30|unique:uni_clients,clients_phone',
            'clients_id_hash' => 'string',
        ]);

        if ($validator->fails()) {
            $firstErrorMessage = $validator->errors()->first();
            return response()->json(['message' => $firstErrorMessage], 400);
        }

        // Создание пользователя в базе данных
        $userData = [
            'clients_name' => $request->clients_name,
            'clients_pass' => password_hash($request->clients_pass . "4f7b37eac80ddaac99086dec1ff21a41", PASSWORD_DEFAULT),
            'clients_id_hash' => md5($request->clients_email ?: $request->clients_phone),
        ];

        // В зависимости от типа регистрации, добавляем соответствующие данные в массив
        if ($request->type_is_email) {
            $userData['clients_email'] = $request->clients_email;
        } else {
            $userData['clients_phone'] = $request->clients_phone;
        }

        Cache::put('user_data', $userData, now()->addMinutes(15));


        if (!$request->type_is_email) {
            $phone = $this->formatPhone($request->clients_phone);
            $verificationCode = $this->smsVerificationCode($phone);

            // Сохраняем код подтверждения и другие данные в сессии или переменных
            return response()->json(['status' => true, 'verification_code' => $verificationCode]);
        }
        else  {

            $verificationCode = mt_rand(1000, 9999);

            try {
                Mail::to($request->input('clients_email'))->send(new EmailVerification($verificationCode));
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to send email verification'], 500);
            }


        }

    }


    public function step2(Request $request)
    {
        $userData = Cache::get('user_data');

        if (!$userData) {
            return response()->json('registration fails');

        }
        $user = Client::create($userData);

        // Создание токена и возврат ответа
        $token = Auth::login($user);

        Cache::forget('user_data');
        return $this->getToken($token);

    }



    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clients_email' => 'required|email',
            'clients_pass' => 'required|string',
        ]);


        $user = Client::where('clients_email', $request->clients_email)->first();

        if (!$user || !password_verify($request->clients_pass."4f7b37eac80ddaac99086dec1ff21a41", $user->clients_pass)) {
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
            // 'expires_in' => auth()->factory()->getTTL() * 60,
            //  'user' => auth()->user()
        ]);
    }

    protected function getToken($token){
        return response()->json([
            'message' => 'User successfully registered',
            'access_token' => $token,
            'token_type' => 'bearer',
            // 'expires_in' => auth()->factory()->getTTL() * 60,
            //  'user' => auth()->user()
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


//    public function sms($phone_to="",$text="",$method="sms"){
//
//        $sms_service_pass = 'Qz75@1%A1*c';
//        $sms_service_login = 'kabirjanov';
//        $params['messages'] = [
//            "recipient" => trim($phone_to, '+'),
//            "message-id" => mt_rand(1000000,9000000),
//            "sms" => [
//                "originator" => '3700',
//                "content" => [
//                    "text" => $text
//                ]
//            ]
//        ];
//
//        return json_encode(sendPostRequest('http://91.204.239.44/broker-api/send', $params, ['Content-Type: application/json', 'Authorization: Basic '.base64_encode($sms_service_login.":".$sms_service_pass)]));
//
//    }

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
