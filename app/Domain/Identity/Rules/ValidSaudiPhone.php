<?php

namespace App\Domain\Identity\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valid Saudi Phone Rule
 *
 * Validates Saudi Arabian phone numbers.
 */
class ValidSaudiPhone implements ValidationRule
{
    /**
     * Whether to allow international format
     */
    protected bool $allowInternational = true;

    /**
     * Create a new rule instance.
     */
    public function __construct(bool $allowInternational = true)
    {
        $this->allowInternational = $allowInternational;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail(__('validation.phone.must_be_string'));
            return;
        }

        $phone = $this->normalize($value);

        // Saudi mobile numbers start with 05 (10 digits)
        // or +966 5 (12 digits with country code)
        $patterns = [
            '/^05[0-9]{8}$/',                    // Local format: 05XXXXXXXX
            '/^5[0-9]{8}$/',                     // Without leading 0: 5XXXXXXXX
        ];

        if ($this->allowInternational) {
            $patterns[] = '/^9665[0-9]{8}$/';    // International: 9665XXXXXXXX
            $patterns[] = '/^\+9665[0-9]{8}$/';  // International with +: +9665XXXXXXXX
            $patterns[] = '/^009665[0-9]{8}$/';  // International with 00: 009665XXXXXXXX
        }

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $phone)) {
                return; // Valid
            }
        }

        $fail(__('validation.phone.invalid_saudi_format'));
    }

    /**
     * Normalize phone number
     */
    protected function normalize(string $phone): string
    {
        // Remove spaces, dashes, and parentheses
        return preg_replace('/[\s\-\(\)]/', '', $phone);
    }
}
