<?php

namespace App\Domain\Commerce\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valid Quantity Rule
 *
 * Validates cart item quantity.
 */
class ValidQuantity implements ValidationRule
{
    /**
     * Minimum quantity
     */
    protected int $min = 1;

    /**
     * Maximum quantity
     */
    protected int $max = 100;

    /**
     * Create a new rule instance.
     */
    public function __construct(int $min = 1, int $max = 100)
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
            $fail(__('validation.quantity.must_be_numeric'));
            return;
        }

        $quantity = (int) $value;

        if ($quantity < $this->min) {
            $fail(__('validation.quantity.too_low', ['min' => $this->min]));
            return;
        }

        if ($quantity > $this->max) {
            $fail(__('validation.quantity.too_high', ['max' => $this->max]));
            return;
        }

        // Must be a whole number
        if ($quantity != $value) {
            $fail(__('validation.quantity.must_be_integer'));
        }
    }
}
