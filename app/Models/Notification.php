<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Notification extends Model implements TranslatableContract, HasMedia
{
    use Translatable, InteractsWithMedia;

    protected $table = 'notifications';
    public $translatedAttributes = ['title','description']; 
    public $timestamps = true;
    protected $guarded=[];


}