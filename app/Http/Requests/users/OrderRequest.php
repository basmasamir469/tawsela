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
            'address_id'                =>'nullable',
            'permenant_end_longitude'   =>'nullable',
            'permenant_end_latitude'    =>'nullable',
            'start_address'             =>'nullable',
            'end_address'               =>'nullable',
            'permenant_end_address'     =>'nullable',
            'start_latitude'            =>'nullable',
            'start_longitude'           =>'nullable',
            'end_longitude'             =>'required_if:permenant_end_longitude,nullable|required_if:address_id,nullable',
            'end_latitude'              =>'required_if:permenant_end_latitude,nullable|required_if:address_id,nullable',
            'car_type_id'               =>'required',
            'promo_code'                =>'nullable'

        ];
    }
}
