<?php

namespace App\Listeners;

use App\Events\UserVerified;
use App\Models\Notification;
use App\Models\Token;
use App\Traits\SendNotification;

class SendDriverWelcomeNotification
{
    use SendNotification;

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
            $this->notifyByFirebase([$token->device_id], $data, $token->device_type);
        }
    }
}