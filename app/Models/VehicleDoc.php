<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class VehicleDoc extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'vehicle_docs';
    public $timestamps = true;
    protected $guarded=[];


    public function carType()
    {
        return $this->hasOne('App\Models\CarType');
    }

    public function carBrand()
    {
        return $this->hasOne('App\Models\CarBrand');
    }

    public function carColor()
    {
        return $this->hasOne('App\Models\CarColor');
    }

    public function driver()
    {
        return $this->belongsTo('App\Models\User','driver_id');
    }

}