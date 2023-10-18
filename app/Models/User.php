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
    public function orders()
    {
        return $this->hasMany('App\Models\Order');
    }

    public function vehicleDoc()
    {
        return $this->hasOne('App\Models\VehicleDoc');
    }

    public function driveInvoice()
    {
        return $this->hasMany('App\Models\DriveInvoice');
    }

    public function addresses()
    {
        return $this->hasMany('App\Models\Address');
    }

    public function promotions()
    {
        return $this->belongsToMany('App\Models\Promotion');
    }

    public function notifications()
    {
        return $this->hasMany('App\Models\Notification');
    }
}