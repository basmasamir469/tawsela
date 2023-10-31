<?php

namespace App\Http\Requests\users;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
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
            'start_address'             =>'nullable',
            'end_address'               =>'nullable',
            'start_latitude'            =>'required|numeric|between:-90,90',
            'start_longitude'           =>'required|numeric|between:-180,180',
            'end_longitude'             =>'required|numeric|between:-180,180',
            'end_latitude'              =>'required|numeric|between:-90,90',
            'car_type_id'               =>'required',
            'promo_code'                =>'nullable',
            'price'                     =>'required|numeric',
            'drive_distance'            =>'nullable'

        ];
    }
}
