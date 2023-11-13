<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use GuzzleHttp\Psr7\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function update(Request $request)
    {
       $settings = $request->settings;
       foreach($settings as $key => $value)
       {
        if ($value instanceOf UploadedFile)
        {   
            $setting = Setting::where('key',$key)->first();
            $setting->clearMediaCollection('settings-images');
            $setting->addMedia($value)
                    ->toMediaCollection('settings-images');
        }
        else{
          Setting::where('key',$key)->update([
            'value'=>$value
          ]);
          Cache::forget('settings');
        }
       }

    }
}
