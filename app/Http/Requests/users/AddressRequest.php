<?php

namespace App\Http\Requests\users;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
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
            'type'      =>'required|in:0,1,2',
            'name'      =>'required',
            'latitude'  =>'required|numeric|between:-90,90',
            'longitude' =>'required|numeric|between:-180,180'
        ];
    }
}
