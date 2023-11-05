<?php

namespace App\Transformers;

use App\Models\User;
use Carbon\Carbon;
use League\Fractal\TransformerAbstract;

class DriverTransformer extends TransformerAbstract
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
    public function transform(User $driver)
    {
        $array =  [
            'id'             => $driver->id,
            'name'           => $driver->name,
            'image'          => $driver->getFirstMediaUrl('drivers-images'),
            'is_new'         => $driver->is_new,
            'orders_count'   => $driver->orders_count,
            'total_income'   => $driver->total_income,
            'total_distance' => $driver->driverOrders()->whereDate('created_at',Carbon::now()->format('Y-m-d'))->sum('drive_distance'),
            'connect_hours'  => Carbon::parse($driver->active_hours)->format('H:i')
        ];

        // if($this->type=="dashboard"){
        //     unset($array['name']);
        //     $array['name_en'] = $type->translate('en')->name;
        //     $array['name_ar'] = $type->translate('ar')->name;
        // }

        return $array;
    }
}
