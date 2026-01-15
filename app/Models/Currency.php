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
 * Currency Model
 *
 * Manages currency configurations for the MUAADH EPC multi-merchant platform.
 * Supports multiple currencies with exchange rate calculations for auto parts pricing.
 *
 * @property int $id
 * @property string $name Currency name (e.g., SAR, USD, EUR)
 * @property string $sign Currency symbol (e.g., ر.س, $, €)
 * @property float $value Exchange rate relative to base currency
 */
class Currency extends Model
{
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
     * Convert amount from this currency to base currency.
     *
     * @param float $amount
     * @return float
     */
    public function toBaseCurrency(float $amount): float
    {
        return $this->value > 0 ? $amount / $this->value : $amount;
    }

    /**
     * Convert amount from base currency to this currency.
     *
     * @param float $amount
     * @return float
     */
    public function fromBaseCurrency(float $amount): float
    {
        return $amount * $this->value;
    }

    /**
     * Format amount with currency sign.
     *
     * @param float $amount
     * @return string
     */
    public function formatAmount(float $amount): string
    {
        return $this->sign . ' ' . number_format($amount, 2);
    }
}
