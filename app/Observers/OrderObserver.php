<?php

namespace App\Observers;

use App\Events\OrderCreated;
use App\Models\Order;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order ): void
    {
        
    }

    /**
     * Handle the order "updated" event.
     */
    public function updated(Order $order): void
    {
        //
    }

    /**
     * Handle the order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
