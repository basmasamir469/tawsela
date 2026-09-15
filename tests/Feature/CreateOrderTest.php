<?php

namespace Tests\Feature;

use App\Models\CarType;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Redis;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateOrderTest extends TestCase
{
    use RefreshDatabase;
    public function test_make_order_without_promotion_code():void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            ]);
        $role = Role::create(['name' => 'user' , 'guard_name' => 'api']);
        $user->assignRole($role);
        $carType = CarType::create([
            'en'              =>["name"=>"Car"],
            'ar'              =>['name'=>'سيارة'],
            'price_per_meter' => 10.00,
        ]);

        $vat = Setting::create([
            'key' => 'vat',
            'value' => ['en' => 0.15, 'ar' => 0.15],
        ]);

        Redis::shouldReceive('geosearch')
        ->andReturn([]);

        $response =$this->actingAs($user, 'sanctum')
             ->postJson('/api/v1/make-order', [
               'car_type_id' => $carType->id,
               'price' => 1000,
               'start_address' => 'Mansoura',
               'start_latitude' => 31.0409,
               'start_longitude' => 31.3785,
               'end_address' => 'Cairo',
               'end_latitude' => 30.0444,
               'end_longitude' => 31.2357,
               'drive_distance' => 120,
                'promo_code' => null,
    ]);

    $response->assertStatus(200)
             ->assertJson([
                'status' => true,
                'message' => 'order is sent successfully',
             ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'car_type_id' => $carType->id,
            'order_status' => Order::PENDING,
            'price' => 1000,
            'price_after_discount' => 0,
            'promotion_id' => null,
            'drive_distance' => 120,
         ]);

         $this->assertDatabaseHas('order_details', [
            'start_address' => 'Mansoura',
            'start_latitude' => 31.0409,
            'start_longitude' => 31.3785,
            'end_address' => 'Cairo',
            'end_latitude' => 30.0444,
            'end_longitude' => 31.2357,
         ]);




    }
}
