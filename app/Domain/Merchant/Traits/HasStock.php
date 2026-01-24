<?php

namespace App\Domain\Merchant\Traits;

use App\Domain\Merchant\Enums\StockStatus;

/**
 * Has Stock Trait
 *
 * Provides stock management functionality.
 */
trait HasStock
{
    /**
     * Get stock column
     */
    public function getStockColumn(): string
    {
        return $this->stockColumn ?? 'stock';
    }

    /**
     * Get low stock threshold
     */
    public function getLowStockThreshold(): int
    {
        return $this->lowStockThreshold ?? 5;
    }

    /**
     * Get current stock
     */
    public function getStock(): int
    {
        return (int) ($this->{$this->getStockColumn()} ?? 0);
    }

    /**
     * Check if in stock
     */
    public function inStock(): bool
    {
        return $this->getStock() > 0;
    }

    /**
     * Check if out of stock
     */
    public function outOfStock(): bool
    {
        return $this->getStock() <= 0;
    }

    /**
     * Check if low stock
     */
    public function isLowStock(): bool
    {
        $stock = $this->getStock();
        return $stock > 0 && $stock <= $this->getLowStockThreshold();
    }

    /**
     * Get stock status
     */
    public function getStockStatus(): StockStatus
    {
        return StockStatus::fromQuantity($this->getStock(), $this->getLowStockThreshold());
    }

    /**
     * Check if has enough stock
     */
    public function hasEnoughStock(int $quantity): bool
    {
        return $this->getStock() >= $quantity;
    }

    /**
     * Increment stock
     */
    public function incrementStock(int $amount = 1): bool
    {
        return $this->increment($this->getStockColumn(), $amount);
    }

    /**
     * Decrement stock
     */
    public function decrementStock(int $amount = 1): bool
    {
        if (!$this->hasEnoughStock($amount)) {
            return false;
        }

        return $this->decrement($this->getStockColumn(), $amount);
    }

    /**
     * Set stock
     */
    public function setStock(int $amount): bool
    {
        return $this->update([$this->getStockColumn() => max(0, $amount)]);
    }

    /**
     * Reserve stock
     */
    public function reserveStock(int $amount): bool
    {
        if (!$this->hasEnoughStock($amount)) {
            return false;
        }

        return $this->decrementStock($amount);
    }

    /**
     * Release reserved stock
     */
    public function releaseStock(int $amount): bool
    {
        return $this->incrementStock($amount);
    }

    /**
     * Scope in stock
     */
    public function scopeInStock($query)
    {
        return $query->where($this->getStockColumn(), '>', 0);
    }

    /**
     * Scope out of stock
     */
    public function scopeOutOfStock($query)
    {
        return $query->where($this->getStockColumn(), '<=', 0);
    }

    /**
     * Scope low stock
     */
    public function scopeLowStock($query)
    {
        return $query->where($this->getStockColumn(), '>', 0)
            ->where($this->getStockColumn(), '<=', $this->getLowStockThreshold());
    }

    /**
     * Scope with minimum stock
     */
    public function scopeWithMinimumStock($query, int $minimum)
    {
        return $query->where($this->getStockColumn(), '>=', $minimum);
    }
}
