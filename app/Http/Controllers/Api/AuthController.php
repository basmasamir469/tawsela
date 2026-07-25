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
use App\Http\Requests\users\TokenRequest;
use App\Mail\VerifyEmail;
use App\Models\Notification;
use App\Models\Token;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    // register 

    public function register(RegisterRequest $request, AuthService $auth)
    {
        try {
            if (! $auth->register($request->validated(), $request->header('X-Role', 'user'))) {
                return $this->dataResponse(null, __('faild to send activation code! please try again'), 422);
            }

            return $this->dataResponse(null, __('registered successfully! activation code has been sent to your phone number'), 200);
        } catch (\Exception $e) {
            return $this->dataResponse(null, $e->getMessage(), 422);
        }
    }

    // enter code to activate account 

    public function verifyUser(CheckCodeRequest $request, AuthService $auth)
    {
        $result = $auth->verifyUser($request->validated());

        if ($result) {
            $user = $result['user'];
            $token = $result['token'];
            if($user->hasRole('driver'))
            {
              $notification = Notification::create([
                'user_id' => $user->id,
                'en'=>['title'=>'A special welcome bonus for you ! ','description'=>'welcome to our application'],
                'ar'=>['title'=>' ! بونص ترحيبي خاص  بك ','description'=>'مرحبا بك في تطبيقنا']
            ]);
            $data =[
              'title'=>$notification->title,
              'body' =>$notification->description
            ];
             $submit_token = Token::where('user_id', $user->id)->first();
             if ($submit_token) {
               $this->notifyByFirebase([$submit_token->token], $data, $submit_token->device_type);
             }
            }
            return $this->dataResponse(['token'=>$token],__('your account is activated successfully!'),200); 
        }
            return $this->dataResponse(null,__('invalid code'),422); 


    }

    // login after after activation

    public function login(LoginRequest $request, AuthService $auth)
    {
        $result = $auth->login($request->validated());

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

        // forget password 

        public function forgetPassword(ForgetPasswordRequest $request, AuthService $auth)
        {
            if ($auth->sendPasswordResetCode($request->validated())) {
                return $this->dataResponse(null, __('we have sent reset password code to you'), 200);
            }

            return $this->dataResponse(null, __('credentials are not correct! please try again'), 422);
        }

        // enter code to reset password
        public function checkResetPasswordCode(CheckResetPasswordCodeRequest $request, AuthService $auth)
        {
            if ($auth->isPasswordResetCodeValid($request->validated())) {
                return $this->dataResponse(null, __('code is valid'), 200);
            }

            return $this->dataResponse(null, __('code is invalid'), 422);

        }
       
        // reset password 

        public function resetPassword(ResetPasswordRequest $request, AuthService $auth)
        {
            if ($auth->resetPassword($request->validated())) {
                return $this->dataResponse(null, __('password is updated successfully'), 200);
            }

            return $this->dataResponse(null, __('reset code is invalid'), 422);

        }

        // logout

        public function logout(Request $request)
        {
          if($request->user()->currentAccessToken()->delete())
          {
            return $this->dataResponse(null,__('logged out successfully'),200);
          }
        }

        public function submitToken(TokenRequest $request)
        {
           $data = $request->validated();
           Token::updateOrCreate(
           ['device_id'     =>$data['device_id']],
           [
             'user_id'      =>$request->user()->id,
             'device_type'  =>$data['device_type'],
             'token'        =>$data['token']
           ]);

           return $this->dataResponse(null,__('token submitted successfully'),200);
        }

        public function updateProfile(UpdateProfileRequest $request , AuthService $auth)
        {
            $data = $request->validated();
            $user = $request->user();
            
            try {
                $auth->updateProfile($user, $data);
            } catch (\Exception $e) {
                return $this->dataResponse(null, __('faild to update profile! please try again'), 422);
            }
            return $this->dataResponse(null, __('profile updated successfully'), 200);
        }

    
}
