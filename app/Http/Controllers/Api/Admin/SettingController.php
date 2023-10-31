<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use GuzzleHttp\Psr7\UploadedFile;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function update(Request $request)
    {
       $settings = $request->settings;
       foreach($settings as $setting)
       {
        if ($setting['value'] instanceOf UploadedFile)
        {   
            $setting = Setting::where('key',$setting['key'])->first();
            $setting->clearMediaCollection('settings-images');
            $setting->addMedia($setting['value'])
                    ->toMediaCollection('settings-images');
        }
           Setting::where('key',$setting['key'])->update([
             'value'=>$setting['value']
           ]);
       }

    }
}
