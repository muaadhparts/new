<?php

namespace App\Domain\Shipping\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Identity\Models\Courier;

/**
 * CourierServiceArea Model - Courier service coverage areas
 *
 * Domain: Shipping
 * Table: courier_service_areas
 *
 * @property int $id
 * @property int $courier_id
 * @property int $city_id
 * @property float|null $latitude
 * @property float|null $longitude
 * @property int $service_radius_km
 * @property float $price
 * @property int $status
 */
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

    // =========================================================
    // RELATIONS
    // =========================================================

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class, 'courier_id');
    }

    // =========================================================
    // SCOPES
    // =========================================================

    /**
     * Find service areas within radius of given coordinates
     * Uses Haversine formula for accurate distance calculation
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
     * Active couriers only
     */
    public function scopeWithActiveCourier($query)
    {
        return $query->whereHas('courier', function ($q) {
            $q->where('status', 1);
        });
    }

    /**
     * Active service areas
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Service areas by city
     */
    public function scopeByCity($query, int $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    /**
     * Service areas by courier
     */
    public function scopeByCourier($query, int $courierId)
    {
        return $query->where('courier_id', $courierId);
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Check if a location is within this service area
     */
    public function isLocationCovered(float $lat, float $lng): bool
    {
        if (!$this->latitude || !$this->longitude) {
            return false;
        }

        $distance = $this->calculateDistance($lat, $lng);
        return $distance <= $this->service_radius_km;
    }

    /**
     * Calculate distance from service area center to a location
     */
    public function calculateDistance(float $lat, float $lng): float
    {
        if (!$this->latitude || !$this->longitude) {
            return PHP_FLOAT_MAX;
        }

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

        return $earthRadius * $c;
    }
}
