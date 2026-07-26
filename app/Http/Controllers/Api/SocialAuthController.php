<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SocialLoginRequest;
use App\Services\Auth\SocialAuthService;
use Illuminate\Http\Request;

class SocialAuthController extends Controller
{
    public function __construct(private readonly SocialAuthService $socialAuth)
    {
    }

    public function login(SocialLoginRequest $request)
    {
        $data = $request->validated();

        try {
            $result = $this->socialAuth->loginWithProvider(
                $data['provider'], 
                $data['token'], 
                $data['role'] ?? 'user'
            );

            return $this->dataResponse([
                'token' => $result['token'],
                'user' => $result['user'],
            ], __('logged in successfully'), 200);
        } catch (\Exception $e) {
            return $this->dataResponse(null, $e->getMessage(), 422);
        }
    }
}
