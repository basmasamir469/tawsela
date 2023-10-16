<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model implements TranslatableContract
{
    use Translatable;

    protected $table = 'promotions';
    public $timestamps = true;
    protected $guarded=[];


}