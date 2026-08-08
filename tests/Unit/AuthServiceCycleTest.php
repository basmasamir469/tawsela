<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\VerificationChallenge;
use App\Services\Auth\AuthService;
use App\Services\Auth\VerificationChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthServiceCycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_verify_and_login_cycle(): void
    {
        Storage::fake('local');

        Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $service = app (AuthService::class);

        $payload = [
            'name' => 'Ahmed Ali',
            'password' => 'secret123',
            'address' => 'Riyadh',
            'phone' => '966500000001',
            'device_id' => 'device-001',
            'device_type' => 'android',
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ];

        $this->assertTrue($service->register($payload, 'user'));

        $user = User::where('phone', $payload['phone'])->first();
        $this->assertNotNull($user);

        $challenge = VerificationChallenge::where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($challenge);

        $result = $service->verifyUser([
            'type' => 'phone',
            'value' => $payload['phone'],
            'code' => '123456',
        ]);

        $this->assertNotNull($result);
        $this->assertTrue($result['user']->is_active_phone);
        $this->assertNotEmpty($result['token']);

        $loginResult = $service->login([
            'type' => 'phone',
            'value' => $payload['phone'],
            'password' => $payload['password'],
            'device_id' => $payload['device_id'],
            'device_type' => $payload['device_type'],
        ]);

        $this->assertNotNull($loginResult);
        $this->assertTrue($loginResult['activated']);
        $this->assertNotEmpty($loginResult['token']);
    }
}