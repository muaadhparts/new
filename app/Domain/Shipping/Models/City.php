<?php

namespace App\Domain\Shipping\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * City Model - City/location data
 *
 * Domain: Shipping
 * Table: cities
 *
 * Architectural note:
 * - Tryoto is the single source for cities
 * - Data: city_name (English only), latitude, longitude, country_id, tryoto_supported
 * - No Arabic city names (city_name_ar removed from database)
 *
 * @property int $id
 * @property string $city_name
 * @property int $status
 * @property int $country_id
 * @property float|null $latitude
 * @property float|null $longitude
 * @property bool $tryoto_supported
 */
class City extends Model
{
    use HasFactory;

    protected $table = 'cities';

    protected $fillable = [
        'city_name',
        'status',
        'country_id',
        'latitude',
        'longitude',
        'tryoto_supported'
    ];

    protected $casts = [
        'status' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'tryoto_supported' => 'boolean',
    ];

    public $timestamps = false;

    // =========================================================
    // RELATIONS
    // =========================================================

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function courierServiceAreas(): HasMany
    {
        return $this->hasMany(CourierServiceArea::class, 'city_id');
    }

    public function merchantBranches(): HasMany
    {
        return $this->hasMany(\App\Models\MerchantBranch::class, 'city_id');
    }

    // =========================================================
    // SCOPES
    // =========================================================

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeTryotoSupported($query)
    {
        return $query->where('tryoto_supported', true);
    }

    public function scopeByCountry($query, int $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    public function scopeWithCoordinates($query)
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Check if city has valid coordinates
     */
    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Get display name
     */
    public function getDisplayName(): string
    {
        return $this->city_name;
    }
}
