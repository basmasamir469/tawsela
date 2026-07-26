<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class SocialAuthService
{
    public function loginWithProvider(string $provider, string $token, string $role = 'user'): array
    {
        $socialUser = Socialite::driver($provider)->stateless()->userFromToken($token);

        $providerId = $socialUser->getId();
        $providerEmail = $socialUser->getEmail();
        $name = $socialUser->getName() ?? $socialUser->getNickname() ?? $providerEmail;
        $avatar = $socialUser->getAvatar();

        $user = User::where('provider', $provider)
            ->where('provider_id', $providerId)
            ->orWhere(function ($query) use ($providerEmail, $provider) {
                if ($providerEmail) {
                    $query->where('email', $providerEmail)
                        ->whereNull('provider');
                }
            })
            ->first();

        if (! $user) {
            $user = User::create([
                'name' => $name,
                'email' => $providerEmail,
                'phone' => '',
                'address' => '',
                'provider' => $provider,
                'provider_id' => $providerId,
                'is_active_email' => 1,
                'is_active_phone' => 0,
                'password' => Hash::make(bin2hex(random_bytes(16))),
            ]);
        } else {
            if (! $user->provider) {
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $providerId,
                ]);
            }
        }

        $mediaCollection = $role === 'driver' ? 'drivers-images' : 'users-images';

        if ($avatar) {
            $user->clearMediaCollection($mediaCollection);
            try {
                $user->addMediaFromUrl($avatar)
                    ->preservingOriginal()
                    ->toMediaCollection($mediaCollection);
            } catch (\Exception $e) {
                // ignore remote avatar failure
            }
        }

        $roleModel = Role::where(['name' => $role, 'guard_name' => 'api'])->first();
        if ($roleModel && ! $user->hasRole($role)) {
            $user->syncRoles([$roleModel]);
        }

        $token = $user->createToken('Tawsela')->plainTextToken;

        return [
            'token' => $token,
            'user' => $user,
        ];
    }
}
