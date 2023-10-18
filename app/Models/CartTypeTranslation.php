<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class CartTypeTranslation extends Model implements TranslatableContract
{

    use Translatable;

    protected $table = 'cart_type_translations';
    public $timestamps = true;
    protected $guarded=[];


}