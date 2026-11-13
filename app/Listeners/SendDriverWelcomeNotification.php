<?php

namespace App\Listeners;

use App\Events\UserVerified;
use App\Models\Notification;
use App\Models\Token;
use App\Traits\SendNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use RuntimeException;

class SendDriverWelcomeNotification implements ShouldQueue
{
    use SendNotification;

    public int $tries = 5;
    public bool $afterCommit = true;

    public function backoff(): array
    {
        return [10, 30, 60, 120];
    }

    public function handle(UserVerified $event): void
    {
        $user = $event->user;

        if (! $user->hasRole('driver')) {
            return;
        }

        $notification = Notification::create([
            'user_id' => $user->id,
            'en' => [
                'title' => 'A special welcome bonus for you ! ',
                'description' => 'welcome to our application',
            ],
            'ar' => [
                'title' => ' ! مرحبا بكم في التطبيق ',
                'description' => 'مرحباً بك في تطبيقنا',
            ],
        ]);

        $data = [
            'title' => $notification->title,
            'body' => $notification->description,
        ];

        foreach (Token::where('user_id', $user->id)->get() as $token) {
            $result = $this->notifyByFirebase([$token->token], $data, $token->device_type);
            if (! empty($result['failed'])) {
                throw new RuntimeException('Failed to send the driver welcome notification.');
            }
        }
    }
}
