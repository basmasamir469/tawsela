<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class CarColor extends Model implements TranslatableContract
{
    use Translatable;

    protected $table = 'car_colors';
    public $translatedAttributes = ['name']; 
    public $timestamps = true;
    protected $guarded=[];


}