<?php

namespace App\Http\Controllers\Api\Driver;

use App\Events\PickLocation;
use App\Http\Controllers\Controller;
use App\Http\Requests\drivers\DriverDocumentRequest;
use App\Http\Requests\drivers\UpdateDriverDocumentRequest;
use App\Http\Requests\drivers\VehicleDocumentRequest;
use App\Http\Requests\RegisterRequest;
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
use App\Transformers\NotificationTransformer;
use App\Transformers\OrderTransformer;
use App\Transformers\WalletTransformer;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
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
         'model_year'          => $data['model_year'],
         'license_expire_date' => $data['license_expire_date']
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
        $current_year = Carbon::now()->year;
        $model_years = collect(range(2000,$current_year))->map(fn($year) => ['year'=>$year]);
        return $this->dataResponse($model_years,'model_years',200);     
    }

    public function activate(Request $request)
    {
        $user = $request->user();
        $today = Carbon::createFromFormat('Y-m-d H:i:s',$user->activate_time??Carbon::now());
        if($user->active_status)
        {
            $start_time = Carbon::createFromFormat('Y-m-d H:i:s',$user->activate_time);
            $end_time   = Carbon::createFromFormat('Y-m-d H:i:s',Carbon::now());     
            $diff = $start_time->diff($end_time);
            $user->active_hours = Carbon::createFromFormat('H:i:s',$user->active_hours);
            $user->active_hours->addHours($diff->h)->addMinutes($diff->i);
            
            if(!$today->isSameDay(Carbon::now()))
            {
               $user->active_hours = Carbon::createFromTime(0, 0);
               $start_time = Carbon::now()->startOfDay();
               $end_time   = Carbon::createFromFormat('Y-m-d H:i:s',Carbon::now());
               $diff = $start_time->diff($end_time);
               $user->active_hours->addHours($diff->h)->addMinutes($diff->i);
            }    
        }
        $user->update([
            'active_status' => $user->active_status? 0 : 1,
            'activate_time' => $user->active_status ? null : Carbon::now(),
            'active_hours'  => $user->active_hours,
        ]);
        $message = $user->active_status? __('user is activated successfully') : __('user is inactivated successfully');
        return $this->dataResponse(null,$message,200);     
    }

    public function currentLocation(Request $request)
    {
        Picker::updateOrCreate([
           'user_id'=>$request->user()->id
        ],[
          'latitude' =>  $request->latitude,
          'longitude'=> $request->longitude,
        ]);
        
        $data = [
            'user_id'   => $request->user()->id,
            'latitude'  => $request->latitude,
            'longitude' => $request->longitude
        ];
         event(new PickLocation($data));

        return $this->dataResponse(null,'current location',200);
    }

    public function show()
    {
       $driver =  auth()->user();
       $driver = fractal($driver,new DriverTransformer())->toArray();
       return $this->dataResponse($driver,'driver details',200);
    }

    public function drivesDates()
    {
         $start_date = auth()->user()->driverOrders()->orderBy('created_at','asc')->first()->created_at;
         $start_date  = Carbon::parse($start_date)->format('Y-m-d');
         $end_date  = Carbon::now()->format('Y-m-d');
         $dates = collect(CarbonPeriod::create($start_date,$end_date))->map(fn($date)=>[$date->format('Y-m-d')]);
         return $this->dataResponse($dates,'drives dates',200);
    }

    public function drives(Request $request)
    {
        $skip = $request->skip? $request->skip : 0;
        $take = $request->take? $request->take : 10;
        $orders = $request->user()->driverOrders()->when(request('date'),function($q){
                  return $q->whereDate('created_at',request('date'));
        })->skip($skip)
          ->take($take)
          ->get();

        $orders = fractal()
        ->collection($orders)
        ->transformWith(new OrderTransformer())
        ->toArray();
        return $this->dataResponse($orders,'all drives',200);     
    }

    public function myWallet(Request $request)
    {
        $user = $request->user();
        $orders = $user->driverOrders()->filterByDate()->get();
        $pending_orders = $user->pendingOrders()->filterByDate()->count();
        $cancelled_orders = $orders->where('order_status',Order::CANCELLED)->count();
        $completed_orders = $orders->where('order_status',Order::COMPLETED)->count();
        // $unapproved_orders = $user->unapprovedOrders()->filterByDate()->count();
        $accepted_orders = $orders->whereIn('order_status',[Order::ACCEPTED,Order::STARTED,Order::FINISHED,Order::COMPLETED,Order::INWAY])->count();
        $acceptance_rate = ($pending_orders+$accepted_orders+$cancelled_orders) == 0 ? 0 : round(($accepted_orders/($pending_orders+$accepted_orders+$cancelled_orders))*100,1);

        return $this->dataResponse([
            'total_cost'         =>$orders->sum('total_cost'),
            'accepted_orders'    =>$accepted_orders,
            'cancelled_orders'   =>$cancelled_orders,
            'completed_orders'   =>$completed_orders,
            'acceptance rate'    =>$acceptance_rate .'%'
        ],('my wallet'),200);
    }

    public function notifications(Request $request)
    { 
        $skip = $request->skip? $request->skip :0;
        $take = $request->take? $request->take :10;
        $notifications = $request->user()->globalNotifications()
                                         ->latest()
                                         ->skip($skip)->take($take)
                                         ->get();
        $notifications = fractal()
        ->collection($notifications)
        ->transformWith(new NotificationTransformer())
        ->toArray();
        return $this->dataResponse($notifications,'notifications',200);     
    }
}
