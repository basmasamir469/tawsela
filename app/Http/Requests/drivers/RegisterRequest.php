<?php

namespace App\Http\Requests\drivers;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'    =>'required',
            'phone'   =>'required|unique:users|regex:/(01)[0-9]{9}/',
            'address' =>'required',
            'password'=>'confirmed|min:6',
            'image'   =>'required'
        ];
    }
}
