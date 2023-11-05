<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

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

    public function calculateDriveDistance($st_long,$end_long,$st_lat,$end_lat)
    {
            $from_long= $st_long;
            $to_long  = $end_long;
            $from_lat = $st_lat;
            $to_lat   = $end_lat;
            $theta    = $from_long - $to_long;
            $dist     = sin(deg2rad($from_lat)) * sin(deg2rad($to_lat)) +  cos(deg2rad($from_lat)) * cos(deg2rad($to_lat)) * cos(deg2rad($theta));
            $dist     = acos($dist);
            $dist     = rad2deg($dist);
            $miles    = $dist * 60 * 1.1515;
            $Km       = round($miles *1.609344,2);

          return $Km;    
    }

    public function promocode()
    {
        return $this->belongsTo('App\Models\Promotion');
    }

    public function scopeFilterByDate($q)
    {
         return $q->when(request('filter'),function($q){
            if(request('filter') == 'day')
            {
              return $q->whereDate('orders.created_at',Carbon::today());
            }
            if(request('filter') == 'week')
            {
              return $q->whereBetween('orders.created_at',[Carbon::now()->startOfWeek(),Carbon::now()->endOfWeek()]);
            }
            if(request('filter') == 'month')
            {
              return $q->whereBetween('orders.created_at',[Carbon::now()->startOfMonth(),Carbon::now()->endOfMonth()]);
            }
          });

    }
}