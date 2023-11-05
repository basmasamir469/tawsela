<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class CancelOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cancel-order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "if order's created-at exceeds 5 minutes it is cancelled ";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Order::where('order_status',Order::PENDING)
              ->whereRaw("TIMESTAMPDIFF(MINUTE,created_at, NOW())>5")
              ->update([
                'order_status' => Order::CANCELLED
              ]);
    }
}
