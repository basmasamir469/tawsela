<?php

namespace App\Services\Auth\Contracts;

use App\Models\User;

interface VerificationTypeInterface
{
    public function send(string $code , User $user): void;
}
