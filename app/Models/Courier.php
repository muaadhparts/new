<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Courier extends Authenticatable
{
    protected $table = 'couriers';

    protected $fillable = [
        'name', 'photo', 'address', 'phone',
        'email', 'password', 'email_verify', 'email_verified', 'email_token',
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

    public function deliveries()
    {
        return $this->hasMany(DeliveryCourier::class);
    }

    public function serviceAreas()
    {
        return $this->hasMany(CourierServiceArea::class);
    }

    /**
     * Get accounting party for this courier
     */
    public function accountParty()
    {
        return $this->hasOne(AccountParty::class, 'reference_id')
            ->where('party_type', 'courier')
            ->where('reference_type', 'Courier');
    }

    public function getCurrentBalance(): float
    {
        return (float) $this->balance;
    }

    public function isInDebt(): bool
    {
        return $this->balance < 0;
    }

    public function hasCredit(): bool
    {
        return $this->balance > 0;
    }

    public function recordCodCollection(float $amount): void
    {
        $this->balance -= $amount;
        $this->total_collected += $amount;
        $this->save();
    }

    public function recordDeliveryFeeEarned(float $amount): void
    {
        $this->balance += $amount;
        $this->total_fees_earned += $amount;
        $this->save();
    }

    public function recordSettlementPaid(float $amount): void
    {
        $this->balance += $amount;
        $this->total_delivered += $amount;
        $this->save();
    }

    public function recordSettlementReceived(float $amount): void
    {
        $this->balance -= $amount;
        $this->save();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeInDebt($query)
    {
        return $query->where('balance', '<', 0);
    }

    public function scopeHasCredit($query)
    {
        return $query->where('balance', '>', 0);
    }

    public function servesCity($cityId): bool
    {
        return $this->serviceAreas()->where('city_id', $cityId)->exists();
    }

    public function getDeliveryFeeForCity($cityId): ?float
    {
        $serviceArea = $this->serviceAreas()->where('city_id', $cityId)->first();
        return $serviceArea ? (float) $serviceArea->price : null;
    }
}
