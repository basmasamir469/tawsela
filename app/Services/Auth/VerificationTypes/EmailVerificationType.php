<?php

namespace App\Services\Auth\VerificationTypes;

use App\Jobs\SendVerificationEmail;
use App\Models\User;
use App\Services\Auth\Contracts\VerificationTypeInterface;

class EmailVerificationType implements VerificationTypeInterface
{
    public function send(string $code,  User $user): void
    {
        SendVerificationEmail::dispatch($user->email, $code)->afterCommit();
    }
}
