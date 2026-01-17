<?php

namespace App\Services\Cart;

use Illuminate\Support\Facades\Session;

/**
 * CartStorage - Session-based cart persistence
 *
 * This class handles all cart session operations.
 * Single source of truth for cart session management.
 */
class CartStorage
{
    /**
     * Session key for the cart
     */
    public const SESSION_KEY = 'merchant_cart';

    /**
     * Session key for old cart (migration)
     */
    public const OLD_SESSION_KEY = 'cart_v2';
    public const LEGACY_SESSION_KEY = 'cart';

    /**
     * Get the cart data from session
     */
    public function get(): array
    {
        return Session::get(self::SESSION_KEY, $this->getEmptyCart());
    }

    /**
     * Save cart data to session
     */
    public function save(array $cart): void
    {
        Session::put(self::SESSION_KEY, $cart);
    }

    /**
     * Clear the cart from session
     */
    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Check if cart exists and has items
     */
    public function hasItems(): bool
    {
        $cart = $this->get();
        return !empty($cart['items']);
    }

    /**
     * Get empty cart structure
     */
    public function getEmptyCart(): array
    {
        return [
            'items' => [],
            'totals' => [
                'qty' => 0,
                'subtotal' => 0.0,
                'discount' => 0.0,
                'total' => 0.0,
            ],
        ];
    }

    /**
     * Get a specific item from cart
     */
    public function getItem(string $key): ?array
    {
        $cart = $this->get();
        return $cart['items'][$key] ?? null;
    }

    /**
     * Set a specific item in cart
     */
    public function setItem(string $key, array $item): void
    {
        $cart = $this->get();
        $cart['items'][$key] = $item;
        $this->save($cart);
    }

    /**
     * Remove a specific item from cart
     */
    public function removeItem(string $key): void
    {
        $cart = $this->get();
        unset($cart['items'][$key]);
        $this->save($cart);
    }

    /**
     * Get all items
     */
    public function getItems(): array
    {
        $cart = $this->get();
        return $cart['items'] ?? [];
    }

    /**
     * Get totals
     */
    public function getTotals(): array
    {
        $cart = $this->get();
        return $cart['totals'] ?? $this->getEmptyCart()['totals'];
    }

    /**
     * Update totals
     */
    public function updateTotals(array $totals): void
    {
        $cart = $this->get();
        $cart['totals'] = $totals;
        $this->save($cart);
    }

    /**
     * Check if cart has specific item
     */
    public function hasItem(string $key): bool
    {
        $cart = $this->get();
        return isset($cart['items'][$key]);
    }

    /**
     * Get item count (distinct items, not total quantity)
     */
    public function getItemCount(): int
    {
        $cart = $this->get();
        return count($cart['items'] ?? []);
    }

    /**
     * Get total quantity
     */
    public function getTotalQty(): int
    {
        $totals = $this->getTotals();
        return (int) ($totals['qty'] ?? 0);
    }

    /**
     * Get total price
     */
    public function getTotalPrice(): float
    {
        $totals = $this->getTotals();
        return (float) ($totals['total'] ?? 0);
    }

    /**
     * Migrate from old cart session to new format
     * Call this once on first access if old cart exists
     */
    public function migrateFromOldCart(): bool
    {
        // Check v2 cart first
        if (Session::has(self::OLD_SESSION_KEY)) {
            $oldCart = Session::get(self::OLD_SESSION_KEY);

            if (!empty($oldCart['items'])) {
                // Already in array format, just rename
                $newCart = $this->getEmptyCart();
                $newCart['items'] = $oldCart['items'];
                $newCart['totals']['qty'] = (int) ($oldCart['totalQty'] ?? 0);
                $newCart['totals']['total'] = (float) ($oldCart['totalPrice'] ?? 0);

                $this->save($newCart);
                Session::forget(self::OLD_SESSION_KEY);
                return true;
            }
        }

        // Check legacy object-based cart
        if (Session::has(self::LEGACY_SESSION_KEY)) {
            $legacyCart = Session::get(self::LEGACY_SESSION_KEY);

            if (is_object($legacyCart) && !empty($legacyCart->items)) {
                // Convert object to array
                $newCart = $this->getEmptyCart();

                foreach ($legacyCart->items as $key => $item) {
                    // Convert legacy format to new format
                    $newCart['items'][$key] = $this->convertLegacyItem($key, $item);
                }

                $newCart['totals']['qty'] = (int) ($legacyCart->totalQty ?? 0);
                $newCart['totals']['total'] = (float) ($legacyCart->totalPrice ?? 0);

                $this->save($newCart);
                Session::forget(self::LEGACY_SESSION_KEY);
                return true;
            }
        }

        return false;
    }

