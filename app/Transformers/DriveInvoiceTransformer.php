<?php

namespace App\Transformers;

use App\Models\DriveInvoice;
use App\Models\Order;
use Carbon\Carbon;
use League\Fractal\TransformerAbstract;

class DriveInvoiceTransformer extends TransformerAbstract
{
    private $type;

    public function __construct($type = false)
    {
        $this->type = $type;
    }
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected array $defaultIncludes = [
        //
    ];
    
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected array $availableIncludes = [
        //
    ];
    
    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(DriveInvoice $invoice)
    {

         $finish_time  = Carbon::parse($invoice->order->end_time);
         $start_time   = Carbon::parse($invoice->order->start_time);
         return [
            'id'             => $invoice->id,
            'drive_number'   => $invoice->order_id,
            'price'          => $invoice->price_after_discount?? $invoice->order->price,
            'vat'            => $invoice->vat,
            'waiting_price'  => $invoice->waiting_price,
            'total_cost'     => $invoice->total_cost,
            'distance'       => $invoice->order->drive_distance,
            'payment_way'    => $invoice->order->payment_way,
            'duration'       => str_replace('after', '',$finish_time->diffForHumans($start_time)),
            'driver_id'      => $invoice->driver_id

            ];
    }
}
