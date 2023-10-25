<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Picker extends Model 
{

    protected $table = 'picker';
    public $timestamps = true;
    protected $guarded=[];

 public function user()
 {
    return $this->belongsTo('App\Models\User');
 }
}