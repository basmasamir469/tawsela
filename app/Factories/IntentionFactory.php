<?php

namespace App\Factories;

use App\Models\User;
use App\Services\Chat\AiIntentionsTools\OrderStatusStrategy;
use App\Services\Chat\Contracts\IntentionToolInterface;

class IntentionFactory
{

     public static function make(array $data ) :IntentionToolInterface
     {
        return match ($data['intent']){

          "order_status"   => new OrderStatusStrategy($data),
         "driver_location" => new DriverLocationStrategy($data)
         //  "driver_info"
         //  "cancel_order"
         //  "cancellation_policy"
         //  "privacy_policy"
         //  "driver_late"
         //  "general"

        };
     }
}
