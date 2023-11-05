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
use App\Models\ActivationProcess;
use App\Models\Notification;
use App\Models\Token;
use App\Models\User;
use App\Traits\SendSms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
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

        $user = User::create([
             'name'     => $data['name'],
             'password' => $password,
             'address'  => $data['address'],
             'phone'    => $data['phone'],
         ]);
         $header = $request->header('X-Role', 'user');

           if($header == 'user')
           {
            $user->addMedia($data['image'])
            ->toMediaCollection('users-images');
            $role = Role::where(['name'=>'user','guard_name'=>'api'])->first();
           }
           else
           {
            $user->addMedia($data['image'])
            ->toMediaCollection('drivers-images');
            $role = Role::where(['name'=>'driver','guard_name'=>'api'])->first();
           }
 
        $user->assignRole($role);     
        $code = rand(11111,99999);
        $act_process = ActivationProcess::create([
           'code' => $code,
           'status' => 0 ,
           'type'   =>'phone',
           'value'  => $user->phone,
        ]);

        DB::commit();

        // send sms
        $this->sendSms($user->phone,$act_process->code);

       return $this->dataResponse(null,__('registered successfully! activation code has been sent to your phone number'),200);
    }

    // enter code to activate account 

    public function verifyUser(CheckCodeRequest $request)
    {
        $data = $request->validated();

        // type for email or mobile 
        $code = ActivationProcess::where(['type'=>$data['type'],'value'=>$data['value'],'code'=>$data['code'],'status'=>0])->first();
        if($code)
        {
            DB::beginTransaction();
            $user = User::where($data['type'],$data['value'])->first();
            $data['type'] == 'email' ? $user->update(['is_active_email'=>1]) : $user->update(['is_active_phone'=>1]);

            $code->update([
                'status' =>1
            ]);
            ActivationProcess::where(['type'=>$data['type'],'value'=>$data['value'],'status'=>0])->delete();
            DB::commit();
            Auth::login($user);
            $auth_user = $request->user();
            $token = $auth_user->createToken("TAWSELA")->plainTextToken;
            if($auth_user->hasRole('driver'))
            {
              $notification = Notification::create([
                'user_id' => $auth_user->id,
                'en'=>['title'=>'A special welcome bonus for you ! ','description'=>'welcome to our application'],
                'ar'=>['title'=>' ! بونص ترحيبي خاص  بك ','description'=>'مرحبا بك في تطبيقنا']
            ]);
            $data =[
              'title'=>$notification->title,
              'body' =>$notification->description
            ];
             $submit_token = Token::where('user_id',auth()->user()->id)->first();
             $this->notifyByFirebase([$submit_token->token],$data,$submit_token->device_type);
            }
            return $this->dataResponse(['token'=>$token],__('your account is activated successfully!'),200); 
        }
            return $this->dataResponse(null,__('invalid code'),422); 


    }

    // login after after activation

    public function login(LoginRequest $request)
    {
        $data = $request->validated();
        if(Auth::attempt([$data['type'] => $data['value'], 'password' => $data['password']]))
        {
           $user = $request->user();
           $activated = $data['type']=='email'? $user->is_active_email: $user->is_active_phone;

              if($activated)
              {
                $token = $user->createToken("TAWSELA")->plainTextToken;
                return $this->dataResponse(['activation'=>$user->is_active_phone , 'token'=>$token],__('logged in successfully'),200);
              }
              $code = rand(11111,99999);
              $act_process = ActivationProcess::create([
                'code' => $code,
                'status' => 0 ,
                'type'   => $data['type'],
                'value'  => $data['value'],
             ]);

             if($data['type'] == 'email')
             {
               Mail::to($user->email)
              ->bcc("basmaelazony@gmail.com")
              ->send(new VerifyEmail($code)); 
             }
             else
             {
              $this->sendSms($user->phone,$act_process->code);
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

        public function updateProfile(UpdateProfileRequest $request)
        {
            $data = $request->validated();
            $user = $request->user();
            $national_number = isset($data['national_number'])? $data['national_number'] : null;
            DB::beginTransaction();
            $user->update([
                'name'            => $data['name'],
                'address'         => $data['address'],
                'phone'           => $data['phone'],
                'national_number' => $national_number  
            ]);
            if($user->hasRole('driver'))
            {
              $vehicle = $user->vehicleDoc();
              $vehicle->update([
                  'car_type_id'         => $data['car_type_id'],
                  'car_brand_id'        => $data['car_brand_id'],
                  'car_color'           => $data['car_color'],
                  'metal_plate_numbers' => $data['metal_plate_numbers'],
                  'model_year'          => $data['model_year'],
                  'license_expire_date' => $data['license_expire_date']
              ]);
              try{
                if($data['image'])
                {
                 $user->clearMediaCollection('drivers-images');
                 $user->addMedia($data['image'])
                 ->toMediaCollection('drivers-images');
                }   
  
                if($data['vehicle_license']){
                     $vehicle->clearMediaCollection('vehicle_licenses');
                     $vehicle->addMedia($data['vehicle_license'])
                     ->toMediaCollection('vehicle_licenses');
                }

                if($data['vehicle_license_behind']){
                  $vehicle->clearMediaCollection('vehicle_licenses_behind');
                  $vehicle->addMedia($data['vehicle_license_behind'])
                  ->toMediaCollection('vehicle_licenses_behind');
                }

                if($data['vehicle_inspection']){
                  $vehicle->clearMediaCollection('vehicle_inspections');
                  $vehicle->addMedia($data['vehicle_inspection'])
                  ->toMediaCollection('vehicle_inspections');
                }

                if($data['nationalId_image']){
                  $vehicle->clearMediaCollection('nationalId_images');
                  $vehicle->addMedia($data['nationalId_image'])
                  ->toMediaCollection('nationalId_images');
                }

                if($data['personal_image']){
                  $vehicle->clearMediaCollection('personal_images');
                  $vehicle->addMedia($data['personal_image'])
                  ->toMediaCollection('personal_images');
                }

                if($data['driving_license']){
                  $vehicle->clearMediaCollection('driving_licenses');
                  $vehicle->addMedia($data['driving_license'])
                  ->toMediaCollection('driving_licenses');
                }

                if($data['drug_analysis']){
                  $vehicle->clearMediaCollection('Drug_analyses');
                  $vehicle->addMedia($data['drug_analysis'])
                  ->toMediaCollection('Drug_analyses');
                }

                if($data['criminal_record']){
                  $vehicle->clearMediaCollection('criminal_records');
                  $vehicle->addMedia($data['criminal_record'])
                  ->toMediaCollection('criminal_records');
                }
              DB::commit();
              }
              catch(\Exception $e)
              {
               DB::rollBack();
               return $this->dataResponse(null,$e->getMessage(),422);
              }

            }
            else{
              if($data['image'])
              {
               $user->clearMediaCollection('users-images');
               $user->addMedia($data['image'])
               ->toMediaCollection('users-images');
              }   
              DB::commit();
            }
            
         return $this->dataResponse(null,__('profile updated successfully'),200);
        }
    
      

}
