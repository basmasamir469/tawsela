<?php

namespace Tests\Feature;

use App\Models\CarType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CancelOrderTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_cancel_order(): void
    {
        $user = $this->user();
        $carType = $this->carType();

        $order = $user->userOrders()->create([
            'car_type_id' => $carType->id,
            'user_id' => $user->id,
            'price' => 1000,
            'price_after_discount' => 500,
            'vat' => 50,
            'payment_way' => 'cash',
            'order_status' => Order::PENDING            
        ]);
        
        $response = $this->actingAs($user,'sanctum')->postJson("/api/v1/users/cancel-order/$order->id",[
            'cancel_reason' => 'Changed my mind',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => Order::CANCELLED,
        ]);
    }

    private function user()
    {
        $user = User::factory()->create();
        Role::findOrCreate('user', 'api');
        $user->assignRole('user');
        return $user;
    }

    private function driver()
    {
        $user = User::factory()->create();
        Role::findOrCreate('driver', 'api');
        $user->assignRole('driver');
        return $user;
    }

     private function carType(): CarType
    {
        return CarType::create([
            'en' => ['name' => 'Car'],
            'ar' => ['name' => 'سيارة'],
            'price_per_meter' => 10.00,
        ]);
    }
}
