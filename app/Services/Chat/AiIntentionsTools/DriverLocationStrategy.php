<?php

namespace App\Services\Chat\AiIntentionsTools;

use App\Models\Order;
use App\Services\Chat\Contracts\IntentionToolInterface;
use RuntimeException;

class DriverLocationStrategy implements IntentionToolInterface
{
   
     public function sendResponse(array $data)
     {
            if($data['order_id'] && $data['user_id']){
    
            $order = Order::where('id', $data['order_id'])->where('user_id', $data['user_id'])->first();
    
            if(!$order){
                throw new RuntimeException("Order not found for the given Order ID and User ID.");
            }
    
            return "The driver for your order with ID {$order->id} is currently located at: {$order->driver_location}.";
    
            }else{
                throw new RuntimeException("Order ID and User ID are required to check driver location.");
            }
     }
}