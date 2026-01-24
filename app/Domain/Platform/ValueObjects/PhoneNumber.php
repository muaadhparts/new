<?php

namespace App\Domain\Platform\ValueObjects;

use InvalidArgumentException;

/**
 * PhoneNumber Value Object
 *
 * Immutable representation of a phone number.
 * Handles normalization and formatting.
 */
final class PhoneNumber
{
    private string $value;
    private string $normalized;
    private ?string $countryCode;

    private function __construct(string $value, ?string $countryCode = null)
    {
        $trimmed = trim($value);

        if (empty($trimmed)) {
            throw new InvalidArgumentException('Phone number cannot be empty');
        }

        // Remove all non-numeric characters except +
        $normalized = preg_replace('/[^0-9+]/', '', $trimmed);

        if (strlen($normalized) < 7) {
            throw new InvalidArgumentException('Phone number is too short');
        }

        $this->value = $trimmed;
        $this->normalized = $normalized;
        $this->countryCode = $countryCode;
    }

    /**
     * Create from string
     */
    public static function from(string $value, ?string $countryCode = null): self
    {
        return new self($value, $countryCode);
    }

    /**
     * Try to create (returns null on failure)
     */
    public static function tryFrom(string $value): ?self
    {
        try {
            return new self($value);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Create Saudi phone number
     */
    public static function saudi(string $value): self
    {
        $normalized = preg_replace('/[^0-9]/', '', $value);

        // Convert 05xxxxxxxx to +9665xxxxxxxx
        if (str_starts_with($normalized, '05') && strlen($normalized) === 10) {
            $normalized = '+966' . substr($normalized, 1);
        }
        // Convert 5xxxxxxxx to +9665xxxxxxxx
        elseif (str_starts_with($normalized, '5') && strlen($normalized) === 9) {
            $normalized = '+966' . $normalized;
        }
        // Convert 9665xxxxxxxx to +9665xxxxxxxx
        elseif (str_starts_with($normalized, '966') && strlen($normalized) === 12) {
            $normalized = '+' . $normalized;
        }

        return new self($normalized, '+966');
    }

    /**
     * Get original value
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Get normalized value
     */
    public function normalized(): string
    {
        return $this->normalized;
    }

    /**
     * Get country code
     */
    public function countryCode(): ?string
    {
        return $this->countryCode;
    }

    /**
     * Check if has country code
     */
    public function hasCountryCode(): bool
    {
        return str_starts_with($this->normalized, '+');
    }

    /**
     * Get local number (without country code)
     */
    public function localNumber(): string
    {
        if (!$this->hasCountryCode()) {
            return $this->normalized;
        }

        // Remove + and country code (assume 2-3 digits)
        return preg_replace('/^\+\d{1,3}/', '', $this->normalized);
    }

    /**
     * Format for display
     */
    public function formatted(): string
    {
        $local = $this->localNumber();
        $len = strlen($local);

        // Saudi format: 5XX XXX XXXX
        if ($len === 9 && $this->countryCode === '+966') {
            return sprintf(
                '+966 %s %s %s',
                substr($local, 0, 2),
                substr($local, 2, 3),
                substr($local, 5)
            );
        }

        // Generic format: add spaces every 3 digits
        return $this->normalized;
    }

    /**
     * Get WhatsApp link
     */
    public function whatsappLink(): string
    {
        $number = preg_replace('/[^0-9]/', '', $this->normalized);
        return "https://wa.me/{$number}";
    }

    /**
     * Get tel: link
     */
    public function telLink(): string
    {
        return "tel:{$this->normalized}";
    }

    /**
     * Check if equals another phone
     */
    public function equals(PhoneNumber $other): bool
    {
        return $this->normalized === $other->normalized;
    }

    /**
     * Check if is Saudi number
     */
    public function isSaudi(): bool
    {
        return str_starts_with($this->normalized, '+966')
            || str_starts_with($this->normalized, '966')
            || str_starts_with($this->normalized, '05');
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return $this->normalized;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'normalized' => $this->normalized,
            'formatted' => $this->formatted(),
            'country_code' => $this->countryCode,
        ];
    }
}
