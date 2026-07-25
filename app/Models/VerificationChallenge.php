<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationChallenge extends Model
{
    public const PURPOSE_PHONE_VERIFICATION = 'phone_verification';
    public const PURPOSE_EMAIL_VERIFICATION = 'email_verification';
    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    protected $fillable = [
        'user_id',
        'purpose',
        'destination',
        'code_hash',
        'attempts',
        'expires_at',
        'consumed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];
}
