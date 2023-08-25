<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string',
            'role' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Это поля неоходимо заполнить',
            'name.string' => 'Это поля должно быть строкой',
            'email.required' => 'Это поля неоходимо заполнить',
            'email.string' => 'Это поля должно быть строкой',
            'email.unique' => 'Пользователь с таким email уже существует',
            'email.email' => 'Ваша почта должна соответсовать формату email@some.domain',
            'password.required' => 'Это поля неоходимо заполнить',
            'password.string' => 'Это поля должно быть строкой',
        ];
    }
}
