<?php

namespace App\Domain\Platform\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valid JSON Rule
 *
 * Validates JSON string with optional schema.
 */
class ValidJson implements ValidationRule
{
    /**
     * Required keys
     */
    protected array $requiredKeys;

    /**
     * Create a new rule instance.
     */
    public function __construct(array $requiredKeys = [])
    {
        $this->requiredKeys = $requiredKeys;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail(__('validation.json.must_be_string'));
            return;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $fail(__('validation.json.invalid'));
            return;
        }

        // Check required keys
        if (!empty($this->requiredKeys) && is_array($decoded)) {
            foreach ($this->requiredKeys as $key) {
                if (!array_key_exists($key, $decoded)) {
                    $fail(__('validation.json.missing_key', ['key' => $key]));
                }
            }
        }
    }
}
