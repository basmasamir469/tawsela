<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Token;
use App\Models\User;
use App\Traits\SendNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class LicenseAlert extends Command
{
    use SendNotification;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:license-alert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send notification alert to drivers before license date expires';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $drivers_ids = User::whereHas('vehicleDoc',function($q){
            return $q->whereRaw("DATEDIFF(license_expire_date,Now())<3");
        })->pluck('id')->toArray();
        $notification = Notification::create([
          'en' =>['title'=>'date of your vehicle license is about to expire','description'=>'please you should renew your license and send license-image again'],
          'ar' =>['title'=>'اوشك تاريخ رخصتك علي الانتهاء','description'=>'يرجي تجديد رخصتك واعادة ارسال الصورة']
        ]);
        $notification->drivers()->attach($drivers_ids);
        $android_tokens  = Token::whereIn('user_id',$drivers_ids)->where('device_type','android')->pluck('token')->toArray();
        $ios_tokens  = Token::whereIn('user_id',$drivers_ids)->where('device_type','ios')->pluck('token')->toArray();
        $data = [
           'title'        => $notification->title,
           'body'         => $notification->description,
           'action_type'  => 'license-expire'
        ];
        if(count($android_tokens) > 0)
        {
           $this->notifyByFirebase($android_tokens,$data,'android');
        }

        if(count($ios_tokens) > 0)
        {
           $this->notifyByFirebase($ios_tokens,$data,'ios');
        }

    }
}
