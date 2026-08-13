<?php

namespace App\Services\Address;

use App\Models\Address;
use App\Models\User;
use App\Transformers\AddressTransformer;
use Illuminate\Support\Collection;

class AddressService
{
    public function listForUser(User $user): array
    {
        return fractal()
            ->collection($user->addresses)
            ->transformWith(new AddressTransformer())
            ->toArray();
    }

    public function store(User $user, array $data): void
    {
        if ($this->isHomeOrWork($data['type']) && $user->addresses()->where('type', $data['type'])->exists()) {
            throw new \RuntimeException(__('failed to save addresss work && home address already existed'));
        }

        $user->addresses()->create([
            'type' => $data['type'],
            'name' => $data['name'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
        ]);
    }

    public function update(User $user, int $addressId, array $data): void
    {
        $user->addresses()
            ->where('id', $addressId)
            ->where('type', $data['type'])
            ->update([
                'name' => $data['name'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
            ]);
    }

    private function isHomeOrWork(int $type): bool
    {
        return $type === Address::HOME || $type === Address::WORK;
    }
}
