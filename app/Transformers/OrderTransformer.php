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
            //  $array['total_cost'] = $order->price_after_discount ;
             $array['vat']        = $order->vat .'%';
             $array['notes']      = $order->notes??__('no notes');
         }

        if($this->type == "finished_drive")
        {
         $finish_time  = Carbon::parse($order->end_time);
         $start_time   = Carbon::parse($order->start_time);
         $second_array = [
            'id'             => $order->id,
            'price'          => $order->price_after_discount?? $order->price,
            'vat'            =>($order->total_cost * $order->vat)/100,
            'waiting_price'  => $order->waiting_price,
            'total_cost'     => $order->total_cost,
            'distance'       => $order->drive_distance,
            'payment_way'    => $order->payment_way,
            'duration'       => str_replace('after', '',$finish_time->diffForHumans($start_time))

            ];

        return $second_array;
        }

        return $array;
    }
}
