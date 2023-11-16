<?php

namespace App\Http\Controllers;

use App\Models\Picker;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class SocialController extends Controller
{
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

        public function handleProviderCallback(Request $request,$provider)
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
                $header = $request->header('X-Role', 'user'); 
                if($header == 'user')
                {
                  $user_created->clearMediaCollection('users-images');
                  $user_created->addMediaFromUrl($social_user->getAvatar()) 
                  ->preservingOriginal() //middle method
                  ->toMediaCollection('users-images');  
                 $role = Role::where(['name'=>'user','guard_name'=>'api'])->first();
                }
                else{
                  $user_created->clearMediaCollection('drivers-images');
                  $user_created->addMediaFromUrl($social_user->getAvatar()) 
                  ->preservingOriginal() //middle method
                  ->toMediaCollection('drivers-images');  
                 $role = Role::where(['name'=>'driver','guard_name'=>'api'])->first();
                }                   
                $user_created->assignRole($role);             
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
        
        public function home()
        {
          $pickers = Picker::all();
          return view('welcome',compact('pickers'));
        }
}
