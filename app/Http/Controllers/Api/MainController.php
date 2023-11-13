<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Transformers\ProfileTransformer;
use Illuminate\Http\Request;

class MainController extends Controller
{
    public function voiceAlert(Request $request)
    {
       $user =  $request->user();
       $user->update([
             'voice_alert'=> $user->voice_alert? 0 : 1
        ]);
       $message = $user->voice_alert? __('voice alert on'):__('voice alert off');
        return $this->dataResponse(null,$message,200);     
    }
  
    public function activateNotifications(Request $request)
    {
        $user = $request->user();
        $user ->update([
            'notify_status' => $user->notify_status? 0 : 1
        ]);
        $message = $user->notify_status? __('receive notifications on'):__('receive notifications off');
        return $this->dataResponse(null,$message,200);     
    }

    public function profile()
    {
        $profile = auth()->user();
        $profile = fractal($profile,new ProfileTransformer())->toArray();
        return $this->dataResponse($profile,__('my profile'),200);     
    }
    
}
