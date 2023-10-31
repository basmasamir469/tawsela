<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model 
{

    protected $table = 'settings';
    public $timestamps = true;
    protected $guarded=[];
    protected $casts=['value'=>'array'];

    public static function map(): array
    {
        return static::all()->mapWithKeys(fn ($i) => [$i->key => $i])->all();
    }

}