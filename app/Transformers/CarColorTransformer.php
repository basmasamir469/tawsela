<?php

namespace App\Transformers;

use App\Models\CarColor;
use League\Fractal\TransformerAbstract;

class CarColorTransformer extends TransformerAbstract
{
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
    public function transform(CarColor $type)
    {
        return [
            'id'   => $type->id,
            'name' => $type->name
        ];
    }
}
