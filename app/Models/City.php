<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * City Model
 *
 * التوجيه المعماري:
 * - Tryoto هو المصدر الوحيد للمدن
 * - البيانات: city_name (إنجليزي فقط), latitude, longitude, country_id, tryoto_supported
 * - لا يوجد اسم عربي للمدن (city_name_ar محذوف من قاعدة البيانات)
 */
class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_name',
        'status',
        'country_id',
        'latitude',
        'longitude',
        'tryoto_supported'
    ];

    public $timestamps = false;

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
