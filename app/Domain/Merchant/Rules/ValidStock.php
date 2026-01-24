<?php

namespace App\Domain\Merchant\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valid Stock Rule
 *
 * Validates that the stock quantity is valid.
 */
class ValidStock implements ValidationRule
{
    /**
     * Minimum stock
     */
    protected int $min = 0;

    /**
     * Maximum stock
     */
    protected int $max = 999999;

    /**
     * Create a new rule instance.
     */
    public function __construct(int $min = 0, int $max = 999999)
    {
        $this->min = $min;
        $this->max = $max;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail(__('validation.stock.must_be_numeric'));
            return;
        }

        $stock = (int) $value;

        if ($stock < $this->min) {
            $fail(__('validation.stock.too_low', ['min' => $this->min]));
            return;
        }

        if ($stock > $this->max) {
            $fail(__('validation.stock.too_high', ['max' => $this->max]));
            return;
        }

        // Must be a whole number
        if ($stock != $value) {
            $fail(__('validation.stock.must_be_integer'));
        }
    }
}
