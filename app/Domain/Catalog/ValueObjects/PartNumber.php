<?php

namespace App\Domain\Catalog\ValueObjects;

use InvalidArgumentException;

/**
 * PartNumber Value Object
 *
 * Immutable representation of a part number.
 * Handles normalization and validation of part numbers.
 */
final class PartNumber
{
    private string $value;
    private string $normalized;

    private function __construct(string $value)
    {
        $trimmed = trim($value);

        if (empty($trimmed)) {
            throw new InvalidArgumentException('Part number cannot be empty');
        }

        $this->value = $trimmed;
        $this->normalized = self::normalize($trimmed);
    }

    /**
     * Create from string
     */
    public static function from(string $value): self
    {
        return new self($value);
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
     * Get original value
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Get normalized value (for search/comparison)
     */
    public function normalized(): string
    {
        return $this->normalized;
    }

    /**
     * Check if matches another part number (normalized comparison)
     */
    public function matches(PartNumber $other): bool
    {
        return $this->normalized === $other->normalized;
    }

    /**
     * Check if matches string
     */
    public function matchesString(string $value): bool
    {
        return $this->normalized === self::normalize($value);
    }

    /**
     * Check if contains substring
     */
    public function contains(string $substring): bool
    {
        return str_contains($this->normalized, self::normalize($substring));
    }

    /**
     * Check if starts with prefix
     */
    public function startsWith(string $prefix): bool
    {
        return str_starts_with($this->normalized, self::normalize($prefix));
    }

    /**
     * Get brand prefix (first segment before dash/space)
     */
    public function brandPrefix(): ?string
    {
        $parts = preg_split('/[-\s]/', $this->value, 2);
        return $parts[0] ?? null;
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Normalize part number for comparison
     * Removes spaces, dashes, converts to uppercase
     */
    public static function normalize(string $value): string
    {
        // Remove all non-alphanumeric characters
        $normalized = preg_replace('/[^A-Za-z0-9]/', '', $value);
        return strtoupper($normalized);
    }

    /**
     * Format for display (add dashes at standard positions)
     */
    public function formatted(): string
    {
        // Return as-is if already has formatting
        if (preg_match('/[-\s]/', $this->value)) {
            return $this->value;
        }

        // Auto-format based on length (common patterns)
        $len = strlen($this->normalized);

        if ($len >= 10) {
            // Format like: XXXXX-XXXXX
            return substr($this->normalized, 0, 5) . '-' . substr($this->normalized, 5);
        }

        return $this->value;
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
        ];
    }
}
