<?php

namespace App\Domain\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shipping Model - Shipping methods and providers
 *
 * Domain: Shipping
 * Table: shippings
 *
 * Ownership Logic:
 * | user_id | operator    | Meaning                                     |
 * |---------|-------------|---------------------------------------------|
 * | 0       | 0           | Disabled - not visible to anyone            |
 * | 0       | merchant_id | Platform shipping enabled for specific merchant |
 * | merchant_id | 0       | Merchant's own shipping method              |
 *
 * @property int $id
 * @property int $user_id
 * @property int $operator
 * @property string $integration_type
 * @property string|null $provider
 * @property string $name
 * @property string|null $subname
 * @property float $price
 * @property float|null $free_above
 * @property int $status
 */
class Shipping extends Model
{
    protected $table = 'shippings';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'operator',
        'integration_type', // none, manual, api
        'provider',
        'name',
        'subname',
        'price',
        'free_above',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'free_above' => 'decimal:2',
        'status' => 'integer',
    ];

    // =========================================================
    // SCOPES
    // =========================================================

    /**
     * Get shipping methods available for a merchant
     *
     * Priority: Merchant's own methods first, then platform methods
     */
    public function scopeForMerchant(Builder $query, int $merchantId): Builder
    {
        return $query
            ->where('status', 1)
            ->where(function ($q) use ($merchantId) {
                // 1. Merchant's own shipping (user_id = merchantId)
                $q->where('user_id', $merchantId)
                // 2. Or platform shipping enabled for this merchant (user_id = 0 AND operator = merchantId)
                ->orWhere(function ($q2) use ($merchantId) {
                    $q2->where('user_id', 0)
                       ->where('operator', $merchantId);
                });
            })
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$merchantId]);
    }

    /**
     * Platform-owned shipping methods only (for admin)
     */
    public function scopePlatformOnly(Builder $query): Builder
    {
        return $query->where('user_id', 0);
    }

    /**
     * Active shipping methods
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    // =========================================================
    // OWNERSHIP METHODS
    // =========================================================

    /**
     * Is this a platform-owned shipping method?
     */
    public function isPlatformOwned(): bool
    {
        return $this->user_id === 0 || $this->user_id === null;
    }

    /**
     * Is this a merchant-owned shipping method?
     */
    public function isMerchantOwned(int $merchantId): bool
    {
        return $this->user_id > 0 && $this->user_id === $merchantId;
    }

    /**
     * Is this shipping method enabled for a specific merchant?
     */
    public function isEnabledForMerchant(int $merchantId): bool
    {
        // Merchant's own shipping
        if ($this->user_id === $merchantId) {
            return true;
        }

        // Platform shipping enabled for this merchant
        if ($this->user_id === 0 && $this->operator === $merchantId) {
            return true;
        }

        return false;
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Check if shipping is free above certain amount
     */
    public function isFreeAbove(float $amount): bool
    {
        if (!$this->free_above || $this->free_above <= 0) {
            return false;
        }

        return $amount >= $this->free_above;
    }

    /**
     * Get price for an order amount (considering free_above)
     */
    public function getPriceForAmount(float $amount): float
    {
        if ($this->isFreeAbove($amount)) {
            return 0.0;
        }

        return (float) $this->price;
    }

    /**
     * Is this an API-integrated shipping?
     */
    public function isApiIntegration(): bool
    {
        return $this->integration_type === 'api';
    }

    /**
     * Is this a manual shipping?
     */
    public function isManualIntegration(): bool
    {
        return $this->integration_type === 'manual' || $this->integration_type === 'none';
    }
}
