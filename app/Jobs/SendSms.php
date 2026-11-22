<?php

namespace App\Jobs;

use App\Traits\SendSms as SendsSms;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class SendSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SendsSms;

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
        $response = $this->sendSms($this->recipient, $this->code);
        if ($response->getStatus() !== 0) {
            throw new RuntimeException('Vonage rejected the SMS with status '.$response->getStatus().'.');
        }
    }
}
