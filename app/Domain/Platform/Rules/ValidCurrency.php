<?php

namespace App\Domain\Platform\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Domain\Platform\Models\MonetaryUnit;

/**
 * Valid Currency Rule
 *
 * Validates that a currency code is valid and active.
 */
class ValidCurrency implements ValidationRule
{
    /**
     * Whether the currency must be active
     */
    protected bool $mustBeActive;

    /**
     * Create a new rule instance.
     */
    public function __construct(bool $mustBeActive = true)
    {
        $this->mustBeActive = $mustBeActive;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail(__('validation.currency.must_be_string'));
            return;
        }

        $code = strtoupper(trim($value));

        $query = MonetaryUnit::where('code', $code);

        if ($this->mustBeActive) {
            $query->where('status', 1);
        }

        if (!$query->exists()) {
            $fail(__('validation.currency.invalid'));
        }
    }
}
