<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriveInvoice extends Model 
{

    protected $table = 'drive_invoices';
    public $timestamps = true;
    protected $guarded=[];


    public function driver()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function order()
    {
        return $this->belongsTo('App\Models\Order');
    }

}