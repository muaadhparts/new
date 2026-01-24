<?php

namespace App\Domain\Commerce\Traits;

/**
 * Has Cart Trait
 *
 * Provides cart functionality for user models.
 */
trait HasCart
{
    /**
     * Get cart session key
     */
    public function getCartSessionKey(): string
    {
        return 'cart_' . ($this->id ?? 'guest');
    }

    /**
     * Get cart items from session
     */
    public function getCartItems(): array
    {
        return session($this->getCartSessionKey(), []);
    }

    /**
     * Get cart items count
     */
    public function getCartItemsCount(): int
    {
        return array_sum(array_column($this->getCartItems(), 'quantity'));
    }

    /**
     * Get unique items count
     */
    public function getCartUniqueItemsCount(): int
    {
        return count($this->getCartItems());
    }

    /**
     * Check if cart is empty
     */
    public function hasEmptyCart(): bool
    {
        return empty($this->getCartItems());
    }

    /**
     * Check if cart has items
     */
    public function hasItemsInCart(): bool
    {
        return !$this->hasEmptyCart();
    }

    /**
     * Get cart total
     */
    public function getCartTotal(): float
    {
        $items = $this->getCartItems();

        return array_reduce($items, function ($total, $item) {
            return $total + (($item['price'] ?? 0) * ($item['quantity'] ?? 1));
        }, 0.0);
    }

    /**
     * Add item to cart
     */
    public function addToCart(int $itemId, int $quantity = 1, array $options = []): bool
    {
        $cart = $this->getCartItems();
        $key = $this->generateCartItemKey($itemId, $options);

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $quantity;
        } else {
            $cart[$key] = [
                'item_id' => $itemId,
                'quantity' => $quantity,
                'options' => $options,
                'added_at' => now()->toISOString(),
            ];
        }

        session([$this->getCartSessionKey() => $cart]);
        return true;
    }

    /**
     * Update cart item quantity
     */
    public function updateCartItemQuantity(string $key, int $quantity): bool
    {
        $cart = $this->getCartItems();

        if (!isset($cart[$key])) {
            return false;
        }

        if ($quantity <= 0) {
            unset($cart[$key]);
        } else {
            $cart[$key]['quantity'] = $quantity;
        }

        session([$this->getCartSessionKey() => $cart]);
        return true;
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart(string $key): bool
    {
        $cart = $this->getCartItems();

        if (!isset($cart[$key])) {
            return false;
        }

        unset($cart[$key]);
        session([$this->getCartSessionKey() => $cart]);
        return true;
    }

    /**
     * Clear cart
     */
    public function clearCart(): bool
    {
        session()->forget($this->getCartSessionKey());
        return true;
    }

    /**
     * Generate cart item key
     */
    protected function generateCartItemKey(int $itemId, array $options = []): string
    {
        $optionsHash = md5(json_encode($options));
        return "{$itemId}_{$optionsHash}";
    }

    /**
     * Check if item in cart
     */
    public function hasInCart(int $itemId): bool
    {
        $cart = $this->getCartItems();

        foreach ($cart as $item) {
            if (($item['item_id'] ?? null) === $itemId) {
                return true;
            }
        }

        return false;
    }
}
