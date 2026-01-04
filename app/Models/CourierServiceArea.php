<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierServiceArea extends Model
{
    use HasFactory;

    protected $table = 'courier_service_areas';

    public $timestamps = false;

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function courier()
    {
        return $this->belongsTo(Courier::class, 'courier_id');
    }
}
