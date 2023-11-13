<?php

namespace App\Transformers;

use App\Models\Order;
use Carbon\Carbon;
use League\Fractal\TransformerAbstract;

class OrderTransformer extends TransformerAbstract
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
    public function transform(Order $order)
    {
        $array =  [
            'id'             => $order->id,
            'user_name'      => $order->user->name,
            'image'          => $order->user->getFirstMediaUrl('users-images'),
            'start_address'  => $order->orderDetails->start_address,
            'end_address'    => $order->orderDetails->end_address,
            'payment_way'    => $order->payment_way,
            'has_promo_code' => $order->promotion_id? 1 : 0,
            'price'          => $order->price_fter_discount?? $order->price,
            'distance'       => $order->drive_distance
        ];

         if($this->type == "order_details"){
             $array['vat']        = $order->vat .'%';
             $array['notes']      = $order->notes??__('no notes');
         }

        if($this->type == 'drive_details')
        {
          $driver = $order->driver;
          return [
               'drive_id'       => $order->id,
               'driver_name'    => $driver?->name,
               'image'          => $driver?->getFirstMediaUrl('drivers-images'),
               'metal_numbers'  => $driver?->vehicleDoc?->metal_plate_numbers,
               'address'        => $driver?->address,
               'average_rate'   => round($driver?->reviews?->avg('rate'),1),
               'start_address'  => $order->orderDetails?->start_address,
               'end_address'    => $order->orderDetails?->end_address,   
            ];
        }

        if($this->type == 'index')
        {
          return [
            'end_latitude'  => $order->orderDetails->end_latitude,
            'end_longitude' => $order->orderDetails->end_longitude,
            'end_address'    => $order->orderDetails->end_address,   
            'price'          => $order->price_after_discount?? $order->price,
            'created_at'    => Carbon::parse($order->created_at)->format('M d, Y H:i A')
          ];
        }


        return $array;
    }
}