    /**
     * Convert legacy cart item to new format
     */
    private function convertLegacyItem(string $key, array $item): array
    {
        $catalogItem = $item['item'] ?? null;

        return [
            'key' => $key,
            'merchant_item_id' => (int) ($item['merchant_item_id'] ?? 0),
            'merchant_id' => (int) ($item['user_id'] ?? 0),
            'catalog_item_id' => $catalogItem ? ($catalogItem->id ?? 0) : 0,
            'brand_quality_id' => $item['brand_quality_id'] ?? null,
            'name' => $catalogItem ? ($catalogItem->name ?? '') : '',
            'name_ar' => $catalogItem ? ($catalogItem->label_ar ?? '') : '',
            'photo' => $catalogItem ? ($catalogItem->photo ?? '') : '',
            'slug' => $catalogItem ? ($catalogItem->slug ?? '') : '',
            'part_number' => $catalogItem ? ($catalogItem->part_number ?? '') : '',
            'merchant_name' => '', // Will be filled later
            'merchant_name_ar' => '',
            'unit_price' => (float) ($item['item_price'] ?? $item['price'] ?? 0),
            'size_price' => (float) ($item['size_price'] ?? 0),
            'color_price' => (float) ($item['color_price'] ?? 0),
            'previous_price' => 0,
            'qty' => (int) ($item['qty'] ?? 1),
            'min_qty' => (int) ($item['minimum_qty'] ?? 1),
            'stock' => (int) ($item['stock'] ?? 0),
            'preordered' => (bool) ($item['preordered'] ?? false),
            'size' => $item['size'] ?? null,
            'color' => $item['color'] ?? null,
            'keys' => $item['keys'] ?? null,
            'values' => $item['values'] ?? null,
            'whole_sell_qty' => [],
            'whole_sell_discount' => [],
            'added_at' => now()->toDateTimeString(),
            'effective_unit_price' => (float) ($item['item_price'] ?? $item['price'] ?? 0),
            'discount_percent' => (float) ($item['discount'] ?? 0),
            'discounted_unit_price' => (float) ($item['item_price'] ?? $item['price'] ?? 0),
            'total_price' => (float) ($item['price'] ?? 0),
        ];
    }

    /**
     * Get items grouped by merchant
     */
    public function getItemsByMerchant(): array
    {
        $items = $this->getItems();
        $grouped = [];

        foreach ($items as $key => $item) {
            $merchantId = (int) ($item['merchant_id'] ?? 0);

            if (!isset($grouped[$merchantId])) {
                $grouped[$merchantId] = [
                    'merchant_id' => $merchantId,
                    'merchant_name' => $item['merchant_name'] ?? '',
                    'merchant_name_ar' => $item['merchant_name_ar'] ?? '',
                    'items' => [],
                    'subtotal' => 0.0,
                    'qty' => 0,
                ];
            }

            $grouped[$merchantId]['items'][$key] = $item;
            $grouped[$merchantId]['subtotal'] += (float) ($item['total_price'] ?? 0);
            $grouped[$merchantId]['qty'] += (int) ($item['qty'] ?? 0);
        }

        return $grouped;
    }

    /**
     * Get items for a specific merchant
     */
    public function getItemsForMerchant(int $merchantId): array
    {
        $items = $this->getItems();
        $merchantItems = [];

        foreach ($items as $key => $item) {
            if ((int) ($item['merchant_id'] ?? 0) === $merchantId) {
                $merchantItems[$key] = $item;
            }
        }

        return $merchantItems;
    }

    /**
     * Get distinct merchant IDs in cart
     */
    public function getMerchantIds(): array
    {
        $items = $this->getItems();
        $ids = [];

        foreach ($items as $item) {
            $merchantId = (int) ($item['merchant_id'] ?? 0);
            if ($merchantId > 0 && !in_array($merchantId, $ids)) {
                $ids[] = $merchantId;
            }
        }

        return $ids;
    }
}
