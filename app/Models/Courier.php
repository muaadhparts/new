<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Courier extends Authenticatable
{
    protected $table = 'couriers';

    protected $fillable = ['name', 'photo', 'zip', 'city_id', 'country', 'address', 'phone', 'fax', 'email', 'password', 'location', 'email_verify', 'email_verified', 'email_token', 'status', 'balance'];

    protected $hidden = [
        'password', 'remember_token'
    ];


    public function deliveries()
    {
        return $this->hasMany('App\Models\DeliveryCourier');
    }

    public function city()
    {
        return $this->belongsTo('App\Models\City');
    }
}
