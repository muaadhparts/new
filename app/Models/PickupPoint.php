<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickupPoint extends Model
{
    use HasFactory;

    protected $table = 'pickup_points';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'location',
        'city_id',
        'latitude',
        'longitude',
        'service_radius_km',
        'status',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'service_radius_km' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function merchant()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeByMerchant($query, $merchantId)
    {
        return $query->where('user_id', $merchantId);
    }

    public function scopeByCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    public function isInCity($cityId): bool
    {
        return $this->city_id == $cityId;
    }

    /**
     * Scope: Find pickup points within radius of given coordinates
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
                pickup_points.*,
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
     * Check if this pickup point is within radius of given coordinates
     *
     * @param float $lat
     * @param float $lng
     * @param int|null $radiusKm Use service_radius_km if null
     * @return bool
     */
    public function isWithinRadius(float $lat, float $lng, ?int $radiusKm = null): bool
    {
        if (!$this->latitude || !$this->longitude) {
            return false;
        }

        $radius = $radiusKm ?? $this->service_radius_km ?? 20;

        // Haversine formula
        $earthRadius = 6371; // km
        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($lat);
        $lonTo = deg2rad($lng);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2 +
             cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c;

        return $distance <= $radius;
    }
}
