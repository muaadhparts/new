<?php

namespace App\Domain\Merchant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domain\Identity\Models\User;
use App\Domain\Shipping\Models\City;
use App\Domain\Shipping\Models\Country;
use App\Domain\Shipping\Models\DeliveryCourier;
use App\Domain\Commerce\Models\MerchantPurchase;

/**
 * MerchantBranch Model - Merchant branch/warehouse locations
 *
 * Domain: Merchant
 * Table: merchant_branches
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $branch_name
 * @property string|null $warehouse_name
 * @property string|null $tryoto_warehouse_code
 * @property int|null $country_id
 * @property int|null $city_id
 * @property string|null $location
 * @property float|null $latitude
 * @property float|null $longitude
 * @property int $status
 */
class MerchantBranch extends Model
{
    use HasFactory;

    protected $table = 'merchant_branches';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'branch_name',
        'warehouse_name',
        'tryoto_warehouse_code',
        'country_id',
        'location',
        'city_id',
        'latitude',
        'longitude',
        'status',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'country_id' => 'integer',
        'city_id' => 'integer',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'status' => 'integer',
    ];

    /* =========================================================================
     |  RELATIONSHIPS
     | ========================================================================= */

    /**
     * The user (merchant) who owns this branch.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias for user() - merchant relationship.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The city this branch is located in.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    /**
     * The country this branch is located in.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * All merchant items in this branch.
     */
    public function merchantItems(): HasMany
    {
        return $this->hasMany(MerchantItem::class, 'merchant_branch_id');
    }

    /**
     * Active merchant items in this branch.
     */
    public function activeMerchantItems(): HasMany
    {
        return $this->merchantItems()->where('status', 1);
    }

    /**
     * Delivery couriers assigned to this branch.
     */
    public function deliveryCouriers(): HasMany
    {
        return $this->hasMany(DeliveryCourier::class, 'merchant_branch_id');
    }

    /**
     * Merchant purchases from this branch.
     */
    public function merchantPurchases(): HasMany
    {
        return $this->hasMany(MerchantPurchase::class, 'merchant_branch_id');
    }

    /* =========================================================================
     |  SCOPES
     | ========================================================================= */

    /**
     * Scope: Only active branches.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    /**
     * Scope: Filter by merchant ID.
     */
    public function scopeForMerchant(Builder $query, int $merchantId): Builder
    {
        return $query->where('user_id', $merchantId);
    }

    /**
     * Scope: Alias for forMerchant.
     */
    public function scopeByMerchant(Builder $query, int $merchantId): Builder
    {
        return $query->where('user_id', $merchantId);
    }

    /**
     * Scope: Filter by city ID.
     */
    public function scopeForCity(Builder $query, int $cityId): Builder
    {
        return $query->where('city_id', $cityId);
    }

    /**
     * Scope: Alias for forCity.
     */
    public function scopeByCity(Builder $query, int $cityId): Builder
    {
        return $query->where('city_id', $cityId);
    }

    /**
     * Scope: Filter by country ID.
     */
    public function scopeForCountry(Builder $query, int $countryId): Builder
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Scope: Only branches with Tryoto warehouse code.
     */
    public function scopeWithTryoto(Builder $query): Builder
    {
        return $query->whereNotNull('tryoto_warehouse_code')
            ->where('tryoto_warehouse_code', '!=', '');
    }

    /**
     * Scope: Only branches with coordinates.
     */
    public function scopeWithCoordinates(Builder $query): Builder
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }

    /**
     * Scope: Find branches within a radius (km) from a point.
     */
    public function scopeWithinRadius(Builder $query, float $lat, float $lng, float $radiusKm = 50): Builder
    {
        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";

        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("merchant_branches.*, {$haversine} AS distance", [$lat, $lng, $lat])
            ->havingRaw('distance <= ?', [$radiusKm])
            ->orderBy('distance');
    }

    /* =========================================================================
     |  ACCESSORS
     | ========================================================================= */

    /**
     * Get the display name (localized).
     */
    public function getDisplayNameAttribute(): string
    {
        if (app()->getLocale() === 'ar') {
            return $this->warehouse_name ?: $this->branch_name ?: __('Branch') . ' #' . $this->id;
        }
        return $this->branch_name ?: $this->warehouse_name ?: 'Branch #' . $this->id;
    }

    /**
     * Get the city name.
     */
    public function getCityNameAttribute(): ?string
    {
        if ($this->relationLoaded('city')) {
            return $this->city?->city_name;
        }
        return $this->city()->value('city_name');
    }

    /**
     * Get the full address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->location,
            $this->city_name,
        ]);
        return implode(', ', $parts);
    }

    /**
     * Check if this branch has coordinates.
     */
    public function getHasCoordinatesAttribute(): bool
    {
        return !empty($this->latitude) && !empty($this->longitude);
    }

    /* =========================================================================
     |  HELPER METHODS
     | ========================================================================= */

    /**
     * Check if branch has Tryoto warehouse code.
     */
    public function hasTryotoWarehouseCode(): bool
    {
        return !empty($this->tryoto_warehouse_code);
    }

    /**
     * Check if branch is in a specific city.
     */
    public function isInCity(int $cityId): bool
    {
        return $this->city_id == $cityId;
    }

    /**
     * Check if branch is within a radius from a point.
     */
    public function isWithinRadius(float $lat, float $lng, float $radiusKm = 50): bool
    {
        if (!$this->has_coordinates) {
            return false;
        }

        $earthRadius = 6371;
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

        return $distance <= $radiusKm;
    }

    /**
     * Get the count of items in this branch.
     */
    public function getItemsCount(): int
    {
        if ($this->relationLoaded('merchantItems')) {
            return $this->merchantItems->count();
        }
        return $this->merchantItems()->count();
    }

    /**
     * Get the count of active items in this branch.
     */
    public function getActiveItemsCount(): int
    {
        if ($this->relationLoaded('merchantItems')) {
            return $this->merchantItems->where('status', 1)->count();
        }
        return $this->activeMerchantItems()->count();
    }
}
