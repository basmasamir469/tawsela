<?php

namespace App\Transformers;

use App\Models\Notification;
use League\Fractal\TransformerAbstract;

class NotificationTransformer extends TransformerAbstract
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
    public function transform(Notification $notification)
    {
        $array = [
            'id'              => $notification->id,
            'title'           => $notification->title,
            'description'     => $notification->description,
        ];

        if($this->type == 'user_notifications')
        {
            if(!$notification->promotion_id)
            {
                return $array;
            }
            $array['promotion_id'] = $notification->promotion_id;
        }

        return $array;
    }
}
