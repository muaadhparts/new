<?php

namespace App\Domain\Identity\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valid Password Rule
 *
 * Validates password strength requirements.
 */
class ValidPassword implements ValidationRule
{
    /**
     * Minimum length
     */
    protected int $minLength = 8;

    /**
     * Require uppercase
     */
    protected bool $requireUppercase = true;

    /**
     * Require lowercase
     */
    protected bool $requireLowercase = true;

    /**
     * Require numbers
     */
    protected bool $requireNumbers = true;

    /**
     * Require special characters
     */
    protected bool $requireSpecial = false;

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail(__('validation.password.must_be_string'));
            return;
        }

        if (strlen($value) < $this->minLength) {
            $fail(__('validation.password.min_length', ['min' => $this->minLength]));
            return;
        }

        if ($this->requireUppercase && !preg_match('/[A-Z]/', $value)) {
            $fail(__('validation.password.require_uppercase'));
            return;
        }

        if ($this->requireLowercase && !preg_match('/[a-z]/', $value)) {
            $fail(__('validation.password.require_lowercase'));
            return;
        }

        if ($this->requireNumbers && !preg_match('/[0-9]/', $value)) {
            $fail(__('validation.password.require_numbers'));
            return;
        }

        if ($this->requireSpecial && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $value)) {
            $fail(__('validation.password.require_special'));
        }
    }

    /**
     * Set minimum length
     */
    public function min(int $length): self
    {
        $this->minLength = $length;
        return $this;
    }

    /**
     * Require special characters
     */
    public function withSpecialCharacters(): self
    {
        $this->requireSpecial = true;
        return $this;
    }

    /**
     * Simple password (only length check)
     */
    public function simple(): self
    {
        $this->requireUppercase = false;
        $this->requireLowercase = false;
        $this->requireNumbers = false;
        $this->requireSpecial = false;
        return $this;
    }
}
