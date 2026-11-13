<?php

namespace App\Services\Auth\VerificationTypes;

use App\Models\User;
use App\Jobs\SendSms as SendSmsJob;
use App\Services\Auth\Contracts\VerificationTypeInterface;

class SmsVerificationType implements VerificationTypeInterface
{
    public function send(string $code, User $user): void
    {
        SendSmsJob::dispatch($user->phone, $code)->afterCommit();
    }
}
