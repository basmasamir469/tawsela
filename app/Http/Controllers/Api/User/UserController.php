<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\users\OrderRequest;
use App\Models\Address;
use App\Models\CarType;
use App\Models\Order;
use App\Models\Promotion;
use App\Transformers\CarTypeTransformer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
   public function driveVehicles()
   {
    $vehicles = CarType::all();
    $vehicles = fractal()
    ->collection($vehicles)
    ->transformWith(new CarTypeTransformer('drive_vehicles'))
    ->toArray();

    return $this->dataResponse($vehicles, 'all drive vehicles', 200);
   }
   public function makeOrder(OrderRequest $request)
   {
    $data  = $request->validated();
    $price = CarType::findOrFail($data['car_type_id'])->price;
    DB::beginTransaction();

    if($data['promo_code'])
    {
        if($request->user()->userOrders()->where(['promo_code'=>$data['promo_code'],'order_status'=>Order::ACCEPTED])->first())
        {
            return $this->dataResponse(null,__('this promo code is invalid'),422);
        }
    
        if(!Promotion::where('code',$data['promo_code'])->where('expire_date','>',Carbon::now())->first())
        {
            return $this->dataResponse(null,__('this promo code is invalid'),422);
        }
    }

    $order = Order::create([
    'car_type_id'  => $data['car_type_id'],
    'user_id'      => $request->user()->id,
    'order_status' => Order::PENDING,
    'promo_code'   => $data['promo_code'],
    'price'        => $price,
    'payment_way'  => 'cash'

    ]);
    if($data['permenant_end_latitude'] && $data['permenant_end_longitude'])
    {
        $request->user()->addresses()->create([
             'type'        => Address::OTHER,
             'name'        => $data['permenant_end_address'],
             'latitude'    => $data['permenant_end_latitude'],
             'longitude'   => $data['permenant_end_longitude']
        ]);

        $order->orderDetails()->create([
            'start_address'   =>$data['start_address'],
            'start_latitude'  =>$data['start_latitude'],
            'start_longitude' =>$data['start_longitude'],
            'end_address'     =>$data['permenant_end_address'],
            'end_latitude'    =>$data['permenant_end_latitude'],
            'end_longitude'   =>$data['permenant_end_longitude']
        ]);

        DB::commit();

        return $this->dataResponse(null , __('order is sent successfully') ,200);      
    }

    $order->orderDetails()->create([
    'address_id'      =>$data['address_id'],
    'start_address'   =>$data['start_address'],
    'start_latitude'  =>$data['start_latitude'],
    'start_longitude' =>$data['start_longitude'],
    'end_address'     =>$data['end_address'],
    'end_latitude'    =>$data['end_latitude'],
    'end_longitude'   =>$data['end_longitude']
    ]);
    DB::commit();
    return $this->dataResponse(null , __('order is sent successfully') ,200);
   
   }  
}
