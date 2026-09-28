<?php

namespace App\Services\Auth\Contracts;

use App\Models\User;

Interface VerificationTypeInterface
{
    public function send(string $code , User $user): void;
}
