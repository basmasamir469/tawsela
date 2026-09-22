<?php

namespace App\Factories;

use App\Models\User;
use App\Services\Auth\VerificationTypes\EmailVerificationType;
use App\Services\Auth\VerificationTypes\SmsVerificationType;

class VerificationCodeDestinationFactory
{

     public static function make(string $type , string $code , User $user)
     {
        return match ($type){

           'email' => new EmailVerificationType($code , $user),
           'sms' => new SmsVerificationType($code , $user)

        };
     }
}
