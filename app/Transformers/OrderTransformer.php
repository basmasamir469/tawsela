<?php

namespace App\Transformers;

use App\Models\Order;
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
            'start_address'  => $order->orderDetails->start_address,
            'end_address'    => $order->orderDetails->end_address,
            'payment_way'    => $order->payment_way,
            'has_promo_code' => $order->promo_code? 1 : 0,
            'price'          => $order->price,
            'distance'       => $order->distance
        ];

        // if($this->type=="dashboard"){
        //     unset($array['name']);
        //     $array['name_en'] = $type->translate('en')->name;
        //     $array['name_ar'] = $type->translate('ar')->name;
        // }

        return $array;
    }
}
