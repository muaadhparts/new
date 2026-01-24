<?php

namespace App\Domain\Merchant\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valid Price Rule
 *
 * Validates that the price is valid and within acceptable range.
 */
class ValidPrice implements ValidationRule
{
    /**
     * Minimum price
     */
    protected float $min = 0.01;

    /**
     * Maximum price
     */
    protected float $max = 9999999.99;

    /**
     * Create a new rule instance.
     */
    public function __construct(float $min = 0.01, ?float $max = null)
    {
        $this->min = $min;
        if ($max !== null) {
            $this->max = $max;
        }
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail(__('validation.price.must_be_numeric'));
            return;
        }

        $price = (float) $value;

        if ($price < $this->min) {
            $fail(__('validation.price.too_low', ['min' => $this->min]));
            return;
        }

        if ($price > $this->max) {
            $fail(__('validation.price.too_high', ['max' => $this->max]));
            return;
        }

        // Check decimal places (max 2)
        if (floor($price * 100) != $price * 100) {
            $fail(__('validation.price.invalid_decimals'));
        }
    }
}
