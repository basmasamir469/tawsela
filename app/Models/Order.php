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
    Const STARTED        = 3;
    Const INWAY          = 4;
    Const FINISHED       = 5;
    Const COMPLETED      = 6;

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
        $picker = Picker::where('user_id',auth()->user()->id)->latest()->first();
        $theta    = $picker->longitude - $this->orderDetails?->start_longitude;
        $dist     = sin(deg2rad($picker->latitude)) * sin(deg2rad($this->orderDetails?->start_latitude)) +  cos(deg2rad($picker->latitude)) * cos(deg2rad($this->orderDetails?->start_latitude)) * cos(deg2rad($theta));
        $dist     = acos($dist);
        $dist     = rad2deg($dist);
        $miles    = $dist * 60 * 1.1515;
        $Km      = round($miles *1.609344,2);
        return $Km .' Km';
    }

    public function getDriveDistanceAttribute()
    {
        if(!$this->attributes['drive_distance'])
        {
            $from_long= $this->orderDetails?->start_longitude;
            $to_long  = $this->orderDetails?->end_longitude;
            $from_lat = $this->orderDetails?->start_latitude;
            $to_lat   = $this->orderDetails?->end_latitude;
            $theta    = $from_long - $to_long;
            $dist     = sin(deg2rad($from_lat)) * sin(deg2rad($to_lat)) +  cos(deg2rad($from_lat)) * cos(deg2rad($to_lat)) * cos(deg2rad($theta));
            $dist     = acos($dist);
            $dist     = rad2deg($dist);
            $miles    = $dist * 60 * 1.1515;
            $Km       = round($miles *1.609344,2);
            return $Km .' Km';    
        }
    }

    public function promocode()
    {
        return $this->belongsTo('App\Models\Promotion');
    }
}