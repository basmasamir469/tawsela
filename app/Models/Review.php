<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model 
{

    protected $table = 'reviews';
    public $timestamps = true;
    protected $guarded=[];


    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id');
    }

    public function driver()
    {
        return $this->belongsTo('App\Models\User','driver_id');
    }

}