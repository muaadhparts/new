<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierServiceArea extends Model
{
    use HasFactory;

    protected $table = 'courier_service_areas';

    public $timestamps = false;

    protected $fillable = [
        'courier_id',
        'city_id',
        'latitude',
        'longitude',
        'service_radius_km',
        'price',
        'status',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'service_radius_km' => 'integer',
        'price' => 'float',
        'status' => 'integer',
    ];

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function courier()
    {
        return $this->belongsTo(Courier::class, 'courier_id');
    }

    /**
     * Scope: Find service areas within radius of given coordinates
     * Uses Haversine formula for accurate distance calculation
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @param int $radiusKm Radius in kilometers (default 20)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithinRadius($query, float $lat, float $lng, int $radiusKm = 20)
    {
        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("
                courier_service_areas.*,
                (6371 * acos(
                    cos(radians(?)) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(latitude))
                )) AS distance_km
            ", [$lat, $lng, $lat])
            ->havingRaw("distance_km <= ?", [$radiusKm])
            ->orderBy('distance_km');
    }

    /**
     * Scope: Active couriers only
     */
    public function scopeWithActiveCourier($query)
    {
        return $query->whereHas('courier', function ($q) {
            $q->where('status', 1);
        });
    }
}
