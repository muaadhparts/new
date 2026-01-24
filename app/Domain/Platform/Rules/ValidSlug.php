<?php

namespace App\Domain\Platform\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valid Slug Rule
 *
 * Validates URL-friendly slugs.
 */
class ValidSlug implements ValidationRule
{
    /**
     * Minimum length
     */
    protected int $minLength;

    /**
     * Maximum length
     */
    protected int $maxLength;

    /**
     * Create a new rule instance.
     */
    public function __construct(int $minLength = 3, int $maxLength = 100)
    {
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail(__('validation.slug.must_be_string'));
            return;
        }

        $slug = trim($value);

        // Check length
        if (strlen($slug) < $this->minLength) {
            $fail(__('validation.slug.too_short', ['min' => $this->minLength]));
            return;
        }

        if (strlen($slug) > $this->maxLength) {
            $fail(__('validation.slug.too_long', ['max' => $this->maxLength]));
            return;
        }

        // Valid slug pattern: lowercase letters, numbers, hyphens
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $fail(__('validation.slug.invalid_format'));
        }
    }
}
