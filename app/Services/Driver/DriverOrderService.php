<?php

namespace App\Services\Driver;

use App\Models\Order;
use App\Models\OrderDriverRejection;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DriverOrderService
{
    public function rejectOffer(User $driver, Order $order, array $data): OrderDriverRejection
    {
        return DB::transaction(function () use ($driver, $order, $data) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->order_status !== Order::PENDING) {
                throw new AuthorizationException('This order can no longer be rejected.');
            }

            if ($lockedOrder->driverRejections()->where('driver_id', $driver->id)->exists()) {
                throw ValidationException::withMessages([
                    'order' => ['This order has already been rejected by this driver.'],
                ]);
            }

            return $lockedOrder->driverRejections()->create([
                'driver_id' => $driver->id,
                'reason' => $data['reason'] ?? null,
            ]);
        });
    }
}
