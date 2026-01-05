<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantCommission extends Model
{
    protected $fillable = [
        'user_id',
        'fixed_commission',
        'percentage_commission',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'fixed_commission' => 'decimal:2',
        'percentage_commission' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the merchant (user) for this commission setting.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user() - merchant relationship
     */
    public function merchant()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Calculate commission for a given price.
     *
     * @param float $price Base price
     * @return float Commission amount
     */
    public function calculateCommission(float $price): float
    {
        if (!$this->is_active) {
            return 0;
        }

        $percentageAmount = $price * ($this->percentage_commission / 100);
        return $this->fixed_commission + $percentageAmount;
    }

    /**
     * Get price with commission applied.
     *
     * @param float $price Base price
     * @return float Price with commission
     */
    public function getPriceWithCommission(float $price): float
    {
        return $price + $this->calculateCommission($price);
    }

    /**
     * Get commission settings for a merchant, or create default if not exists.
     *
     * @param int $userId Merchant user ID
     * @return self
     */
    public static function getOrCreateForMerchant(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'fixed_commission' => 0,
                'percentage_commission' => 0,
                'is_active' => true,
            ]
        );
    }

    /**
     * Scope to get only active commission settings.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
