<?php

namespace App\Domain\Merchant\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Price Cast
 *
 * Handles price values with decimal precision.
 */
class PriceCast implements CastsAttributes
{
    /**
     * Decimal places
     */
    protected int $decimals;

    /**
     * Create a new cast instance.
     */
    public function __construct(int $decimals = 2)
    {
        $this->decimals = $decimals;
    }

    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?float
    {
        if ($value === null) {
            return null;
        }

        return round((float) $value, $this->decimals);
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?float
    {
        if ($value === null) {
            return null;
        }

        // Handle string with currency symbols
        if (is_string($value)) {
            $value = preg_replace('/[^\d.]/', '', $value);
        }

        $price = (float) $value;

        // Ensure non-negative
        if ($price < 0) {
            $price = 0;
        }

        return round($price, $this->decimals);
    }

    /**
     * Format price for display
     */
    public static function format(float $price, string $currency = 'SAR'): string
    {
        return number_format($price, 2) . ' ' . $currency;
    }

    /**
     * Calculate discounted price
     */
    public static function applyDiscount(float $price, float $discount, string $type = 'percent'): float
    {
        if ($type === 'percent') {
            return $price - ($price * $discount / 100);
        }

        return max(0, $price - $discount);
    }
}
