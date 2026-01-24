<?php

namespace App\Domain\Commerce\Contracts;

use Illuminate\Support\Collection;

/**
 * CartInterface - Contract for cart operations
 *
 * All cart operations MUST go through this interface.
 */
interface CartInterface
{
    /**
     * Get cart items for a branch
     */
    public function getItems(int $branchId): Collection;

    /**
     * Add item to cart
     */
    public function addItem(int $branchId, int $merchantItemId, int $quantity = 1): bool;

    /**
     * Update item quantity
     */
    public function updateQuantity(int $branchId, int $merchantItemId, int $quantity): bool;

    /**
     * Remove item from cart
     */
    public function removeItem(int $branchId, int $merchantItemId): bool;

    /**
     * Clear cart for a branch
     */
    public function clear(int $branchId): bool;

    /**
     * Get cart total
     */
    public function getTotal(int $branchId): float;

    /**
     * Get cart item count
     */
    public function getCount(int $branchId): int;

    /**
     * Check if cart has items
     */
    public function hasItems(int $branchId): bool;

    /**
     * Validate cart items availability
     */
    public function validateStock(int $branchId): array;
}
