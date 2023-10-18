<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\drivers\CheckCodeRequest;
use App\Http\Requests\drivers\CheckResetPasswordCodeRequest;
use App\Http\Requests\drivers\ForgetPasswordRequest;
use App\Http\Requests\drivers\LoginRequest;
use App\Http\Requests\drivers\RegisterRequest;
use App\Http\Requests\drivers\ResetPasswordRequest;
use App\Mail\VerifyEmail;
use App\Models\ActivationProcess;
use App\Models\Provider;
use App\Models\User;
use App\Traits\SendSms;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use SendinBlue\Client\Api\TransactionalSMSApi;
use SendinBlue\Client\Configuration;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    use SendSms;

    // register 

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $password = Hash::make($data['password']);

        DB::beginTransaction();

        $driver = User::create([
             'name'     => $data['name'],
             'password' => $password,
             'address'  => $data['address'],
             'phone'    => $data['phone'],
         ]);
        $role = Role::where(['name'=>'driver','guard_name'=>'api'])->first();
        $driver->assignRole($role);     
        $code = rand(11111,99999);
        $act_process = ActivationProcess::create([
           'code' => $code,
           'status' => 0 ,
           'type'   =>'phone',
           'value'  => $driver->phone,
        ]);

        DB::commit();

        // send sms
        $this->sendSms($driver->phone,$act_process->code);

        $driver->addMedia($data['image'])
              ->toMediaCollection('drivers-images');


       return $this->dataResponse(null,__('registered successfully! activation code has been sent to your phone number'),200);
    }

    // enter code to activate account 

    public function verifyUser(CheckCodeRequest $request)
    {
        $data = $request->validated();
        $code = ActivationProcess::where(['type'=>$data['type'],'value'=>$data['value'],'code'=>$data['code']])->first();
        if($code)
        {
            DB::beginTransaction();
            $driver = User::where($data['type'],$data['value'])->first();
            $data['type'] == 'email' ? $driver->update(['is_active_email'=>1]) : $driver->update(['is_active_phone'=>1]);

            $code->update([
                'status' =>1
            ]);
            ActivationProcess::where(['type'=>$data['type'],'value'=>$data['value'],'status'=>0])->delete();
            DB::commit();
            return $this->dataResponse(null,__('your account is activated successfully!'),200); 
        }
            return $this->dataResponse(null,__('invalid code'),422); 


    }

    // login after after activation

    public function login(LoginRequest $request)
    {
        $data = $request->validated();
        if(Auth::attempt([$data['type'] => $data['value'], 'password' => $data['password']]))
        {
           $driver = $request->user();
           $code = rand(11111,99999);
           $activated = $data['type']=='email'? $driver->is_active_email: $driver->is_active_phone;

              if($activated)
              {
                $token = $driver->createToken("TAWSELA")->plainTextToken;
                return $this->dataResponse(['activation'=>$driver->is_active_phone , 'token'=>$token],__('logged in successfully'),200);
              }

              $act_process = ActivationProcess::create([
                'code' => $code,
                'status' => 0 ,
                'type'   => $data['type'],
                'value'  => $data['value'],
             ]);

             if($data['type'] == 'email')
             {
               Mail::to($driver->email)
              ->bcc("basmaelazony@gmail.com")
              ->send(new VerifyEmail($code)); 
             }
             else
             {
              $this->sendSms($driver->phone,$act_process->code);
             }
           return $this->dataResponse(['activation'=>0], __('your account has not activated yet, activation code has been sent to your phone!'),422);
         }
           return $this->dataResponse(null,__('faild to login! phone or password does not meet our credentials'),422);
        }

        // forget password 

        public function forgetPassword(ForgetPasswordRequest $request)
        {
            $data = $request->validated();
            $driver = User::where($data['type'],$data['value'])->first();
            if($driver)
            {
                $token = rand(11111,99999);
                DB::table('password_reset_tokens')->insert(['value'=>$data['value'],'token'=>$token]);
                if($data['type'] == 'email')
                {
                  Mail::to($driver->email)
                  ->bcc("basmaelazony@gmail.com")
                  ->send(new VerifyEmail($token));     
                }
                else
                {
                  $this->sendSms($driver->phone ,$token);
                }
                return $this->dataResponse(null,__('we have sent reset password code to you'),200);
            }
                return $this->dataResponse(null,__('credentials are not correct! please try again'),422);
        }

        // enter code to reset password
        public function checkResetPasswordCode(CheckResetPasswordCodeRequest $request)
        {
            $data = $request->validated();
            $code = DB::table('password_reset_tokens')->where(['value'=>$data['value'],'token'=>$data['code']])->first();
            if($code){
             return $this->dataResponse(null,__('code is valid'),200);
            }
             return $this->dataResponse(null,__('code is invalid'),422);

        }
       
        // reset password 

        public function resetPassword(ResetPasswordRequest $request)
        {
            $data = $request->validated();
            $driver = User::where($data['type'],$data['value'])->first();
            if($driver){
                $password = Hash::make($data['password']);
                $driver->update([
                  'password' => $password
                ]);

              DB::table('password_reset_tokens')->where('value',$data['value'])->delete();
                
             return $this->dataResponse(null,__('password is updated successfully'),200);
            }
             return $this->dataResponse(null,__('credentials are not correct please try again'),422);

        }

        // logout

        public function logout(Request $request)
        {
          if($request->user()->currentAccessToken()->delete())
          {
            return $this->dataResponse(null,__('logged out successfully'),200);
          }
        }
      
        // sign up with facebook or google

        public function redirectToProvider($provider)
        {
            $validated = $this->validateProvider($provider);
            if (!is_null($validated)) {
                return $validated;
            }
    
            return Socialite::driver($provider)->stateless()->redirect();    
        }

        // redirect to google or facebook callback url 

        public function handleProviderCallback($provider)
        {
            $validated = $this->validateProvider($provider);
            if (!is_null($validated)) {
                return $validated;
            }
            try {
                $social_user = Socialite::driver($provider)->stateless()->user();
            } catch (\Exception $e) {
                return $this->dataResponse(null,$e->getMessage(), 422);
            }
                $user_created = User::updateOrCreate([
                      'provider_id'=>$social_user->getId(),
                      'provider'   =>$provider
                ],[  
                      'email'  =>$social_user->getEmail(),
                      'address'=>'',
                      'phone'  =>'',
                      'is_active_phone'=>0,
                      'is_active_email'=>1,
                      'name'  => $social_user->getName()
                  ]);                
                $role = Role::where(['name'=>'driver','guard_name'=>'api'])->first();
                $user_created->assignRole($role);             
                $user_created->clearMediaCollection('drivers-images');
                $user_created->addMediaFromUrl($social_user->getAvatar()) 
                ->preservingOriginal() //middle method
                ->toMediaCollection('drivers-images');
                $token = $user_created->createToken('Tawsela')->plainTextToken;
                return $this->dataResponse(['token'=>$token],__('logged in successfully'),200);

          }    

        public function validateProvider($provider)
        {
          if(!in_array($provider,['facebook','google']))
          {
            return $this->dataResponse(null,__('Please login using facebook, github or google'),422);
          }
        }


}
