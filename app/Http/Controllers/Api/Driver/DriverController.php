<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\drivers\DriverDocumentRequest;
use App\Http\Requests\drivers\VehicleDocumentRequest;
use App\Models\CarBrand;
use App\Models\CarColor;
use App\Models\CarType;
use App\Models\Order;
use App\Models\Picker;
use App\Models\VehicleDoc;
use App\Transformers\CarBrandTransformer;
use App\Transformers\CarColorTransformer;
use App\Transformers\CarTypeTransformer;
use App\Transformers\DriverTransformer;
use App\Transformers\OrderTransformer;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverController extends Controller
{
    public function driverDocuments(DriverDocumentRequest $request)
    {
        $data = $request->validated();
        $driver = $request->user();
        DB::beginTransaction();

        $driver->update([
           'national_number'=>$data['national_number']
        ]);

        try
        {
        $driver->addMedia($data['nationalId_image'])
        ->toMediaCollection('nationalId_images');

        $driver->addMedia($data['personal_image'])
        ->toMediaCollection('personal_images');

        $driver->addMedia($data['driving_license'])
        ->toMediaCollection('driving_licenses');

        $data['drug_analysis']   && $driver->addMedia($data['drug_analysis'])
        ->toMediaCollection('Drug_analyses');

        $data['criminal_record'] && $driver->addMedia($data['criminal_record'])
        ->toMediaCollection('criminal_records');

        DB::commit();
        }
        catch(\Exception $e)
        {
            DB::rollBack();
            return $this->dataResponse(null,$e->getMessage(),422);
        }

        return $this->dataResponse(null,__('documents sent successfully'),200);
    }

    public function vehicleDocuments(VehicleDocumentRequest $request)
    {
        $data    = $request->validated();
        DB::beginTransaction();
        $vehicle = $request->user()->vehicleDoc()->create([
         'car_type_id'         => $data['car_type_id'],
         'car_brand_id'        => $data['car_brand_id'],
         'car_color'           => $data['car_color'],
         'metal_plate_numbers' => $data['metal_plate_numbers'],
         'model_year'          => $data['model_year']
        ]);
        
        try
        {
        $vehicle->addMedia($data['vehicle_license'])
        ->toMediaCollection('vehicle_licenses');

        $vehicle->addMedia($data['vehicle_license_behind'])
        ->toMediaCollection('vehicle_licenses_behind');

        $data['vehicle_inspection'] && $vehicle->addMedia($data['vehicle_inspection'])
        ->toMediaCollection('vehicle_inspections');

        DB::commit();
        }
        catch(\Exception $e)
        {
            DB::rollBack();
            return $this->dataResponse(null,$e->getMessage(),422);
        }

        return $this->dataResponse(null,__('documents sent successfully'),200);     
    }

    public function carTypes()
    {
        $car_types = CarType::all();
        $car_types = fractal()
        ->collection($car_types)
        ->transformWith(new CarTypeTransformer())
        ->toArray();
        return $this->dataResponse($car_types,'car_types',200);     
    }

    public function carBrands()
    {
        $car_brands = CarBrand::all();
        $car_brands = fractal()
        ->collection($car_brands)
        ->transformWith(new CarBrandTransformer())
        ->toArray();
        return $this->dataResponse($car_brands,'car_brands',200);     
    }

    public function carColors()
    {
        $car_colors = CarColor::all();
        $car_colors = fractal()
        ->collection($car_colors)
        ->transformWith(new CarColorTransformer())
        ->toArray();
        return $this->dataResponse($car_colors,'car_colors',200);     
    }

    public function modelYears()
    {
        $model_years = collect(range(2000,2023))->map(fn($year) => ['year'=>$year]);
        return $this->dataResponse($model_years,'model_years',200);     
    }

    public function activate(Request $request)
    {
        $user = $request->user();

        $user->update([
            'active_status' => $user->active_status? 0 : 1
        ]);
        $message = $user->active_status? __('user is activated successfully') : __('user is inactivated successfully');
        return $this->dataResponse(null,$message,200);     
    }

    public function currentLocation(Request $request)
    {
        $request->user()->pickers()->create([
          'latitude' =>  $request->latitude,
          'longitude'=> $request->longitude,
        ]);

        return $this->dataResponse(null,'current location',200);
    }

    public function show()
    {
       $driver =  auth()->user();
       $driver = fractal($driver,new DriverTransformer())->toArray();
       return $this->dataResponse($driver,'driver details',200);
    }

    public function pendingOrders()
    {
        $vehicle_id = auth()->user()->vehicleDoc->car_type_id;
        $picker     = Picker::where('user_id',auth()->user()->id)->latest()->first();          
        $orders     = Order::join('order_details','order_details.order_id','=','orders.id')
                      ->join('users','users.id','orders.user_id')
                       ->where(['orders.car_type_id'=>$vehicle_id,'orders.order_status'=>Order::PENDING])
                       ->where(DB::raw("ROUND((degrees(acos(sin(radians($picker->latitude)) * sin(radians(order_details.start_latitude)) +  cos(radians($picker->latitude)) * cos(radians(order_details.start_latitude)) * cos(radians($picker->longitude-order_details.start_longitude)))) * 60 * 1.1515) * 1.609344 , 2)"),'<',100)
                       ->get();
        $orders = fractal()
        ->collection($orders)
        ->transformWith(new OrderTransformer())
        ->toArray();
        return $this->dataResponse($orders,'pending orders',200);     

    }

}
