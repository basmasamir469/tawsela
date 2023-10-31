<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\drivers\CancelOrderRequest;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Picker;
use App\Models\Promotion;
use App\Models\Token;
use App\Transformers\OrderTransformer;
use Carbon\Carbon;
use Vonage\Client ;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Vonage\Client\Credentials\Basic;

class OrderController extends Controller
{
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

    public function acceptOrder($id)
    {
       $order = Order::findOrFail($id);
       if($order->order_status == Order::PENDING)
       {
        DB::beginTransaction();
        $order->update([
          'order_status'=>Order::ACCEPTED,
          'driver_id'   =>auth()->user()->id,
        ]);
        $notification = Notification::create([
            'user_id' => $order->user_id,
            'ar'      =>['title'=>'تم قبول طلبك','description'=>'من فضلك انتظر السائق في الطريق اليك'],
            'en'      =>['title'=>'your order is accepted','description'=>'please wait the driver is on his way to you ']
        ]);
        DB::commit();
        $token = Token::where('user_id',$order->user_id)->first();
        if($token)
        {
            $data =
            [
               'title'      => $notification->title,
               'body'       => $notification->description,
               'action_id'  => $order->id,
               'action_type'=> 'cancel-order'
            ];
           $this->notifyByFirebase([$token->token],$data,$token->device_type);
           
       }  
       return $this->dataResponse(null,__('order is accepted successfully'),200);
       }
       return $this->dataResponse(null,__('this order is not available now'),422);
    }

    public function show($id)
    {
        $order = Order::findOrFail($id);
        $order = fractal($order,new OrderTransformer('order_details'))->toArray();
        return $this->dataResponse($order,'order_details',200);
    }

    public function cancelOrder(CancelOrderRequest $request,$id)
    {
        $data  = $request->validated();
        $order = Order::findOrFail($id);
        if($order->order_status == Order::ACCEPTED)
        {
            $order->update([
              'order_status'   =>Order::CANCELLED,
              'cancel_reason'  =>$data['cancel_reason']
            ]);
            $notification = Notification::create([
                'user_id' =>$order->user_id,
                'ar'      =>['title'=>' تم الغاء طلبك','description'=>' ناسف لابلاغك انه تم الغاء طلبك '],
                'en'      =>['title'=>' your order is cancelled','description'=>'we are sorry to inform you that you order is cancelled for ']
            ]);
            $token = Token::where('user_id',$order->user_id)->first();
            if($token)
            {
                $data =
                [
                   'title'      => $notification->title,
                   'body'       => $notification->description,
                   'action_id'  => $order->id,
                   'action_type'=> 'accept-order'
                ];
              $this->notifyByFirebase([$token->token],$data,$token->device_type);
            }
        
            return $this->dataResponse(null,__('order is cancelled successfully'),200);
    
        }
    }

    public function startDrive($id)
    {
        $order = Order::findOrFail($id);
        if($order->order_status == Order::ACCEPTED)
        {
            $order->update([
              'order_status'=>Order::STARTED,
              'start_time'  => Carbon::now()
            ]);
        
            return $this->dataResponse(null,__('drive is started'),200);
    
        }
            return $this->dataResponse(null,__('drive cannot be started'),422);
    }


    public function finishDrive(Request $request,$id)
    {
        $order = Order::findOrFail($id);
        if($order->order_status == Order::STARTED)
        {   
            $waiting_price = $request->waiting_price ? $request->waiting_price : 0;
            $total_cost = $order->price_after_discount? ($order->price_after_discount + $waiting_price): ($order->price + $waiting_price);
            $vat = ($total_cost * $order->vat )/100;
            $total_cost += $vat;
            $order->update([
              'order_status' =>Order::FINISHED,
              'waiting_price'=>$waiting_price,
              'total_cost'   => $total_cost,
              'end_time'     => Carbon::now(),
            ]);
            $driver = $order->driver;
            $driver->debit += $vat;
            $driver->save();

            $order = fractal($order,new OrderTransformer('finished_drive'))->toArray();
            return $this->dataResponse($order,__('drive is finished'),200);
    
        }
            return $this->dataResponse(null,__('drive cannot be finished'),422);
    }

    public function completeDrive($id)
    {
        $order = Order::findOrFail($id);
        if($order->order_status == Order::FINISHED)
        {
            $order->update([
              'order_status'=>Order::COMPLETED,
            ]);
        
            return $this->dataResponse(null,__('drive is completed successfully'),200);
    
        }
            return $this->dataResponse(null,__('drive cannot be completed'),422);
    }


    public function call($order_id)
    {
        $order = Order::findOrFail($order_id);
        $user_phone   = '+2'.$order->user->phone;
        $driver_phone = '+2'.auth()->user()->phone;
        $keypair = new \Vonage\Client\Credentials\Keypair(
            file_get_contents('private.key'),
            env('VONAGE_APPLICATION_ID')
        );
        $client = new Client($keypair);
        $outboundCall = new \Vonage\Voice\OutboundCall(
            new \Vonage\Voice\Endpoint\Phone($user_phone),
            new \Vonage\Voice\Endpoint\Phone($driver_phone)
        );
        $outboundCall->setAnswerWebhook(
            new \Vonage\Voice\Webhook(
                'https://raw.githubusercontent.com/nexmo-community/ncco-examples/gh-pages/text-to-speech.json',
                \Vonage\Voice\Webhook::METHOD_GET
            )
        );
        $response = $client->voice()->createOutboundCall($outboundCall);
        
        var_dump($response);
    }
}
