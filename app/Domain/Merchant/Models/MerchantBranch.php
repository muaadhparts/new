<?php

namespace App\Domain\Merchant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domain\Identity\Models\User;
use App\Domain\Platform\Models\City;
use App\Domain\Platform\Models\Country;
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
        'latitude' => 'float',
        'longitude' => 'float',
        'status' => 'integer',
    ];

    // =========================================================
    // RELATIONS
    // =========================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function merchantItems(): HasMany
    {
        return $this->hasMany(MerchantItem::class, 'merchant_branch_id');
    }

    public function deliveryCouriers(): HasMany
    {
        return $this->hasMany(DeliveryCourier::class, 'merchant_branch_id');
    }

    public function merchantPurchases(): HasMany
    {
        return $this->hasMany(MerchantPurchase::class, 'merchant_branch_id');
    }

    // =========================================================
    // SCOPES
    // =========================================================

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

    // =========================================================
    // HELPERS
    // =========================================================

    public function isInCity($cityId): bool
    {
        return $this->city_id == $cityId;
    }

    public function getDisplayName(): string
    {
        return $this->branch_name ?: $this->warehouse_name ?: __('Branch #') . $this->id;
    }

    public function getLocalizedDisplayName(): string
    {
        return $this->getDisplayName();
    }

    public function getCityName(): ?string
    {
        return $this->city?->city_name;
    }

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

    public function isWithinRadius(float $lat, float $lng, ?int $radiusKm = null): bool
    {
        if (!$this->latitude || !$this->longitude) {
            return false;
        }

        $radius = $radiusKm ?? 20;

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

        return $distance <= $radius;
    }

    public function hasTryotoWarehouseCode(): bool
    {
        return !empty($this->tryoto_warehouse_code);
    }
}
