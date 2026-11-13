<?php

namespace App\Services\Driver;

use App\Models\Order;
use App\Transformers\DriveInvoiceTransformer;

class DriveInvoiceService
{
    public function details(int $orderId): array
    {
        $invoice = Order::findOrFail($orderId)->driveInvoice;

        return fractal($invoice, new DriveInvoiceTransformer())->toArray();
    }
}
