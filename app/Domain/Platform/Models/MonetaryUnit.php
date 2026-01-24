<?php

namespace App\Domain\Platform\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * MonetaryUnit Model
 *
 * Manages monetary unit configurations for multi-currency support.
 */
class MonetaryUnit extends Model
{
    protected $table = 'monetary_units';

    protected $fillable = ['name', 'sign', 'value', 'is_default'];

    public $timestamps = false;

    protected $casts = [
        'value' => 'decimal:4',
        'is_default' => 'boolean',
    ];

    /**
     * Convert amount from this monetary unit to base.
     */
    public function toBaseMonetaryUnit(float $amount): float
    {
        return $this->value > 0 ? $amount / $this->value : $amount;
    }

    /**
     * Convert amount from base to this monetary unit.
     */
    public function fromBaseMonetaryUnit(float $amount): float
    {
        return $amount * $this->value;
    }

    /**
     * Format amount with monetary unit sign.
     */
    public function formatAmount(float $amount): string
    {
        return $this->sign . ' ' . number_format($amount, 2);
    }

    /**
     * Get the default monetary unit.
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', 1)->first();
    }
}
