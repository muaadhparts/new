<?php
/**
 * MUAADH EPC - Multi-Merchant Auto Parts Catalog
 *
 * @package    MUAADH\Models
 * @author     MUAADH Development Team
 * @copyright  2024-2026 MUAADH EPC
 * @license    Proprietary
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * MonetaryUnit Model
 *
 * Manages monetary unit configurations for the MUAADH EPC multi-merchant platform.
 * Supports multiple monetary units with exchange rate calculations for auto parts pricing.
 *
 * @property int $id
 * @property string $name Monetary unit name (e.g., SAR, USD, EUR)
 * @property string $sign Monetary unit symbol (e.g., ر.س, $, €)
 * @property float $value Exchange rate relative to base monetary unit
 */
class MonetaryUnit extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'monetary_units';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['name', 'sign', 'value'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'decimal:4',
    ];

    /**
     * Convert amount from this monetary unit to base monetary unit.
     *
     * @param float $amount
     * @return float
     */
    public function toBaseMonetaryUnit(float $amount): float
    {
        return $this->value > 0 ? $amount / $this->value : $amount;
    }

    /**
     * Convert amount from base monetary unit to this monetary unit.
     *
     * @param float $amount
     * @return float
     */
    public function fromBaseMonetaryUnit(float $amount): float
    {
        return $amount * $this->value;
    }

    /**
     * Format amount with monetary unit sign.
     *
     * @param float $amount
     * @return string
     */
    public function formatAmount(float $amount): string
    {
        return $this->sign . ' ' . number_format($amount, 2);
    }
}
