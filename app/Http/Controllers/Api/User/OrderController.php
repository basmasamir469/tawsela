<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\users\OrderRequest;
use App\Models\Address;
use App\Models\CarType;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\Token;
use App\Models\User;
use App\Transformers\CarTypeTransformer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
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
    DB::beginTransaction();

    if($data['promo_code'])
    {
    
        if(!Promotion::where('code',$data['promo_code'])->where('expire_date','>',Carbon::now())->first())
        {
            return $this->dataResponse(null,__('this promo code is invalid'),422);
        }

        $promotion = Promotion::where('code',$data['promo_code'])->first();

        if($request->user()->userOrders()->where(['promotion_id'=>$promotion->id,'order_status'=>Order::ACCEPTED])->first())
        {
            return $this->dataResponse(null,__('this promo code is invalid'),422);
        }

        if($promotion?->orders()->where('order_status',Order::ACCEPTED)->count() > 10)
        {
            return $this->dataResponse(null,__('this promo code is invalid'),422);
        }
        $price_after_discount = $data['price']-(($promotion->discount * $data['price'])/100);    
    }

    $setting = Setting::where('key','vat')->first();
    $order = Order::create([
    'car_type_id'  => $data['car_type_id'],
    'user_id'      => $request->user()->id,
    'order_status' => Order::PENDING,
    'promotion_id'   => $promotion->id??null,
    'price'          => $data['price'],
    'price_after_discount'=> $price_after_discount?? 0,
    'vat'                 => $setting->value['en'],
    'payment_way'         => 'cash',
    ]);
    $order->orderDetails()->create([
    'start_address'   =>$data['start_address'],
    'start_latitude'  =>$data['start_latitude'],
    'start_longitude' =>$data['start_longitude'],
    'end_address'     =>$data['end_address'],
    'end_latitude'    =>$data['end_latitude'],
    'end_longitude'   =>$data['end_longitude']
    ]);

    $start_address_lat  = $order->orderDetails->start_latitude;
    $end_address_long    = $order->orderDetails->end_longitude;
    $end_address_lat    = $order->orderDetails->end_latitude;
    $start_address_long = $order->orderDetails->start_longitude;

    $order->drive_distance = $request->drive_distance?? $order->calculateDriveDistance($start_address_long,$end_address_long,$start_address_lat,$end_address_lat);
    $order->save();

    DB::commit();

     $driver_ids = User::join('vehicle_docs',function($join) use($order){
                     return $join->on('vehicle_docs.driver_id','=','users.id')
                                 ->where('vehicle_docs.car_type_id',$order->car_type_id);
                })->join('picker',function($join){
                     return $join->on('picker.user_id','=','users.id')
                        ->orderBy('picker.created_at','desc')
                        ->limit(1);
                    })->where(DB::raw("ROUND((degrees(acos(sin(radians(picker.latitude)) * sin(radians($start_address_lat)) +  cos(radians(picker.latitude)) * cos(radians($start_address_lat)) * cos(radians(picker.longitude-$start_address_long)))) * 60 * 1.1515) * 1.609344 , 2)"),'<',100)
                    ->where('users.active_status',1)
                    ->where('users.account_status',1)
                   ->pluck('users.id')->toArray();

    $notification = Notification::create([
                  'en'=>['title'=>'طلب جديد','description'=>'من فضلك وصلني لهذا العنوان'],
                  'ar'=>['title'=>'New Order','description'=>'please drive me to this address']
    ]);

    $notification->drivers()->attach($driver_ids);
    $android_tokens = Token::whereIn('user_id',$driver_ids)->where('device_type','android')->pluck('token')->toArray();
    $ios_tokens = Token::whereIn('user_id',$driver_ids)->where('device_type','ios')->pluck('token')->toArray();
    if(count($android_tokens) > 0)
    {
        $data =
        [
           'title'      => $notification->title,
           'body'       => $notification->description,
           'action_id'  => $order->id,
           'action_type'=> 'new-order'
        ];
       $this->notifyByFirebase($android_tokens,$data,'android');
    }

    if(count($ios_tokens) > 0)
    {
        $data =
        [
           'title'      => $notification->title,
           'body'       => $notification->description,
           'action_id'  => $order->id,
           'action_type'=> 'new-order'
        ];
       $this->notifyByFirebase($ios_tokens,$data,'ios');
    }


    return $this->dataResponse(null , __('order is sent successfully') ,200);
   
   }  
}
