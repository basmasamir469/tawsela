<?php

namespace Tests\Feature;

use App\Models\CarType;
use App\Models\Order;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

    private const ORDER_ENDPOINT = '/api/v1/make-order';

    private const ORDER_DATA = [
        'price' => 1000,
        'start_address' => 'Mansoura',
        'start_latitude' => 31.0409,
        'start_longitude' => 31.3785,
        'end_address' => 'Cairo',
        'end_latitude' => 30.0444,
        'end_longitude' => 31.2357,
        'drive_distance' => 120,
    ];

    public function test_make_order_without_promotion_code(): void
    {
        $user = $this->user();
        $carType = $this->carType();
        $this->createVatSetting();

        $response = $this->makeOrder($user, $carType);

        $response->assertOk()
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
        $this->assertDatabaseHas('order_details', array_merge(['order_id' => $orderId], $this->orderDetails()));
    }

    public function test_make_order_with_promotion_code(): void
    {
        $user = $this->user();
        $promotion = $this->promotion();
        $user->promotions()->attach($promotion);
        $carType = $this->carType();
        $this->createVatSetting();

        $response = $this->makeOrder($user, $carType, ['promo_code' => $promotion->code]);

        $response->assertOk()
            ->assertJson([
                'status' => true,
                'message' => 'order is sent successfully',
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $response->json('data.id'),
            'user_id' => $user->id,
            'price' => 1000,
            'price_after_discount' => 500,
            'promotion_id' => $promotion->id,
        ]);
    }

    public function test_make_order_with_invalid_promotion_code(): void
    {
        $user = $this->user();
        $promotion = $this->promotion();
        $user->promotions()->attach($promotion);
        $carType = $this->carType();
        $this->createVatSetting();

        $this->makeOrder($user, $carType, ['promo_code' => '2346'])
            ->assertUnprocessable()
            ->assertJson([
                'status' => false,
                'message' => 'this promo code is invalid',
            ]);
    }

    public function test_make_order_with_expired_promotion_code(): void
    {
        $user = $this->user();
        $promotion = $this->promotion(['expire_date' => now()->subDay()->toDateString()]);
        $user->promotions()->attach($promotion);
        $carType = $this->carType();
        $this->createVatSetting();

        $this->makeOrder($user, $carType, ['promo_code' => $promotion->code])
            ->assertUnprocessable()
            ->assertJson([
                'status' => false,
                'message' => 'this promo code is invalid',
            ]);
    }

    public function test_make_order_with_used_promotion_code(): void
    {
        $user = $this->user();
        $promotion = $this->promotion();
        $user->promotions()->attach($promotion);
        $carType = $this->carType();
        $vat = $this->createVatSetting();

        Order::create([
            'car_type_id' => $carType->id,
            'user_id' => $user->id,
            'order_status' => Order::COMPLETED,
            'promotion_id' => $promotion->id,
            'price' => 1000,
            'price_after_discount' => 500,
            'vat' => $vat->value['en'],
            'payment_way' => 'cash',
        ]);

        $this->makeOrder($user, $carType, ['promo_code' => $promotion->code])
            ->assertUnprocessable()
            ->assertJson([
                'status' => false,
                'message' => 'you have already used this promo code',
            ]);
    }

    private function user(): User
    {
        $user = User::factory()->create();
        Role::findOrCreate('user', 'api');
        $user->assignRole('user');

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

    private function createVatSetting(): Setting
    {
        return Setting::create([
            'key' => 'vat',
            'value' => ['en' => 0.15, 'ar' => 0.15],
        ]);
    }

    private function promotion(array $attributes = []): Promotion
    {
        return Promotion::create(array_merge([
            'code' => '2345',
            'expire_date' => now()->addYear()->toDateString(),
            'discount' => 50,
        ], $attributes));
    }

    private function makeOrder(User $user, CarType $carType, array $overrides = [])
    {
        Redis::shouldReceive('geosearch')->andReturn([]);

        return $this->actingAs($user, 'sanctum')->postJson(
            self::ORDER_ENDPOINT,
            array_merge(self::ORDER_DATA, ['car_type_id' => $carType->id], $overrides),
        );
    }

    private function orderDetails(): array
    {
        return array_intersect_key(
            self::ORDER_DATA,
            array_flip([
                'start_address',
                'start_latitude',
                'start_longitude',
                'end_address',
                'end_latitude',
                'end_longitude',
            ]),
        );
    }

    public function test_make_order_wit_propmotion_not_attached_to_user(): void
    {
        $user = $this->user();
        $promotion = $this->promotion();
        $carType = $this->carType();
        $this->createVatSetting();

        $response = $this->makeOrder($user, $carType, ['promo_code' => $promotion->code]);

        $response->assertUnprocessable()
            ->assertJson([
                'status' => false,
                'message' => 'this promo code is invalid',
            ]);
    }

    public function test_make_order_with_unauthenticated_user(): void
    {
        $carType = $this->carType();
        $this->createVatSetting();

        $response = $this->postJson(
            self::ORDER_ENDPOINT,
            array_merge(self::ORDER_DATA, ['car_type_id' => $carType->id]),
        );

        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

}
