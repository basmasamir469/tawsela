<?php

namespace App\Http\Requests\drivers;

use Illuminate\Foundation\Http\FormRequest;

class DriverDocumentRequest extends FormRequest
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
            'national_number'    =>'required|digits:12|unique:users,national_number',
            'nationalId_image'   =>'required|image',
            'driving_license'    =>'required|file|max:1024|mimes:pdf,doc,docx',
            'personal_image'     =>'required|file|max:1024|mimes:png,jpg',
            'drug_analysis'      =>'nullable|file|max:1024|mimes:pdf,doc,docx',
            'criminal_record'    =>'nullable|file|max:1024|mimes:pdf,doc,docx',
        ];
    }
}
