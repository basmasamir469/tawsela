<?php

namespace App\Transformers;

use App\Models\Address;
use App\Models\User;
use League\Fractal\TransformerAbstract;

class AddressTransformer extends TransformerAbstract
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
    public function transform(Address $address)
    {
        $array =  [
            'id'             => $address->id,
            'name'           => $address->name,
            'type'           => $address->type,
        ];
        return $array;
    }
}
