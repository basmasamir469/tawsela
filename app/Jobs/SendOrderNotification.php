<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\Order;
use App\Models\Token;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use \App\Traits\SendNotification;

    public function __construct(public Order $order, public array $driverIds = [])
    {
    }

    public function handle(): void
    {
        if (empty($this->driverIds)) {
            return;
        }

        $notification = Notification::create([
            'en' => ['title' => 'New Order', 'description' => 'please drive me to this address'],
            'ar' => ['title' => 'طلب جديد', 'description' => 'من فضلك اذهب الى هذا العنوان'],
        ]);

        $notification->users()->attach($this->driverIds);

        $androidTokens = Token::whereIn('user_id', $this->driverIds)
            ->where('device_type', 'android')
            ->pluck('token')
            ->toArray();

        $iosTokens = Token::whereIn('user_id', $this->driverIds)
            ->where('device_type', 'ios')
            ->pluck('token')
            ->toArray();

        if (! empty($androidTokens)) {
            $this->notifyByFirebase($androidTokens, [
                'title' => $notification->title,
                'body' => $notification->description,
                'action_id' => $this->order->id,
                'action_type' => 'new-order',
            ], 'android');
        }

        if (! empty($iosTokens)) {
            $this->notifyByFirebase($iosTokens, [
                'title' => $notification->title,
                'body' => $notification->description,
                'action_id' => $this->order->id,
                'action_type' => 'new-order',
            ], 'ios');
        }
    }
}
