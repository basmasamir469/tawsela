<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;

class CarBrandTranslation extends Model implements TranslatableContract
{
    use Translatable;

    protected $table = 'car_brand_translations';
    public $timestamps = true;
    protected $guarded=[];

}