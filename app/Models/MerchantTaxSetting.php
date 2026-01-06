<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function merchant()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByMerchant($query, $merchantId)
    {
        return $query->where('user_id', $merchantId);
    }
}
