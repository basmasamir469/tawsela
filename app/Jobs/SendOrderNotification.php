<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public bool $afterCommit = true;

    public function backoff(): array
    {
        return [10, 30, 60, 120];
    }

    public function __construct(public Order $order, public array $driverIds = [])
    {
    }

    public function handle(): void
    {
        foreach (array_unique($this->driverIds) as $driverId) {
            SendDriverOrderNotification::dispatch($this->order->id, (int) $driverId)->afterCommit();
        }
    }
}
