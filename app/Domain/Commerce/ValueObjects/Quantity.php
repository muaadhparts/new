<?php

namespace App\Domain\Commerce\ValueObjects;

use InvalidArgumentException;

/**
 * Quantity Value Object
 *
 * Immutable representation of item quantity.
 * Handles minimum quantity validation.
 */
final class Quantity
{
    private int $value;
    private int $minimum;

    private function __construct(int $value, int $minimum = 1)
    {
        if ($minimum < 1) {
            throw new InvalidArgumentException('Minimum quantity must be at least 1');
        }

        if ($value < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative');
        }

        if ($value > 0 && $value < $minimum) {
            throw new InvalidArgumentException("Quantity must be at least {$minimum}");
        }

        $this->value = $value;
        $this->minimum = $minimum;
    }

    /**
     * Create quantity with minimum
     */
    public static function of(int $value, int $minimum = 1): self
    {
        return new self($value, $minimum);
    }

    /**
     * Create zero quantity
     */
    public static function zero(): self
    {
        return new self(0, 1);
    }

    /**
     * Create one quantity
     */
    public static function one(): self
    {
        return new self(1, 1);
    }

    /**
     * Get value
     */
    public function value(): int
    {
        return $this->value;
    }

    /**
     * Get minimum
     */
    public function minimum(): int
    {
        return $this->minimum;
    }

    /**
     * Add quantity (returns new instance)
     */
    public function add(int $amount): self
    {
        return new self($this->value + $amount, $this->minimum);
    }

    /**
     * Subtract quantity (returns new instance)
     */
    public function subtract(int $amount): self
    {
        $result = $this->value - $amount;

        if ($result < 0) {
            throw new InvalidArgumentException('Result cannot be negative');
        }

        return new self($result, $this->minimum);
    }

    /**
     * Increment by one
     */
    public function increment(): self
    {
        return $this->add(1);
    }

    /**
     * Decrement by one
     */
    public function decrement(): self
    {
        return $this->subtract(1);
    }

    /**
     * Check if zero
     */
    public function isZero(): bool
    {
        return $this->value === 0;
    }

    /**
     * Check if positive
     */
    public function isPositive(): bool
    {
        return $this->value > 0;
    }

    /**
     * Check if meets minimum
     */
    public function meetsMinimum(): bool
    {
        return $this->value === 0 || $this->value >= $this->minimum;
    }

    /**
     * Check if greater than stock
     */
    public function exceeds(int $stock): bool
    {
        return $this->value > $stock;
    }

    /**
     * Check if equals another quantity
     */
    public function equals(Quantity $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'minimum' => $this->minimum,
        ];
    }
}
