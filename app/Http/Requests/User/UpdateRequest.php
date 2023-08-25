<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'clients_pass' => 'string',
            'clients_email' => 'string',
//            'clients_avatar' => 'nullable|string|max:500',
            'clients_phone' => 'string',
            'clients_name' => 'string',
            'clients_surname' => 'string',
            'clients_balance' => 'numeric',
            'clients_type_person' => 'string',
            'clients_city_id' => 'integer',
            'clients_additional_phones' => 'string',
            'clients_view_phone' => 'integer',
        ];
    }
}


