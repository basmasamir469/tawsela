<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
        $role = auth()->user()->roles->pluck('name')[0];
        $rule = $role == 'driver'? 'required' : 'nullable';
        return [
            'national_number'       =>$rule.'|digits:12|unique:users,national_number,'.auth()->user()->id,
            'nationalId_image'      =>'nullable|image',
            'driving_license'       =>'nullable|file|max:1024|mimes:pdf,doc,docx',
            'personal_image'        =>'nullable|file|max:1024|mimes:png,jpg',
            'drug_analysis'         =>'nullable|file|max:1024|mimes:pdf,doc,docx',
            'criminal_record'       =>'nullable|file|max:1024|mimes:pdf,doc,docx',
            'car_type_id'           => $rule,
            'car_brand_id'          => $rule,
            'car_color'             => $rule,
            'metal_plate_numbers'   => $rule.'|digits:3',
            'model_year'            => $rule,
            'license_date'          => $rule,
            'vehicle_license'       => 'nullable|file|max:1024|mimes:pdf,doc,docx',
            'vehicle_license_behind'=> 'nullable|file|max:1024|mimes:pdf,doc,docx',
            'vehicle_inspection'    => 'nullable|file|max:1024|mimes:pdf,doc,docx',
            'name'                  =>'required',
            'phone'                 =>'required|regex:/(01)[0-9]{9}/',
            'address'               =>'required',
            'image'                 =>'nullable'

        ];
    }
}
