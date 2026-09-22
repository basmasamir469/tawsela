<?php

namespace App\Services\Auth\VerificationTypes;

use App\Mail\VerifyEmail;
use App\Models\User;
use App\Services\Auth\Contracts\VerificationTypeInterface;
use Illuminate\Support\Facades\Mail;

class EmailVerificationType implements VerificationTypeInterface
{
    public function send(string $code,  User $user): void
    {
       
        Mail::to($user->email)
                ->bcc('basmaelazony@gmail.com')
                ->send(new VerifyEmail($code));

    }
}