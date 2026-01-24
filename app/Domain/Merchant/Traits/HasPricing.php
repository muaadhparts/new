<?php

namespace App\Domain\Merchant\Traits;

/**
 * Has Pricing Trait
 *
 * Provides pricing and discount functionality.
 */
trait HasPricing
{
    /**
     * Get price column
     */
    public function getPriceColumn(): string
    {
        return $this->priceColumn ?? 'price';
    }

    /**
     * Get previous price column
     */
    public function getPreviousPriceColumn(): string
    {
        return $this->previousPriceColumn ?? 'previous_price';
    }

    /**
     * Get the current price
     */
    public function getPrice(): float
    {
        return (float) ($this->{$this->getPriceColumn()} ?? 0);
    }

    /**
     * Get the previous/original price
     */
    public function getPreviousPrice(): ?float
    {
        $previous = $this->{$this->getPreviousPriceColumn()};
        return $previous ? (float) $previous : null;
    }

    /**
     * Check if has discount
     */
    public function hasDiscount(): bool
    {
        $previous = $this->getPreviousPrice();
        return $previous !== null && $previous > $this->getPrice();
    }

    /**
     * Get discount amount
     */
    public function getDiscountAmount(): float
    {
        if (!$this->hasDiscount()) {
            return 0;
        }

        return $this->getPreviousPrice() - $this->getPrice();
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentage(): float
    {
        if (!$this->hasDiscount()) {
            return 0;
        }

        return round(($this->getDiscountAmount() / $this->getPreviousPrice()) * 100, 1);
    }

    /**
     * Get formatted price
     */
    public function getFormattedPrice(string $currency = 'SAR'): string
    {
        return number_format($this->getPrice(), 2) . ' ' . $currency;
    }

    /**
     * Get formatted previous price
     */
    public function getFormattedPreviousPrice(string $currency = 'SAR'): ?string
    {
        $previous = $this->getPreviousPrice();
        return $previous ? number_format($previous, 2) . ' ' . $currency : null;
    }

    /**
     * Apply discount percentage
     */
    public function applyDiscountPercentage(float $percentage): self
    {
        if ($percentage > 0 && $percentage <= 100) {
            $this->{$this->getPreviousPriceColumn()} = $this->getPrice();
            $this->{$this->getPriceColumn()} = $this->getPrice() * (1 - $percentage / 100);
        }

        return $this;
    }

    /**
     * Remove discount
     */
    public function removeDiscount(): self
    {
        if ($this->hasDiscount()) {
            $this->{$this->getPriceColumn()} = $this->getPreviousPrice();
            $this->{$this->getPreviousPriceColumn()} = null;
        }

        return $this;
    }

    /**
     * Scope with discount
     */
    public function scopeWithDiscount($query)
    {
        return $query->whereNotNull($this->getPreviousPriceColumn())
            ->whereColumn($this->getPreviousPriceColumn(), '>', $this->getPriceColumn());
    }

    /**
     * Scope price between
     */
    public function scopePriceBetween($query, float $min, float $max)
    {
        return $query->whereBetween($this->getPriceColumn(), [$min, $max]);
    }

    /**
     * Scope price under
     */
    public function scopePriceUnder($query, float $max)
    {
        return $query->where($this->getPriceColumn(), '<=', $max);
    }
}
