<?php

namespace App\Providers;

use App\Events\OrderCreated;
use App\Events\UserVerified;
use App\Listeners\SendDriverWelcomeNotification;
use App\Listeners\SendOrderNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserVerified::class => [
            SendDriverWelcomeNotification::class,
        ],
        OrderCreated::class => [
            SendOrderNotification::class,
        ],
    ];
}
