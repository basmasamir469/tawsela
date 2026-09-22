<?php

namespace App\Services\Auth\VerificationTypes;

use App\Models\User;
use App\Services\Auth\Contracts\VerificationTypeInterface;
use App\Traits\SendSms;

class SmsVerificationType implements VerificationTypeInterface
{
    use SendSms;

    public function send(string $code, User $user): void
    {
       
       $this->sendSms($user->phone, $code);

    }
}