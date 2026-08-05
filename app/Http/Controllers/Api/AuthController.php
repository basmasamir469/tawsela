<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckCodeRequest;
use App\Http\Requests\CheckResetPasswordCodeRequest;
use App\Http\Requests\ForgetPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Mail\VerifyEmail;
use App\Models\Token;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthService $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    public function register(RegisterRequest $request)
    {
        try {
            if (! $this->auth->register($request->validated(), $request->header('X-Role', 'user'))) {
                return $this->dataResponse(null, __('faild to send activation code! please try again'), 422);
            }

            return $this->dataResponse(null, __('registered successfully! activation code has been sent to your phone number'), 200);
        } catch (\Exception $e) {
            return $this->dataResponse(null, $e->getMessage(), 422);
        }
    }

    public function verifyUser(CheckCodeRequest $request)
    {
        $result = $this->auth->verifyUser($request->validated());

        if ($result) {
            $user = $result['user'];
            $token = $result['token'];
            event(new \App\Events\UserVerified($user));

            return $this->dataResponse(['token' => $token], __('your account is activated successfully!'), 200);
        }

        return $this->dataResponse(null, __('invalid code'), 422);
    }

    public function login(LoginRequest $request)
    {
        $result = $this->auth->login($request->validated());

        if (! $result) {
            return $this->dataResponse(null, __('faild to login! phone or password does not meet our credentials'), 422);
        }

        if ($result['activated']) {
            return $this->dataResponse([
                'activation' => $result['activation'],
                'token' => $result['token'],
            ], __('logged in successfully'), 200);
        }

        return $this->dataResponse(['activation' => 0], __('your account has not activated yet, activation code has been sent to your phone!'), 422);
    }

    public function forgetPassword(ForgetPasswordRequest $request)
    {
        if ($this->auth->sendPasswordResetCode($request->validated())) {
            return $this->dataResponse(null, __('we have sent reset password code to you'), 200);
        }

        return $this->dataResponse(null, __('credentials are not correct! please try again'), 422);
    }

    public function checkResetPasswordCode(CheckResetPasswordCodeRequest $request)
    {
        if ($this->auth->isPasswordResetCodeValid($request->validated())) {
            return $this->dataResponse(null, __('code is valid'), 200);
        }

        return $this->dataResponse(null, __('code is invalid'), 422);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        if ($this->auth->resetPassword($request->validated())) {
            return $this->dataResponse(null, __('password is updated successfully'), 200);
        }

        return $this->dataResponse(null, __('reset code is invalid'), 422);
    }

    public function logout(Request $request)
    {
        \App\Models\Token::where('device_id', $request->device_id)->delete();

        if ($request->user()->currentAccessToken()->delete()) {
            return $this->dataResponse(null, __('logged out successfully'), 200);
        }
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        try {
            $this->auth->updateProfile($user, $data);
        } catch (\Exception $e) {
            return $this->dataResponse(null, __('faild to update profile! please try again'), 422);
        }

        return $this->dataResponse(null, __('profile updated successfully'), 200);
    }
}