<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Jobs\SendOrderNotification as SendOrderNotificationJob;

class SendOrderNotification 
{

    public function handle(OrderCreated $event): void
    {
        dispatch((new SendOrderNotificationJob($event->order, $event->driverIds))->afterCommit());
    }
}
