<?php

namespace App\Domain\Merchant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Identity\Models\User;

/**
 * MerchantTaxSetting Model - Tax settings per merchant
 *
 * Domain: Merchant
 * Table: merchant_tax_settings
 *
 * @property int $id
 * @property int $user_id
 * @property float $tax_rate
 * @property string|null $tax_number
 * @property string|null $tax_name
 * @property bool $is_active
 */
class MerchantTaxSetting extends Model
{
    protected $table = 'merchant_tax_settings';

    protected $fillable = [
        'user_id',
        'tax_rate',
        'tax_number',
        'tax_name',
        'is_active',
    ];

    protected $casts = [
        'tax_rate' => 'decimal:2',
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
    // TAX METHODS
    // =========================================================

    public function calculateTax(float $amount): float
    {
        if (!$this->is_active || $this->tax_rate <= 0) {
            return 0;
        }

        return round($amount * ($this->tax_rate / 100), 2);
    }

    public function getPriceWithTax(float $amount): float
    {
        return $amount + $this->calculateTax($amount);
    }

    public static function getOrCreateForMerchant(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'tax_rate' => 0,
                'tax_number' => null,
                'tax_name' => 'VAT',
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

    public function scopeByMerchant($query, $merchantId)
    {
        return $query->where('user_id', $merchantId);
    }
}
