<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivationProcess extends Model 
{

    protected $table = 'activation_processes';
    public $timestamps = true;
    protected $guarded = [];
    CONST EMAIL = 0;
    CONST MOBILE = 1;

}