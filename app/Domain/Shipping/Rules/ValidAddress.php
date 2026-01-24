<?php

namespace App\Domain\Shipping\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valid Address Rule
 *
 * Validates that the address is complete and valid.
 */
class ValidAddress implements ValidationRule
{
    /**
     * Minimum street length
     */
    protected int $minStreetLength = 5;

    /**
     * Maximum street length
     */
    protected int $maxStreetLength = 255;

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail(__('validation.address.must_be_string'));
            return;
        }

        $address = trim($value);

        if (strlen($address) < $this->minStreetLength) {
            $fail(__('validation.address.too_short', ['min' => $this->minStreetLength]));
            return;
        }

        if (strlen($address) > $this->maxStreetLength) {
            $fail(__('validation.address.too_long', ['max' => $this->maxStreetLength]));
            return;
        }

        // Check for suspicious patterns (PO Box only)
        if (preg_match('/^(p\.?o\.?\s*box|صندوق\s*بريد)/i', $address)) {
            $fail(__('validation.address.no_po_box'));
            return;
        }

        // Must contain some alphanumeric characters
        if (!preg_match('/[A-Za-z0-9\x{0600}-\x{06FF}]/u', $address)) {
            $fail(__('validation.address.invalid_format'));
        }
    }
}
