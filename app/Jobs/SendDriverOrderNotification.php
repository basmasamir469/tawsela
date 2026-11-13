<?php

namespace App\Jobs;

use App\Enums\DriverAvailabilityStatus;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Token;
use App\Models\User;
use App\Traits\SendNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SendDriverOrderNotification implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SendNotification;

    public int $tries = 5;
    public bool $afterCommit = true;
    public int $uniqueFor = 3600;

    public function __construct(public int $orderId, public int $driverId)
    {
    }

    public function uniqueId(): string
    {
        return "order:{$this->orderId}:driver:{$this->driverId}";
    }

    public function backoff(): array
    {
        return [10, 30, 60, 120];
    }

    public function handle(): void
    {
        $eligible = DB::transaction(function () {
            $order = Order::query()->lockForUpdate()->find($this->orderId);
            if (! $order || $order->order_status !== Order::PENDING) {
                return false;
            }

            $driver = User::query()->lockForUpdate()->find($this->driverId);

            return $driver !== null
                && $driver->availability_status === DriverAvailabilityStatus::AVAILABLE
                && (int) $driver->active_status === 1
                && (int) $driver->account_status === 1;
        });

        if (! $eligible) {
            return;
        }

        $notification = Notification::create([
            'en' => ['title' => 'New Order', 'description' => 'please drive me to this address'],
            'ar' => ['title' => 'Ø·Ù„Ø¨ Ø¬Ø¯ÙŠØ¯', 'description' => 'Ù…Ù† ÙØ¶Ù„Ùƒ Ø§Ø°Ù‡Ø¨ Ø§Ù„Ù‰ Ù‡Ø°Ø§ Ø§Ù„Ø¹Ù†ÙˆØ§Ù†'],
        ]);
        $notification->users()->attach($this->driverId);

        $tokens = Token::where('user_id', $this->driverId)->pluck('token')->all();
        if ($tokens === []) {
            return;
        }

        $result = $this->notifyByFirebase($tokens, [
            'title' => $notification->title,
            'body' => $notification->description,
            'action_id' => $this->orderId,
            'action_type' => 'new-order',
        ]);

        if (! empty($result['failed'])) {
            throw new RuntimeException('Failed to notify driver '.$this->driverId.' about order '.$this->orderId.'.');
        }
    }
}
