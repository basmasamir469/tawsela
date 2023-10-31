<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model implements TranslatableContract
{
    use Translatable;

    protected $table = 'promotions';
    public $translatedAttributes = ['title']; 
    public $timestamps = true;
    protected $guarded=[];

    public function orders()
    {
        return $this->hasMany('App\Models\Order');
    }
}