<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SocialLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => 'required|string|in:facebook,google',
            'token' => 'required|string',
            'role' => 'sometimes|string|in:user,driver',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('provider')) {
            $this->merge([ 'provider' => strtolower($this->input('provider')) ]);
        }
    }
}
