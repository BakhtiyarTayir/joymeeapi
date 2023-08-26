<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\OrdersParameter;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function payMethod($payment = "", $paramForm = array())
    {


            $paymentModel = new OrdersParameter(); // Создаем экземпляр модели

            $paymentModel->create([
                'orders_parameters_param' => json_encode($paramForm),
                'orders_parameters_id_uniq' => $paramForm["id_order"],
                'orders_parameters_date' => date("Y-m-d H:i:s"),
            ]);

        $amount = number_format($paramForm["amount"], 2, ".", "");


        return $arrFields = array(
            'amount' =>  $amount,
            'merchant_id' => 21586,
            'merchant_user_id' => 34574,
            'service_id' => 29266,
            'transaction_param' =>  $paramForm["id_order"],
            'return_url' =>  "https://joymee.uz/pay/status/success"
        );


    }

    public function getParamForm($payment, $user_id, $amount)
    {

        $getUser = Client::where("clients_id", $user_id)->first();
        $amount = round($amount,2);

        $answer = $this->payMethod( $payment, array( "amount" => $amount, "name" => $getUser["clients_name"], "email" => $getUser["clients_email"], "phone" => $getUser["clients_phone"], "id_order" => $this->generateOrderId(), "id_user" => $user_id, "action" => "balance", "title" => "Пополнение баланса - joymee.uz" ) );

        return response()->json( array( "status" => true, "redirect" => $answer ) );

    }

    public function generateOrderId(){
        return mt_rand(10000000,99999999);
    }
}
