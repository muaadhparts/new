<?php

namespace App\Domain\Catalog\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valid Rating Rule
 *
 * Validates that the rating is within acceptable range.
 */
class ValidRating implements ValidationRule
{
    /**
     * Minimum rating
     */
    protected int $min = 1;

    /**
     * Maximum rating
     */
    protected int $max = 5;

    /**
     * Create a new rule instance.
     */
    public function __construct(int $min = 1, int $max = 5)
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
            $fail(__('validation.rating.must_be_numeric'));
            return;
        }

        $rating = (int) $value;

        if ($rating < $this->min || $rating > $this->max) {
            $fail(__('validation.rating.out_of_range', [
                'min' => $this->min,
                'max' => $this->max,
            ]));
        }
    }
}
