<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserVerified
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user)
    {
    }
}