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
        $array = $this->baseData($user);

        if($user->hasRoles('driver'))
        {
           $array = array_merge($array, $this->driverData($user));
        }

        return $array;
    }


    protected function baseData( User $user): array
    {
        return [
            'name' => $user->name,
            'address' => $user->address,
            'phone' => $user->phone,
            'notify_status' => $user->notify_status,
            'image' => $user->getFirstMediaUrl('users-images'),
        ];
    }

    protected function driverData(User $user): array
    {
        return [
            'image' => $user->getFirstMediaUrl('drivers-images'),
            'national_number' => $user->national_number,
            'nationalId_image' => $user->getFirstMediaUrl('nationalId_images'),
            'personal_image' => $user->getFirstMediaUrl('personal_images'),
            'driving_license' => $user->getFirstMediaUrl('driving_licenses'),
            'drug_analysis' => $user->getFirstMediaUrl('Drug_analyses'),
            'criminal_record' => $user->getFirstMediaUrl('criminal_records'),
            'car_type_id' => $user->vehicleDoc->car_type_id,
            'car_brand_id' => $user->vehicleDoc->car_brand_id,
            'car_color' => $user->vehicleDoc->car_color,
            'metal_plate_numbers' => $user->vehicleDoc->metal_plate_numbers,
            'model_year' => $user->vehicleDoc->model_year,
            'license_expire_date' => $user->vehicleDoc->license_expire_date,
            'vehicle_license' => $user->vehicleDoc->getFirstMediaUrl('vehicle_licenses'),
            'vehicle_license_behind' => $user->vehicleDoc->getFirstMediaUrl('vehicle_licenses_behind'),
            'vehicle_inspection' => $user->vehicleDoc->getFirstMediaUrl('vehicle_inspections'),
        ];
    }
}
