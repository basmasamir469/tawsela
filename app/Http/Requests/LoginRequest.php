<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
        $rule = $this->type == 'email'? '|email' : '|regex:/(01)[0-9]{9}/';
        return [
            'type'   =>'required|in:email,phone',
            'value'  =>'required'.$rule,
            'password'=>'required'
        ];
    }
}
