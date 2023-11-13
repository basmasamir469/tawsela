<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded=[];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $guard = 'api';

    /*
    
    account_status 

    0 => closed
    1 => opened 

    */

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    public function userOrders()
    {
        return $this->hasMany('App\Models\Order','user_id');
    }

    public function driverOrders()
    {
        return $this->hasMany('App\Models\Order','driver_id');
    }

    public function vehicleDoc()
    {
        return $this->hasOne('App\Models\VehicleDoc','driver_id');
    }

    public function driveInvoice()
    {
        return $this->hasMany('App\Models\DriveInvoice');
    }

    public function addresses()
    {
        return $this->hasMany('App\Models\Address');
    }

    public function pickers()
    {
        return $this->hasMany('App\Models\Picker');
    }

    public function promotions()
    {
        return $this->belongsToMany('App\Models\Promotion','promotion_user','user_id','promotion_id');
    }
    public function getOrdersCountAttribute()
    {
       return $this->driverOrders()->whereDate('created_at',Carbon::now()->format('Y-m-d'))->count();
    }

    public function getTotalIncomeAttribute()
    {
        return $this->driverOrders()->whereDate('created_at',Carbon::now()->format('Y-m-d'))->sum('total_cost');
    }

    public function getIsNewAttribute()
    {
      return  $is_new = count($this->driverOrders) > 0 ? 0 : 1;
    }

    public function notifyTokens()
    {
        return $this->hasMany('App\Models\Token');
    }

    public function globalNotifications()
    {
        return $this->belongsToMany('App\Models\Notification','notification_user','user_id','notification_id');
    }

    // public function userNotifications()
    // {
    //     return $this->hasMany('App\Models\Notification');
    // }

    public function getDriverDistanceAttribute()
    {
        return $this->driverOrders->sum(function ($order) {
            return $order->drive_distance;
        });
    }

    public function unapprovedOrders() 
    {
        $vehicle_id = auth()->user()->vehicleDoc->car_type_id;
        $picker     = Picker::where('user_id',auth()->user()->id)->latest()->first();          
        $orders     = Order::join('order_details','order_details.order_id','=','orders.id')
                       ->join('users','users.id','orders.user_id')
                       ->where(['orders.car_type_id'=>$vehicle_id,'orders.order_status'=>Order::CANCELLED,'orders.driver_id'=>Null])
                       ->where(DB::raw("ROUND((degrees(acos(sin(radians($picker->latitude)) * sin(radians(order_details.start_latitude)) +  cos(radians($picker->latitude)) * cos(radians(order_details.start_latitude)) * cos(radians($picker->longitude-order_details.start_longitude)))) * 60 * 1.1515) * 1.609344 , 2)"),'<',100);
      return $orders;
    }

    public function pendingOrders() 
    {
        $vehicle_id = auth()->user()->vehicleDoc->car_type_id;
        $picker     = Picker::where('user_id',auth()->user()->id)->latest()->first();          
        $orders     = Order::join('order_details','order_details.order_id','=','orders.id')
                       ->join('users','users.id','orders.user_id')
                       ->where(['orders.car_type_id'=>$vehicle_id,'orders.order_status'=>Order::PENDING])
                       ->where(DB::raw("ROUND((degrees(acos(sin(radians($picker->latitude)) * sin(radians(order_details.start_latitude)) +  cos(radians($picker->latitude)) * cos(radians(order_details.start_latitude)) * cos(radians($picker->longitude-order_details.start_longitude)))) * 60 * 1.1515) * 1.609344 , 2)"),'<',100);
        return $orders;
    }

   public function reviews()
   {
      return $this->hasMany('App\Models\Review','driver_id');
   }

}
