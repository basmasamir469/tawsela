<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends Model 
{

    protected $table = 'orders';
    public $timestamps = true;
    protected $guarded=[];

    Const CANCELLED      = 0;
    Const PENDING        = 1;
    Const ACCEPTED       = 2;

    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id');
    }

    public function driver()
    {
        return $this->belongsTo('App\Models\User','driver_id');
    }

    public function orderDetails()
    {
        return $this->hasOne('App\Models\OrderDetail');
    }

    public function getDistanceAttribute()
    {
        $vehicle_id = auth()->user()->vehicleDoc->car_type_id; 
        $picker = Picker::where('user_id',auth()->user()->id)->latest()->first();
        $theta    = $picker->longitude - $this->orderDetails?->start_longitude;
        $dist     = sin(deg2rad($picker->latitude)) * sin(deg2rad($this->orderDetails?->start_latitude)) +  cos(deg2rad($picker->latitude)) * cos(deg2rad($this->orderDetails?->start_latitude)) * cos(deg2rad($theta));
        $dist     = acos($dist);
        $dist     = rad2deg($dist);
        $miles    = $dist * 60 * 1.1515;
        $Km      = round($miles *1.609344,2);
        return $Km .' Km';



    }
}