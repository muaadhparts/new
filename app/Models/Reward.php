<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Reward extends Model
{
    protected $fillable = ['user_id', 'purchase_amount', 'reward', 'point_value'];
    public $timestamps = false;

    /**
     * Merchant relationship
     * user_id = 0 means platform/operator default
     * user_id > 0 means merchant-specific
     */
    public function merchant()
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    /**
     * Scope: Get rewards for a specific merchant (or platform default if none)
     */
    public function scopeForMerchant(Builder $query, int $merchantId): Builder
    {
        return $query->whereIn('user_id', [$merchantId, 0])
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$merchantId]);
    }

    /**
     * Scope: Platform-only rewards
     */
    public function scopePlatformOnly(Builder $query): Builder
    {
        return $query->where('user_id', 0);
    }

    /**
     * Scope: Merchant-only rewards
     */
    public function scopeMerchantOnly(Builder $query, int $merchantId): Builder
    {
        return $query->where('user_id', $merchantId);
    }

    /**
     * Check if this is a platform reward
     */
    public function isPlatformOwned(): bool
    {
        return $this->user_id === 0 || $this->user_id === null;
    }

    /**
     * Calculate points earned for a given subtotal amount (before tax/shipping)
     * Formula: floor(subtotal / purchase_amount) * reward
     *
     * Example: purchase_amount=100, reward=1
     * - 100 SAR = 1 point
     * - 250 SAR = 2 points
     * - 500 SAR = 5 points
     *
     * @param float $subtotal The purchase subtotal before tax/shipping
     * @param int $merchantId The merchant ID (0 for platform default)
     * @return array ['points' => int, 'point_value' => float, 'tier' => Reward|null]
     */
    public static function calculatePointsEarned(float $subtotal, int $merchantId = 0): array
    {
        // Get merchant's reward config, fall back to platform default
        $tier = self::forMerchant($merchantId)->first();

        if (!$tier || $tier->purchase_amount <= 0) {
            return ['points' => 0, 'point_value' => 1.00, 'tier' => null];
        }

        // Calculate: for every X amount, give Y points
        // floor(subtotal / purchase_amount) * reward
        $multiplier = floor($subtotal / $tier->purchase_amount);
        $points = (int) ($multiplier * $tier->reward);

        return [
            'points' => $points,
            'point_value' => (float) $tier->point_value,
            'tier' => $tier
        ];
    }

    /**
     * Calculate the monetary value of reward points
     *
     * @param int $points Number of points
     * @param int $merchantId The merchant ID to get point_value from
     * @return float The monetary value
     */
    public static function getPointsValue(int $points, int $merchantId = 0): float
    {
        // Get the point value from merchant's first tier, or platform default
        $tier = self::forMerchant($merchantId)->first();
        $pointValue = $tier ? (float) $tier->point_value : 1.00;

        return $points * $pointValue;
    }

    /**
     * Get point value for a merchant (or platform default)
     *
     * @param int $merchantId
     * @return float
     */
    public static function getMerchantPointValue(int $merchantId = 0): float
    {
        $tier = self::forMerchant($merchantId)->first();
        return $tier ? (float) $tier->point_value : 1.00;
    }

    /**
     * Check if merchant has custom reward tiers
     */
    public static function merchantHasRewards(int $merchantId): bool
    {
        return self::where('user_id', $merchantId)->exists();
    }
}
