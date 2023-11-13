<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\Token;
use App\Traits\SendNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendLicenseAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SendNotification;

    /**
     * Create a new job instance.
     */
    public $drivers_ids;
    public function __construct($drivers)
    {
        $this->drivers_ids = $drivers->pluck('id')->toArray();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $notification = Notification::create([
            'en' =>['title'=>'date of your vehicle license is about to expire','description'=>'please you should renew your license and send license-image again'],
            'ar' =>['title'=>'اوشك تاريخ رخصتك علي الانتهاء','description'=>'يرجي تجديد رخصتك واعادة ارسال الصورة']
          ]);
          $notification->users()->attach($this->drivers_ids);
          $android_tokens  = Token::whereIn('user_id',$this->drivers_ids)->where('device_type','android')->pluck('token')->toArray();
          $ios_tokens  = Token::whereIn('user_id',$this->drivers_ids)->where('device_type','ios')->pluck('token')->toArray();
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
