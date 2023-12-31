<?php

namespace App\Http\Requests\drivers;

use Illuminate\Foundation\Http\FormRequest;

class VehicleDocumentRequest extends FormRequest
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
            'car_type_id'           => 'required',
            'car_brand_id'          => 'required',
            'car_color'             => 'required',
            'metal_plate_numbers'   => 'required|digits:3',
            'model_year'            => 'required',
            'license_expire_date'   => 'required|date',
            'vehicle_license'       => 'required|file|max:1024|mimes:pdf,doc,docx',
            'vehicle_license_behind'=> 'required|file|max:1024|mimes:pdf,doc,docx',
            'vehicle_inspection'    => 'nullable|file|max:1024|mimes:pdf,doc,docx'
           ];
    }
}
