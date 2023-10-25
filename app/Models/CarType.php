<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Spatie\MediaLibrary\InteractsWithMedia;

class CarType extends Model implements HasMedia ,TranslatableContract
{
    use InteractsWithMedia, Translatable;
    public $translatedAttributes = ['name']; 
    protected $table = 'car_types';
    public $timestamps = true;
    protected $guarded=[];




}