<?php

namespace App\Services\Order\Decorators;

use App\Models\Notification;
use App\Models\Order;
use App\Jobs\SendFirebaseNotification;
use App\Models\User;
use App\Services\Order\Contracts\OrderCancellation;
use App\Services\Order\OrderService;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderNotificationDecorator implements OrderCancellation
{
    public function __construct(private OrderService $orderCancellation)
    {
    }

    public function cancelOrder(User $user, int $orderId, array $data): bool
    {
        if (! $this->orderCancellation->cancelOrder($user, $orderId, $data)) {
            return false;
        }

        try {
            $order = Order::find($orderId);
            if (! $order || ! $order->driver_id) {
                return true; // No driver to notify
            }

            $notification = Notification::create([
                'ar' => [
                    'title' => 'تم إلغاء الطلب',
                    'description' => 'نأسف لإبلاغك أن الطلب تم إلغاؤه',
                ],
                'en' => [
                    'title' => 'Order cancelled',
                    'description' => 'We are sorry to inform you that the order was cancelled.',
                ],
            ]);

            $notification->users()->attach($order->driver_id);

            SendFirebaseNotification::dispatch($order->driver_id, [
                'title' => $notification->title,
                'body' => $notification->description,
                'action_id' => $order->id,
                'action_type' => 'cancel-order',
            ], 'silent')->afterCommit();
        } catch (Throwable $exception) {
            Log::error('Failed to notify driver about cancelled order.', [
                'order_id' => $orderId,
                'error' => $exception->getMessage(),
            ]);
        }

        return true;
    }
}
