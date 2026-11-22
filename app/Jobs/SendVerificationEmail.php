<?php

namespace App\Jobs;

use App\Mail\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendVerificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public string $recipient, public string $code)
    {
    }

    public function backoff(): array
    {
        return [10, 30, 60, 120];
    }

    public function handle(): void
    {
        Mail::to($this->recipient)
            ->bcc('basmaelazony@gmail.com')
            ->send(new VerifyEmail($this->code));
    }
}
