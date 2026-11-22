<?php

namespace App\Jobs;

use App\Models\Token;
use App\Traits\SendNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class SendFirebaseNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SendNotification;

    public int $tries = 5;

    public function __construct(
        public int|array $userIds,
        public array $data,
        public ?string $deviceType = null
    ) {
    }

    public function backoff(): array
    {
        return [10, 30, 60, 120];
    }

    public function handle(): void
    {
        $userIds = is_array($this->userIds) ? $this->userIds : [$this->userIds];
        $tokens = Token::whereIn('user_id', $userIds)
            ->when($this->deviceType, fn ($query) => $query->where('device_type', $this->deviceType))
            ->pluck('token')
            ->all();

        if ($tokens === []) {
            return;
        }

        $result = $this->notifyByFirebase($tokens, $this->data, $this->deviceType);
        if (! empty($result['failed'])) {
            throw new RuntimeException('Firebase notification failed for '.count($result['failed']).' device(s).');
        }
    }
}
