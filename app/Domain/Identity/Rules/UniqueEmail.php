<?php

namespace App\Domain\Identity\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Domain\Identity\Models\User;

/**
 * Unique Email Rule
 *
 * Validates that email is unique, with optional exclusion.
 */
class UniqueEmail implements ValidationRule
{
    /**
     * User ID to exclude
     */
    protected ?int $excludeId = null;

    /**
     * Create a new rule instance.
     */
    public function __construct(?int $excludeId = null)
    {
        $this->excludeId = $excludeId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail(__('validation.email.must_be_string'));
            return;
        }

        $email = strtolower(trim($value));

        $query = User::where('email', $email);

        if ($this->excludeId) {
            $query->where('id', '!=', $this->excludeId);
        }

        if ($query->exists()) {
            $fail(__('validation.email.already_taken'));
        }
    }

    /**
     * Exclude a user ID from the check
     */
    public function except(int $userId): self
    {
        $this->excludeId = $userId;
        return $this;
    }
}
