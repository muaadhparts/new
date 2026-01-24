<?php

namespace App\Domain\Catalog\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valid Part Number Rule
 *
 * Validates that the part number follows the correct format.
 */
class ValidPartNumber implements ValidationRule
{
    /**
     * Minimum length for part number
     */
    protected int $minLength = 3;

    /**
     * Maximum length for part number
     */
    protected int $maxLength = 50;

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail(__('validation.part_number.must_be_string'));
            return;
        }

        $normalized = $this->normalize($value);

        if (strlen($normalized) < $this->minLength) {
            $fail(__('validation.part_number.too_short', ['min' => $this->minLength]));
            return;
        }

        if (strlen($normalized) > $this->maxLength) {
            $fail(__('validation.part_number.too_long', ['max' => $this->maxLength]));
            return;
        }

        // Must contain at least one letter or number
        if (!preg_match('/[A-Za-z0-9]/', $normalized)) {
            $fail(__('validation.part_number.invalid_format'));
            return;
        }

        // Check for invalid characters
        if (preg_match('/[<>"\']/', $value)) {
            $fail(__('validation.part_number.invalid_characters'));
        }
    }

    /**
     * Normalize part number
     */
    protected function normalize(string $value): string
    {
        return preg_replace('/[\s\-]/', '', $value);
    }
}
