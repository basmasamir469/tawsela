<?php

namespace Tests\Feature;

use App\Models\CarType;
use App\Models\Order;
use App\Models\Promotion;
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
    $orderId = $response->json('data.id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'user_id' => $user->id,
            'car_type_id' => $carType->id,
            'order_status' => Order::PENDING,
            'price' => 1000,
            'price_after_discount' => 0,
            'promotion_id' => null,
            'drive_distance' => 120,
         ]);

         $this->assertDatabaseHas('order_details', [
            'order_id' => $orderId,
            'start_address' => 'Mansoura',
            'start_latitude' => 31.0409,
            'start_longitude' => 31.3785,
            'end_address' => 'Cairo',
            'end_latitude' => 30.0444,
            'end_longitude' => 31.2357,
         ]);
    }

    public function test_make_order_with_promotion_code():void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            ]);
        $role = Role::create(['name' => 'user' , 'guard_name' => 'api']);

        $promotion = Promotion::create([
            "code" => "2345",
            "expire_date" => "2026-11-11",
            "discount"=>50
        ]);

        $user->promotions()->attach($promotion->id);

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
                'promo_code' => 2345,
    ]);

    $response->assertStatus(200)
             ->assertJson([
                'status' => true,
                'message' => 'order is sent successfully',
             ]);
    $orderId = $response->json('data.id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'user_id' => $user->id,
            'price' => 1000,
            'price_after_discount' => 500,
            'promotion_id' => $promotion->id,
         ]);

    }

    public function test_make_order_with_invalid_promotion_code():void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $role = Role::create(['name' => 'user' , 'guard_name' => 'api']);
        $user->assignRole($role);

         $promotion = Promotion::create([
            "code" => "2345",
            "expire_date" => "2026-11-11",
            "discount"=>50
        ]);

        $user->promotions()->attach($promotion->id);

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
                'promo_code' => 2346, // Invalid promo code
    ]);

    $response->assertStatus(422)
             ->assertJson([
                'status' => false,
                'message' => 'this promo code is invalid',
             ]);

    }


    public function test_make_order_with_expired_promotion_code():void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $role = Role::create(['name' => 'user' , 'guard_name' => 'api']);
        $user->assignRole($role);

        $promotion = Promotion::create([
            "code" => "2345",
            "expire_date" => "2020-11-11", // Expired date
            "discount"=>50
        ]);

        $user->promotions()->attach($promotion->id);

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
                'promo_code' => 2345, // Expired promo code
    ]);

    $response->assertStatus(422)
             ->assertJson([
                'status' => false,
                'message' => 'this promo code is invalid',
             ]);

    }


    public function test_make_order_with_used_promotion_code():void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $role = Role::create(['name' => 'user' , 'guard_name' => 'api']);
        $user->assignRole($role);

        $promotion = Promotion::create([
            "code" => "2345",
            "expire_date" => "2026-11-11",
            "discount"=>50
        ]);

        $user->promotions()->attach($promotion->id);


        $carType = CarType::create([
            'en'              =>["name"=>"Car"],
            'ar'              =>['name'=>'سيارة'],
            'price_per_meter' => 10.00,
        ]);

        $vat = Setting::create([
            'key' => 'vat',
            'value' => ['en' => 0.15, 'ar' => 0.15],
        ]);

        $order = Order::create([
            'car_type_id' => $carType->id,
            'user_id' => $user->id,
            'order_status' => Order::COMPLETED,
            'promotion_id' => $promotion->id,
            'price' => 1000,
            'price_after_discount' => 500,
            'vat' => $vat->value['en'],
            'payment_way' => 'cash',
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
                'promo_code' => 2345, 
    ]);

    $response->assertStatus(422)
             ->assertJson([
                'status' => false,
                'message' => 'you have already used this promo code',
             ]);

    }


}
