<?php

namespace App\Transformers;

use App\Models\User;
use League\Fractal\TransformerAbstract;

class ProfileTransformer extends TransformerAbstract
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
    public function transform(User $user)
    {
        $array = [

            'name'            => $user->name,
            'address'         => $user->address,
            'phone'           => $user->phone,
            'notify_status'   => $user->notify_status,
            'image'           => $user->getFirstMediaUrl('users-images')
            
        ];

        if(auth()->user()->hasRoles('driver'))
        {
           $array['image']                = $user->getFirstMediaUrl('drivers-images');
           $array['national_number']      = $user->national_number;
           $array['nationalId_image']     = $user->getFirstMediaUrl('nationalId_images');
           $array['personal_image']       = $user->getFirstMediaUrl('personal_images');
           $array['driving_license']      = $user->getFirstMediaUrl('driving_licenses');
           $array['drug_analysis']        = $user->getFirstMediaUrl('Drug_analyses');
           $array['criminal_record']      = $user->getFirstMediaUrl('criminal_records');

           $array['car_type_id']          = $user->vehicleDoc->car_type_id;
           $array['car_brand_id']         = $user->vehicleDoc->car_brand_id;
           $array['car_color']            = $user->vehicelDoc->car_color;
           $array['metal_plate_numbers']  = $user->vehicleDoc->metal_plate_numbers;
           $array['model_year']           = $user->vehicleDoc->model_year;
           $array['license_expire_date']  = $user->vehicleDoc->license_expire_date;
           $array['vehicle_license']        = $user->vehicleDoc->getFirstMediaUrl('vehicle_licenses');
           $array['vehicle_license_behind'] = $user->vehicleDoc->getFirstMediaUrl('vehicle_licenses_behind');
           $array['vehicle_inspection']     = $user->vehicleDoc->getFirstMediaUrl('vehicle_inspections');
  
  
        }

        return $array;
    }
}
