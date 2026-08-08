<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\VerificationChallenge;
use App\Services\Auth\AuthService;
use App\Services\Auth\VerificationChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForgetAndResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_forget_and_reset_password_flow(): void
    {
        $user = User::firstOrCreate([
            'name' => 'Ahmed Ali',
            'phone' => '966500000001',
            'password' => Hash::make('secret123'),
        ]);

        $this->assertNotNull($user);

        $service = app(AuthService::class);
        $challengeService = app(VerificationChallengeService::class);

        $this->assertTrue($service->sendPasswordResetCode([
            'type' => 'phone',
            'value' => $user->phone,
        ]));

        $code = $challengeService->issue(
            $user,
            VerificationChallenge::PURPOSE_PASSWORD_RESET,
            $user->phone
        );

        $this->assertTrue($service->isPasswordResetCodeValid([
            'type' => 'phone',
            'value' => $user->phone,
            'code' => $code,
        ]));

        $payload = [
            'type' => 'phone',
            'value' => $user->phone,
            'code' => $code,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $this->assertTrue($service->resetPassword($payload));

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }
}