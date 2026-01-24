<?php

namespace App\Domain\Accounting\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Balance Cast
 *
 * Handles account balance with precision.
 */
class BalanceCast implements CastsAttributes
{
    /**
     * Decimal precision.
     */
    protected int $precision;

    /**
     * Create a new cast instance.
     */
    public function __construct(int $precision = 2)
    {
        $this->precision = $precision;
    }

    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): float
    {
        if ($value === null) {
            return 0.00;
        }

        return round((float) $value, $this->precision);
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): float
    {
        if ($value === null) {
            return 0.00;
        }

        return round((float) $value, $this->precision);
    }

    /**
     * Format balance for display.
     */
    public static function format(float $balance, string $currency = 'SAR'): string
    {
        return number_format($balance, 2) . ' ' . $currency;
    }

    /**
     * Check if balance is sufficient.
     */
    public static function isSufficient(float $balance, float $amount): bool
    {
        return $balance >= $amount;
    }
}
