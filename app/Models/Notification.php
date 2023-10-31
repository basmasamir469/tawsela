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
    
    public function drivers()
    {
        return $this->belongsToMany('App\Models\User','driver_notification','notification_id','driver_id');
    }
    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id');
    }

}