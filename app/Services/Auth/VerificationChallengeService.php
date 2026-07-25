<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\VerificationChallenge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class VerificationChallengeService
{
    public function issue(User $user, string $purpose, string $destination): string
    {
        $code = (string) random_int(100000, 999999);

        DB::transaction(function () use ($user, $purpose, $destination, $code): void {
            VerificationChallenge::query()
                ->where('user_id', $user->id)
                ->where('purpose', $purpose)
                ->where('destination', $destination)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            VerificationChallenge::create([
                'user_id' => $user->id,
                'purpose' => $purpose,
                'destination' => $destination,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(config('auth.verification.code_expiry')),
            ]);
        });

        return $code;
    }

    public function isValid(User $user, string $purpose, string $destination, string $code): bool
    {
        return DB::transaction(function () use ($user, $purpose, $destination, $code): bool {
            $challenge = $this->activeChallenge($user, $purpose, $destination, true);

            if ($challenge === null || $challenge->attempts >= config('auth.verification.max_attempts')) {
                return false;
            }

            if (Hash::check($code, $challenge->code_hash)) {
                return true;
            }

            $attempts = $challenge->attempts + 1;
            $challenge->update([
                'attempts' => $attempts,
                'consumed_at' => $attempts >= config('auth.verification.max_attempts') ? now() : null,
            ]);

            return false;
        });
    }

    public function verifyAndConsume(User $user, string $purpose, string $destination, string $code): bool
    {
        return DB::transaction(function () use ($user, $purpose, $destination, $code): bool {
            $challenge = $this->activeChallenge($user, $purpose, $destination, true);

            if ($challenge === null) {
                return false;
            }

            $maxAttempts = config('auth.verification.max_attempts');

            if ($challenge->attempts >= $maxAttempts) {
                $challenge->update(['consumed_at' => now()]);

                return false;
            }

            if (Hash::check($code, $challenge->code_hash)) {
                $challenge->update(['consumed_at' => now()]);

                return true;
            }

            $attempts = $challenge->attempts + 1;
            $challenge->update([
                'attempts' => $attempts,
                'consumed_at' => $attempts >= $maxAttempts ? now() : null,
            ]);

            return false;
        });
    }

    private function activeChallenge(User $user, string $purpose, string $destination, bool $forUpdate = false): ?VerificationChallenge
    {
        $query = VerificationChallenge::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->where('destination', $destination)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id');

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }
}
