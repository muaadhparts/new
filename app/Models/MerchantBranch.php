<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MerchantBranch - Merchant branch/warehouse locations
 *
 * Each merchant can have multiple branches. Each branch is a complete
 * operational entity with its own:
 * - Location/address
 * - Inventory (via merchant_items)
 * - Shipping origin
 * - Warehouse code (for Tryoto integration)
 *
 * The cart and checkout are branch-scoped, meaning:
 * - Items from different branches = different checkout
 * - Same merchant, different branches = separate orders
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
        'latitude' => 'float',
        'longitude' => 'float',
        'status' => 'integer',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * The merchant (user) who owns this branch
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user() - more semantic
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The city where this branch is located
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    /**
     * The country where this branch is located
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * All merchant items in this branch
     */
    public function merchantItems(): HasMany
    {
        return $this->hasMany(MerchantItem::class, 'merchant_branch_id');
    }

    /**
     * Delivery couriers assigned to this branch
     */
    public function deliveryCouriers(): HasMany
    {
        return $this->hasMany(DeliveryCourier::class, 'merchant_branch_id');
    }

    /**
     * Merchant purchases from this branch
     */
    public function merchantPurchases(): HasMany
    {
        return $this->hasMany(MerchantPurchase::class, 'merchant_branch_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Active branches only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Branches for a specific merchant
     */
    public function scopeByMerchant($query, $merchantId)
    {
        return $query->where('user_id', $merchantId);
    }

    /**
     * Branches in a specific city
     */
    public function scopeByCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Check if branch is in a specific city
     */
    public function isInCity($cityId): bool
    {
        return $this->city_id == $cityId;
    }

    /**
     * Get display name (branch_name or warehouse_name)
     */
    public function getDisplayName(): string
    {
        return $this->branch_name ?: $this->warehouse_name ?: __('Branch #') . $this->id;
    }

    /**
     * Get localized display name
     */
    public function getLocalizedDisplayName(): string
    {
        // For now, return the display name. Can be extended for translations
        return $this->getDisplayName();
    }

    /**
     * Get city name
     */
    public function getCityName(): ?string
    {
        return $this->city?->city_name;
    }

    /**
     * Get full address with city
     */
    public function getFullAddress(): string
    {
        $parts = [];

        if ($this->location) {
            $parts[] = $this->location;
        }

        if ($cityName = $this->getCityName()) {
            $parts[] = $cityName;
        }

        return implode(', ', $parts);
    }

    /**
     * Scope: Find branches within radius of given coordinates
     * Uses Haversine formula for accurate distance calculation
     */
    public function scopeWithinRadius($query, float $lat, float $lng, int $radiusKm = 20)
    {
        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("
                merchant_branches.*,
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
     * Check if this branch is within radius of given coordinates
     */
    public function isWithinRadius(float $lat, float $lng, ?int $radiusKm = null): bool
    {
        if (!$this->latitude || !$this->longitude) {
            return false;
        }

        $radius = $radiusKm ?? 20;

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

    /**
     * Check if branch has Tryoto warehouse code configured
     */
    public function hasTryotoWarehouseCode(): bool
    {
        return !empty($this->tryoto_warehouse_code);
    }
}
