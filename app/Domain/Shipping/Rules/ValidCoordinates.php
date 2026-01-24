<?php

namespace App\Domain\Shipping\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valid Coordinates Rule
 *
 * Validates latitude and longitude coordinates.
 */
class ValidCoordinates implements ValidationRule
{
    /**
     * Coordinate type (latitude or longitude)
     */
    protected string $type;

    /**
     * Create a new rule instance.
     */
    public function __construct(string $type = 'latitude')
    {
        $this->type = $type;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail(__('validation.coordinates.must_be_numeric'));
            return;
        }

        $coordinate = (float) $value;

        if ($this->type === 'latitude') {
            // Latitude must be between -90 and 90
            if ($coordinate < -90 || $coordinate > 90) {
                $fail(__('validation.coordinates.invalid_latitude'));
            }
        } else {
            // Longitude must be between -180 and 180
            if ($coordinate < -180 || $coordinate > 180) {
                $fail(__('validation.coordinates.invalid_longitude'));
            }
        }
    }

    /**
     * Create latitude rule
     */
    public static function latitude(): self
    {
        return new self('latitude');
    }

    /**
     * Create longitude rule
     */
    public static function longitude(): self
    {
        return new self('longitude');
    }
}
