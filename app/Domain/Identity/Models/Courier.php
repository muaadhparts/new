<?php

namespace App\Domain\Identity\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\DeliveryCourier;
use App\Models\CourierServiceArea;
use App\Models\AccountParty;

/**
 * Courier Model - Delivery couriers
 *
 * Domain: Identity
 * Table: couriers
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $address
 * @property int $status
 * @property float $balance
 * @property float $total_collected
 * @property float $total_delivered
 * @property float $total_fees_earned
 */
class Courier extends Authenticatable
{
    protected $table = 'couriers';

    protected $fillable = [
        'name', 'photo', 'address', 'phone',
        'email', 'password', 'email_verify', 'email_token',
        'status', 'balance', 'total_collected', 'total_delivered', 'total_fees_earned'
    ];

    protected $hidden = [
        'password', 'remember_token'
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_collected' => 'decimal:2',
        'total_delivered' => 'decimal:2',
        'total_fees_earned' => 'decimal:2',
    ];

    // =========================================================
    // RELATIONS
    // =========================================================

    /**
     * Get courier deliveries
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(DeliveryCourier::class);
    }

    /**
     * Get courier service areas
     */
    public function serviceAreas(): HasMany
    {
        return $this->hasMany(CourierServiceArea::class);
    }

    /**
     * Get accounting party for this courier
     */
    public function accountParty(): HasOne
    {
        return $this->hasOne(AccountParty::class, 'reference_id')
            ->where('party_type', 'courier')
            ->where('reference_type', 'Courier');
    }

    // =========================================================
    // BALANCE METHODS
    // =========================================================

    /**
     * Get current balance
     */
    public function getCurrentBalance(): float
    {
        return (float) $this->balance;
    }

    /**
     * Check if courier is in debt
     */
    public function isInDebt(): bool
    {
        return $this->balance < 0;
    }

    /**
     * Check if courier has credit
     */
    public function hasCredit(): bool
    {
        return $this->balance > 0;
    }

    /**
     * Record COD collection
     */
    public function recordCodCollection(float $amount): void
    {
        $this->balance -= $amount;
        $this->total_collected += $amount;
        $this->save();
    }

    /**
     * Record delivery fee earned
     */
    public function recordDeliveryFeeEarned(float $amount): void
    {
        $this->balance += $amount;
        $this->total_fees_earned += $amount;
        $this->save();
    }

    /**
     * Record settlement paid
     */
    public function recordSettlementPaid(float $amount): void
    {
        $this->balance += $amount;
        $this->total_delivered += $amount;
        $this->save();
    }

    /**
     * Record settlement received
     */
    public function recordSettlementReceived(float $amount): void
    {
        $this->balance -= $amount;
        $this->save();
    }

    // =========================================================
    // SCOPES
    // =========================================================

    /**
     * Scope: Active couriers only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope: Couriers in debt
     */
    public function scopeInDebt($query)
    {
        return $query->where('balance', '<', 0);
    }

    /**
     * Scope: Couriers with credit
     */
    public function scopeHasCredit($query)
    {
        return $query->where('balance', '>', 0);
    }

    // =========================================================
    // SERVICE AREA METHODS
    // =========================================================

    /**
     * Check if courier serves a city
     */
    public function servesCity($cityId): bool
    {
        return $this->serviceAreas()->where('city_id', $cityId)->exists();
    }

    /**
     * Get delivery fee for a city
     */
    public function getDeliveryFeeForCity($cityId): ?float
    {
        $serviceArea = $this->serviceAreas()->where('city_id', $cityId)->first();
        return $serviceArea ? (float) $serviceArea->price : null;
    }
}
