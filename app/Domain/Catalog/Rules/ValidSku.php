<?php

namespace App\Domain\Catalog\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valid SKU Rule
 *
 * Validates that the SKU follows the correct format.
 */
class ValidSku implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail(__('validation.sku.must_be_string'));
            return;
        }

        // SKU format: PREFIX-XXX-XXXXXX (e.g., SKU-001-ABC123)
        if (!preg_match('/^[A-Z]{2,5}-[A-Z0-9]{2,5}-[A-Z0-9]{4,10}$/i', $value)) {
            $fail(__('validation.sku.invalid_format'));
            return;
        }

        // Check length
        if (strlen($value) < 10 || strlen($value) > 25) {
            $fail(__('validation.sku.invalid_length'));
        }
    }
}
