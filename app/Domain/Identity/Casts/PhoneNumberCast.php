<?php

namespace App\Domain\Identity\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Phone Number Cast
 *
 * Normalizes and formats Saudi phone numbers.
 */
class PhoneNumberCast implements CastsAttributes
{
    /**
     * Country code
     */
    protected string $countryCode;

    /**
     * Create a new cast instance.
     */
    public function __construct(string $countryCode = '966')
    {
        $this->countryCode = $countryCode;
    }

    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->normalize($value);
    }

    /**
     * Normalize phone number to standard format
     */
    protected function normalize(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading zeros
        $phone = ltrim($phone, '0');

        // Remove country code if present
        if (str_starts_with($phone, $this->countryCode)) {
            $phone = substr($phone, strlen($this->countryCode));
        }

        // For Saudi numbers, ensure starts with 5
        if ($this->countryCode === '966' && !str_starts_with($phone, '5')) {
            $phone = '5' . $phone;
        }

        return $phone;
    }

    /**
     * Format phone number for display
     */
    public function formatForDisplay(string $phone): string
    {
        $normalized = $this->normalize($phone);

        // Saudi format: 05X XXX XXXX
        if (strlen($normalized) === 9) {
            return sprintf('0%s %s %s',
                substr($normalized, 0, 2),
                substr($normalized, 2, 3),
                substr($normalized, 5, 4)
            );
        }

        return '0' . $normalized;
    }

    /**
     * Format with international code
     */
    public function formatInternational(string $phone): string
    {
        $normalized = $this->normalize($phone);
        return '+' . $this->countryCode . $normalized;
    }
}
