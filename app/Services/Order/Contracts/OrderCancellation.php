<?php

namespace App\Services\Order\Contracts;

use App\Models\User;

interface OrderCancellation
{
    public function cancelOrder(User $user, int $orderId, array $data): bool;
}
