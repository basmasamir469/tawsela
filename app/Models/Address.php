<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model 
{

    protected $table = 'addresses';
    public $timestamps = true;
    protected $guarded=[];

    CONST OTHER = 0;
    CONST WORK  = 1;
    CONST HOME  = 2;

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function getTypeAttribute(){
        if($this->attributes['type'] == self::HOME)
        {
            return 'Home';
        }
        elseif($this->attributes['type'] == self::WORK)
        {
            return 'Work';
        }
        else
        {
            return 'Other';
        }
    }

}