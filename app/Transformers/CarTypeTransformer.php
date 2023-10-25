<?php

namespace App\Transformers;

use App\Models\CarType;
use League\Fractal\TransformerAbstract;

class CarTypeTransformer extends TransformerAbstract
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
    public function transform(CarType $type)
    {
        $array = [
            'id'   => $type->id,
            'name' => $type->name
        ];

        if($this->type == "dashboard"){
            unset($array['name']);
            $array['name_en'] = $type->translate('en')->name;
            $array['name_ar'] = $type->translate('ar')->name;
        }

        if($this->type == "drive_vehicles"){
            $array['price'] = $type->price;
            $array['image'] = $type->getFirstMediaUrl('car-images');
        }

        return $array;


    }
}
