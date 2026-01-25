<?php

namespace App\Domain\Merchant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Identity\Models\User;

/**
 * MerchantCommission Model - Commission settings per merchant
 *
 * Domain: Merchant
 * Table: merchant_commissions
 *
 * @property int $id
 * @property int $user_id
 * @property float $fixed_commission
 * @property float $percentage_commission
 * @property bool $is_active
 * @property string|null $notes
 */
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

    // =========================================================
    // COMMISSION METHODS
    // =========================================================

    public function calculateCommission(float $price): float
    {
        if (!$this->is_active) {
            return 0;
        }

        $percentageAmount = $price * ($this->percentage_commission / 100);
        return $this->fixed_commission + $percentageAmount;
    }

    public function getPriceWithCommission(float $price): float
    {
        return $price + $this->calculateCommission($price);
    }

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

    // =========================================================
    // SCOPES
    // =========================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
