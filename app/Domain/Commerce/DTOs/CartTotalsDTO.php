<?php

namespace App\Domain\Commerce\DTOs;

/**
 * CartTotalsDTO - Cart totals calculation result
 *
 * Represents the calculated totals for a cart or branch cart.
 */
class CartTotalsDTO
{
    public int $qty = 0;
    public float $subtotal = 0.0;
    public float $discount = 0.0;
    public float $total = 0.0;

    /**
     * Create DTO from array
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->qty = (int) ($data['qty'] ?? 0);
        $dto->subtotal = (float) ($data['subtotal'] ?? 0);
        $dto->discount = (float) ($data['discount'] ?? 0);
        $dto->total = (float) ($data['total'] ?? 0);
        return $dto;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'qty' => $this->qty,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'total' => $this->total,
        ];
    }

    /**
     * Get formatted subtotal
     */
    public function getFormattedSubtotal(): string
    {
        return monetaryUnit()->format($this->subtotal);
    }

    /**
     * Get formatted discount
     */
    public function getFormattedDiscount(): string
    {
        return monetaryUnit()->format($this->discount);
    }

    /**
     * Get formatted total
     */
    public function getFormattedTotal(): string
    {
        return monetaryUnit()->format($this->total);
    }

    /**
     * Check if cart has discount
     */
    public function hasDiscount(): bool
    {
        return $this->discount > 0;
    }

    /**
     * Check if cart is empty
     */
    public function isEmpty(): bool
    {
        return $this->qty === 0;
    }
}
