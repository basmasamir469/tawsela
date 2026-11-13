<?php

namespace App\Services\Driver;

use App\Models\Order;
use App\Models\OrderDriverRejection;
use App\Models\User;
use App\Models\Notification;
use App\Jobs\SendFirebaseNotification;
use App\Enums\DriverAvailabilityStatus;
use App\Transformers\OrderTransformer;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DriverOrderService
{
    public function pendingOrders(User $driver)
    {
        return $driver->pendingOrders();
    }

    public function orderDetails(int $orderId): array
    {
        return fractal(Order::findOrFail($orderId), new OrderTransformer('order_details'))->toArray();
    }

    public function acceptOrder(User $driver, int $orderId): bool|string
    {
        $order = Order::findOrFail($orderId);

        $result = DB::transaction(function () use ($driver, $orderId, $order) {
            $orderUpdated = Order::query()
                ->whereKey($orderId)
                ->where('order_status', Order::PENDING)
                ->update([
                    'order_status' => Order::ACCEPTED,
                    'driver_id' => $driver->id,
                    'updated_at' => now(),
                ]);

            if ($orderUpdated === 0) {
                return false;
            }

            $driverUpdated = User::query()
                ->whereKey($driver->id)
                ->where('availability_status', DriverAvailabilityStatus::AVAILABLE->value)
                ->update([
                    'availability_status' => DriverAvailabilityStatus::BUSY->value,
                    'updated_at' => now(),
                ]);

            if ($driverUpdated === 0) {
                Order::query()
                    ->whereKey($orderId)
                    ->where('order_status', Order::ACCEPTED)
                    ->where('driver_id', $driver->id)
                    ->update([
                        'order_status' => Order::PENDING,
                        'driver_id' => null,
                        'updated_at' => now(),
                    ]);

                return 'unavailable';
            }

            $this->notifyCustomer($order, 'ØªÙ… Ù‚Ø¨ÙˆÙ„ Ø·Ù„Ø¨Ùƒ', 'Ù…Ù† ÙØ¶Ù„Ùƒ Ø§Ù†ØªØ¸Ø± Ø§Ù„Ø³Ø§Ø¦Ù‚ ÙÙŠ Ø§Ù„Ø·Ø±ÙŠÙ‚ Ø§Ù„ÙŠÙƒ', 'your order is accepted', 'please wait the driver is on his way to you ', 'cancel-order');

            return true;
        });

        if ($result === true) {
            $this->sendCustomerPush($order, 'cancel-order');
        }

        return $result;
    }

    public function cancelOrder(int $orderId, array $data): bool
    {
        $order = Order::findOrFail($orderId);

        $order = DB::transaction(function () use ($orderId, $data) {
            $affected = Order::query()
                ->whereKey($orderId)
                ->where('order_status', Order::ACCEPTED)
                ->update([
                    'order_status' => Order::CANCELLED,
                    'cancel_reason' => $data['cancel_reason'],
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                return null;
            }

            $order = Order::findOrFail($orderId);
            $this->makeDriverAvailableIfNoActiveOrders($order->driver_id);
            $this->notifyCustomer($order, ' ØªÙ… Ø§Ù„ØºØ§Ø¡ Ø·Ù„Ø¨Ùƒ', ' Ù†Ø§Ø³Ù Ù„Ø§Ø¨Ù„Ø§ØºÙƒ Ø§Ù†Ù‡ ØªÙ… Ø§Ù„ØºØ§Ø¡ Ø·Ù„Ø¨Ùƒ ', ' your order is cancelled', 'we are sorry to inform you that you order is cancelled for ', 'cancel-order');
            return $order;
        });
        if (! $order) return false;
        $this->sendCustomerPush($order, 'cancel-order');
        return true;
    }

    public function startDrive(int $orderId): bool
    {
        Order::findOrFail($orderId);

        return Order::query()
            ->whereKey($orderId)
            ->where('order_status', Order::ACCEPTED)
            ->update([
                'order_status' => Order::STARTED,
                'start_time' => Carbon::now(),
                'updated_at' => now(),
            ]) === 1;
    }

    public function finishDrive(int $orderId, float $waitingPrice): bool
    {
        $order = Order::findOrFail($orderId);

        return DB::transaction(function () use ($order, $orderId, $waitingPrice) {
            $basePrice = $order->price_after_discount ?: $order->price;
            $vat = (($basePrice + $waitingPrice) * $order->vat) / 100;
            $total = $basePrice + $waitingPrice + $vat;
            $affected = Order::query()
                ->whereKey($orderId)
                ->where('order_status', Order::STARTED)
                ->update([
                    'order_status' => Order::FINISHED,
                    'waiting_price' => $waitingPrice,
                    'total_cost' => $total,
                    'end_time' => Carbon::now(),
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                return false;
            }

            $order->driveInvoice()->create(['price' => $basePrice, 'taxes' => $vat, 'total_cost' => $total, 'waiting_time' => $waitingPrice, 'driver_id' => $order->driver_id, 'user_id' => $order->user_id]);
            User::query()->whereKey($order->driver_id)->increment('debit', $vat, ['updated_at' => now()]);

            return true;
        });
    }

    public function completeDrive(int $orderId): bool
    {
        $order = Order::findOrFail($orderId);

        return DB::transaction(function () use ($orderId, $order) {
            $affected = Order::query()
                ->whereKey($orderId)
                ->where('order_status', Order::FINISHED)
                ->update(['order_status' => Order::COMPLETED, 'updated_at' => now()]);

            if ($affected === 0) {
                return false;
            }

            $this->makeDriverAvailableIfNoActiveOrders($order->driver_id);

            return true;
        });
    }

    public function markArrived(int $orderId): bool
    {
        Order::findOrFail($orderId);

        $order = DB::transaction(function () use ($orderId) {
            $affected = Order::query()
                ->whereKey($orderId)
                ->where('order_status', Order::ACCEPTED)
                ->update(['order_status' => Order::ARRIVED, 'updated_at' => now()]);

            if ($affected === 0) {
                return null;
            }

            $order = Order::findOrFail($orderId);
            $this->notifyCustomer($order, 'ÙˆØµÙ„ Ø§Ù„Ø³Ø§Ø¦Ù‚ Ø§Ù„Ø®Ø§Øµ Ø¨Ùƒ', 'Ù…Ù† ÙØ¶Ù„Ùƒ Ø¹Ù„ÙŠÙƒ Ø§Ù† ØªÙƒÙˆÙ† Ù‡Ù†Ø§Ùƒ ÙÙŠ ØºØ¶ÙˆÙ† Ø®Ù…Ø³ Ø¯Ù‚Ø§Ø¦Ù‚', 'your driver has arrived now', 'please you should be there in five minutes ', 'arrived');
            return $order;
        });
        if (! $order) return false;
        $this->sendCustomerPush($order, 'arrived');
        return true;
    }

    private function makeDriverAvailableIfNoActiveOrders(?int $driverId): void
    {
        if ($driverId === null) {
            return;
        }

        User::query()
            ->whereKey($driverId)
            ->where('availability_status', DriverAvailabilityStatus::BUSY->value)
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('orders')
                    ->whereColumn('orders.driver_id', 'users.id')
                    ->whereIn('orders.order_status', [
                        Order::ACCEPTED,
                        Order::ARRIVED,
                        Order::STARTED,
                        Order::INWAY,
                        Order::FINISHED,
                    ]);
            })
            ->update([
                'availability_status' => DriverAvailabilityStatus::AVAILABLE->value,
                'updated_at' => now(),
            ]);
    }

    private function notifyCustomer(Order $order, string $arTitle, string $arDescription, string $enTitle, string $enDescription, string $action): void
    {
        $notification = Notification::create(['ar' => ['title' => $arTitle, 'description' => $arDescription], 'en' => ['title' => $enTitle, 'description' => $enDescription]]);
        $notification->users()->attach($order->user_id);
        $order->setRelation('lastDriverNotification', $notification);
    }

    private function sendCustomerPush(Order $order, string $action): void
    {
        $notification = $order->lastDriverNotification;
        if ($notification) {
            SendFirebaseNotification::dispatch($order->user_id, [
                'title' => $notification->title,
                'body' => $notification->description,
                'action_id' => $order->id,
                'action_type' => $action,
            ])->afterCommit();
        }
    }
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
