<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = ['city_name', 'city_name_ar', 'status', 'country_id', 'latitude', 'longitude', 'tryoto_supported'];
    public $timestamps = false;

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
