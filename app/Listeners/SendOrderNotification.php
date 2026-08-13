<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Jobs\SendOrderNotification as SendOrderNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderNotification 
{

    public function handle(OrderCreated $event): void
    {
        dispatch(new SendOrderNotificationJob($event->order, $event->driverIds));
    }
}
