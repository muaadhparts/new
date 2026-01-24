<?php

namespace App\Domain\Platform\ValueObjects;

use InvalidArgumentException;

/**
 * Money Value Object
 *
 * Immutable representation of monetary value with currency.
 * All monetary operations should use this value object.
 */
final class Money
{
    private float $amount;
    private string $currency;

    private function __construct(float $amount, string $currency)
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Money amount cannot be negative');
        }

        $this->amount = round($amount, 2);
        $this->currency = strtoupper($currency);
    }

    /**
     * Create Money from amount and currency
     */
    public static function of(float $amount, string $currency = 'SAR'): self
    {
        return new self($amount, $currency);
    }

    /**
     * Create zero Money
     */
    public static function zero(string $currency = 'SAR'): self
    {
        return new self(0, $currency);
    }

    /**
     * Create from cents/smallest unit
     */
    public static function fromCents(int $cents, string $currency = 'SAR'): self
    {
        return new self($cents / 100, $currency);
    }

    /**
     * Get amount
     */
    public function amount(): float
    {
        return $this->amount;
    }

    /**
     * Get currency code
     */
    public function currency(): string
    {
        return $this->currency;
    }

    /**
     * Get amount in cents
     */
    public function cents(): int
    {
        return (int) ($this->amount * 100);
    }

    /**
     * Add money (returns new instance)
     */
    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }

    /**
     * Subtract money (returns new instance)
     */
    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);
        $result = $this->amount - $other->amount;

        if ($result < 0) {
            throw new InvalidArgumentException('Result cannot be negative');
        }

        return new self($result, $this->currency);
    }

    /**
     * Multiply by factor (returns new instance)
     */
    public function multiply(float $factor): self
    {
        if ($factor < 0) {
            throw new InvalidArgumentException('Factor cannot be negative');
        }

        return new self($this->amount * $factor, $this->currency);
    }

    /**
     * Calculate percentage
     */
    public function percentage(float $percent): self
    {
        return $this->multiply($percent / 100);
    }

    /**
     * Apply discount percentage
     */
    public function discount(float $percent): self
    {
        return $this->multiply(1 - $percent / 100);
    }

    /**
     * Check if zero
     */
    public function isZero(): bool
    {
        return $this->amount === 0.0;
    }

    /**
     * Check if positive
     */
    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    /**
     * Check if equals another money
     */
    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency === $other->currency;
    }

    /**
     * Check if greater than
     */
    public function greaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount > $other->amount;
    }

    /**
     * Check if less than
     */
    public function lessThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount < $other->amount;
    }

    /**
     * Format for display
     */
    public function format(): string
    {
        return monetaryUnit()->format($this->amount);
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'formatted' => $this->format(),
        ];
    }

    /**
     * Assert same currency
     */
    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot operate on different currencies: {$this->currency} vs {$other->currency}"
            );
        }
    }
}
