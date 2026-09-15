<?php

namespace App\Services\Order;

use App\Events\OrderCreated;
use App\Models\CarType;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Token;
use App\Models\User;
use App\Enums\DriverAvailabilityStatus;
use App\Traits\SendNotification;
use App\Transformers\CarTypeTransformer;
use App\Transformers\OrderTransformer;
use Carbon\Carbon;
use Closure;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class OrderService
{

    use SendNotification;
    public function listOrders(User $user, int $skip = 0, int $take = 10): array
    {
        $orders = $user->userOrders()
            ->filterByStatus()
            ->skip($skip)
            ->take($take)
            ->get();

        return fractal()
            ->collection($orders)
            ->transformWith(new OrderTransformer('index'))
            ->toArray();
    }

    public function driveVehicles(): array
    {
        $vehicles = CarType::all();

        return fractal()
            ->collection($vehicles)
            ->transformWith(new CarTypeTransformer('drive_vehicles'))
            ->toArray();
    }

    public function makeOrder(User $user, array $data): Order
    {
        $priceAfterDiscount = 0;
        $promotion = null;

        if (! empty($data['promo_code'])) {
            [$promotion , $priceAfterDiscount] = $this->applyPromotion($data , $user);
        }

        $pipeline = app(Pipeline::class);

        $result = DB::transaction(function () use ($pipeline, $user, $data, $promotion, $priceAfterDiscount) {
            return $pipeline
                ->send([
                    'user' => $user,
                    'data' => $data,
                    'promotion' => $promotion,
                    'price_after_discount' => $priceAfterDiscount,
                ])
                ->through([
                    function (array $payload, Closure $next) {
                        $payload['setting'] = Setting::where('key', 'vat')->first();

                        return $next($payload);
                    },
                    function (array $payload, Closure $next) {
                        $user = $payload['user'];
                        $data = $payload['data'];
                        $promotion = $payload['promotion'];
                        $priceAfterDiscount = $payload['price_after_discount'];
                        $setting = $payload['setting'];

                        $order = $this->createOrder($data, $user, $promotion, $priceAfterDiscount, $setting);

                        $order->drive_distance = $data['drive_distance'] ?? $order->calculateDriveDistance(
                            $data['start_longitude'],
                            $data['end_longitude'],
                            $data['start_latitude'],
                            $data['end_latitude']
                        );
                        $order->save();

                        $payload['order'] = $order;
                        $payload['driver_ids'] = $this->getNearestDriverIds($order, $data['start_latitude'], $data['start_longitude']);
                        $order->driverOffers()->createMany(array_map(
                            fn ($driverId) => ['driver_id' => $driverId],
                            $payload['driver_ids']
                        ));

                        return $next($payload);
                    },
                ])
                ->then(function (array $payload) {
                    return $payload;
                });
        });

        event(new OrderCreated($result['order'], $result['driver_ids']));

        return $result['order'];
    }

    private function applyPromotion(array $data , User $user): array
    {
        $promotion = $user->promotions()->where('code', $data['promo_code'])
                ->where('expire_date', '>', Carbon::now())
                ->first();

            if (! $promotion) {
                throw new \RuntimeException(__('this promo code is invalid'));
            }

            if ($user->userOrders()->where(['promotion_id' => $promotion->id, 'order_status' => Order::COMPLETED])->exists()) {
                   throw new \RuntimeException(__('you have already used this promo code'));
            }

            if ($promotion->orders()->where('order_status', Order::COMPLETED)->count() >= 10) {
                    throw new \RuntimeException(__('this promo code usage limit has been reached'));
            }

            $priceAfterDiscount = $data['price'] - (($promotion->discount * $data['price']) / 100);

            return [$promotion , $priceAfterDiscount];
    }

     private function createOrder($data, $user, $promotion, $priceAfterDiscount, $setting): Order
     {
          $order = Order::create([
            'car_type_id' => $data['car_type_id'],
            'user_id' => $user->id,
            'order_status' => Order::PENDING,
            'promotion_id' => $promotion?->id,
            'price' => $data['price'],
            'price_after_discount' => $priceAfterDiscount,
            'vat' => $setting->value['en'],
            'payment_way' => 'cash',
        ]);

         $order?->orderDetails()->create([
            'start_address' => $data['start_address'],
            'start_latitude' => $data['start_latitude'],
            'start_longitude' => $data['start_longitude'],
            'end_address' => $data['end_address'],
            'end_latitude' => $data['end_latitude'],
            'end_longitude' => $data['end_longitude'],
        ]);

        return $order;
    }

    protected function getNearestDriverIds(Order $order, float $startAddressLat, float $startAddressLong): array
    {
        $nearbyDriverIds = Redis::geosearch('drivers:locations', 'FROMLONLAT', $startAddressLong, $startAddressLat ,'BYRADIUS', 100, 'km', 'ASC');

        $activeDriverIds = array_filter($nearbyDriverIds, function ($driverId) {
        return Redis::exists("driver:last_seen:{$driverId}");
        });

        return User::join('vehicle_docs', function ($join) use ($order) {
            return $join->on('vehicle_docs.driver_id', '=', 'users.id')
                ->where('vehicle_docs.car_type_id', $order->car_type_id);
        })->whereIn('users.id', $activeDriverIds)
            ->where('users.active_status', 1)
            ->where('users.account_status', 1)
            ->availableForOffers()
            ->pluck('users.id')
            ->toArray();
    }

    public function cancelOrder(User $user, int $orderId, array $data): bool
    {
        $order = Order::find($orderId);

        if (! $order || $order->order_status !== Order::ACCEPTED) {
            return false;
        }

        $order->update([
            'order_status' => Order::CANCELLED,
            'cancel_reason' => $data['cancel_reason'],
        ]);
        $order->driver?->update(['availability_status' => DriverAvailabilityStatus::AVAILABLE]);

        $notification = Notification::create([
            'ar' => ['title' => 'تم إلغاء الطلب', 'description' => 'نأسف لإبلاغك أن الطلب تم إلغاؤه'],
            'en' => ['title' => 'order is cancelled', 'description' => 'we are sorry to inform you that order is cancelled'],
        ]);

        $notification->users()->attach($order->driver_id);

        $token = Token::where('user_id', $order->driver_id)->first();

        if ($token) {
            $payload = [
                'title' => $notification->title,
                'body' => $notification->description,
                'action_id' => $order->id,
                'action_type' => 'cancel-order',
            ];

            $this->notifyByFirebase([$token->device_id], $payload, $token->device_type);
        }

        return true;
    }

    public function showOrder(int $orderId): array
    {
        $order = Order::findOrFail($orderId);

        return fractal($order, new OrderTransformer('drive_details'))->toArray();
    }

   
}
