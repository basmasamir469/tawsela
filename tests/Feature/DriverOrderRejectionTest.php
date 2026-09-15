<?php

namespace Tests\Feature;

use App\Enums\DriverAvailabilityStatus;
use App\Models\CarType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DriverOrderRejectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_driver_can_reject_a_pending_order_and_remains_available(): void
    {
        $driver = $this->driver();
        $order = $this->order();

        Sanctum::actingAs($driver);

        $this->postJson("/api/driver/orders/{$order->id}/reject", ['reason' => 'Too far away'])
            ->assertCreated()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('order_driver_rejections', [
            'order_id' => $order->id,
            'driver_id' => $driver->id,
            'reason' => 'Too far away',
        ]);
        $this->assertSame(DriverAvailabilityStatus::AVAILABLE, $driver->fresh()->availability_status);
    }

    public function test_an_unauthenticated_user_cannot_reject_an_order(): void
    {
        $order = $this->order();

        $this->postJson("/api/driver/orders/{$order->id}/reject")
            ->assertUnauthorized();
    }

    public function test_a_driver_can_reject_a_pending_order_without_an_offer_record(): void
    {
        $driver = $this->driver();
        $order = $this->order();

        Sanctum::actingAs($driver);

        $this->postJson("/api/driver/orders/{$order->id}/reject")
            ->assertCreated();

        $this->assertDatabaseHas('order_driver_rejections', [
            'order_id' => $order->id,
            'driver_id' => $driver->id,
        ]);
    }

    public function test_a_driver_cannot_reject_the_same_order_twice(): void
    {
        $driver = $this->driver();
        $order = $this->order();
        Sanctum::actingAs($driver);

        $this->postJson("/api/driver/orders/{$order->id}/reject")->assertCreated();
        $this->postJson("/api/driver/orders/{$order->id}/reject")->assertUnprocessable();

        $this->assertDatabaseCount('order_driver_rejections', 1);
    }

    public function test_a_rejected_order_is_excluded_from_that_drivers_pending_orders(): void
    {
        $driver = $this->driver();
        $carType = CarType::create();
        $driver->vehicleDoc()->create([
            'car_type_id' => $carType->id,
            'car_brand_id' => 1,
            'car_color' => 1,
            'metal_plate_numbers' => 'ABC-123',
            'model_year' => '2026',
        ]);
        $order = $this->order(['car_type_id' => $carType->id]);
        $order->orderDetails()->create([
            'start_latitude' => 30.0444,
            'start_longitude' => 31.2357,
            'end_latitude' => 30.0444,
            'end_longitude' => 31.2357,
        ]);
        $driver->pickers()->create([
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);
        $order->driverRejections()->create(['driver_id' => $driver->id]);

        $this->assertFalse($driver->pendingOrders()->pluck('orders.id')->contains($order->id));
    }

    public function test_busy_drivers_are_excluded_from_the_offer_eligible_scope(): void
    {
        $availableDriver = $this->driver();
        $busyDriver = $this->driver(['availability_status' => DriverAvailabilityStatus::BUSY]);

        $driverIds = User::query()->availableForOffers()->pluck('id');

        $this->assertTrue($driverIds->contains($availableDriver->id));
        $this->assertFalse($driverIds->contains($busyDriver->id));
    }

    public function test_driver_becomes_available_when_their_active_order_is_completed(): void
    {
        $driver = $this->driver(['availability_status' => DriverAvailabilityStatus::BUSY]);
        $order = $this->order([
            'driver_id' => $driver->id,
            'order_status' => Order::FINISHED,
        ]);
        Sanctum::actingAs($driver);

        $this->getJson("/api/v1/complete-drive/{$order->id}")->assertOk();

        $this->assertSame(DriverAvailabilityStatus::AVAILABLE, $driver->fresh()->availability_status);
    }

    private function driver(array $attributes = []): User
    {
        $driver = User::create(array_merge([
            'name' => 'Driver '.uniqid(),
            'email' => uniqid('driver', true).'@example.test',
            'password' => 'password',
            'address' => 'Test address',
            'phone' => uniqid('1'),
            'active_status' => 1,
            'account_status' => 1,
            'availability_status' => DriverAvailabilityStatus::AVAILABLE,
        ], $attributes));

        Role::findOrCreate('driver', 'api');
        $driver->assignRole('driver');

        return $driver;
    }

    private function order(array $attributes = []): Order
    {
        $rider = User::create([
            'name' => 'Rider '.uniqid(),
            'email' => uniqid('rider', true).'@example.test',
            'password' => 'password',
            'address' => 'Test address',
            'phone' => uniqid('2'),
            'active_status' => 1,
            'account_status' => 1,
        ]);

        return Order::create(array_merge([
            'user_id' => $rider->id,
            'order_status' => Order::PENDING,
            'payment_way' => 'cash',
        ], $attributes));
    }
}
