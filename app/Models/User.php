<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // protected $fillable = [
    //     'name',
    //     'email',
    //     'password',
    // ];

    protected $guarded=[];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $guard = 'api';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    public function userOrders()
    {
        return $this->hasMany('App\Models\Order','user_id');
    }

    public function driverOrders()
    {
        return $this->hasMany('App\Models\Order','driver_id');
    }

    public function vehicleDoc()
    {
        return $this->hasOne('App\Models\VehicleDoc','driver_id');
    }

    public function driveInvoice()
    {
        return $this->hasMany('App\Models\DriveInvoice');
    }

    public function addresses()
    {
        return $this->hasMany('App\Models\Address');
    }

    public function pickers()
    {
        return $this->hasMany('App\Models\Picker');
    }

    public function promotions()
    {
        return $this->belongsToMany('App\Models\Promotion');
    }
    public function getOrdersCountAttribute()
    {
        if(count($this->driverOrders) > 0){
            return count($this->driverOrders);
        }
            return 0;
    }

    public function getIsNewAttribute()
    {
      return  $is_new = count($this->driverOrders) > 0 ? 0 : 1;
    }

    public function notifyTokens()
    {
        return $this->hasMany('App\Models\Token');
    }

    public function driverNotifications()
    {
        return $this->belongsToMany('App\Models\Notification','driver_notification','driver_id','notification_id');
    }

    public function userNotifications()
    {
        return $this->hasMany('App\Models\Notification');
    }
}
