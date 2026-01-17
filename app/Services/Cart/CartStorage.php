<?php

namespace App\Services\Cart;

use Illuminate\Support\Facades\Session;

/**
 * CartStorage - Session-based cart persistence
 *
 * SINGLE SOURCE OF TRUTH for cart data.
 * FAIL-FAST: No fallbacks, no legacy support, no silent defaults.
 *
 * All cart keys MUST match current session.
 * Invalid keys are automatically removed.
 * Missing required fields = Exception.
 */
class CartStorage
{
    public const SESSION_KEY = 'merchant_cart';

    public function get(): array
    {
        $cart = Session::get(self::SESSION_KEY, $this->getEmptyCart());

        // Clean invalid keys (from different sessions)
        $cleaned = $this->cleanInvalidKeys($cart);
        if ($cleaned !== $cart) {
            $this->save($cleaned);
            return $cleaned;
        }

        return $cart;
    }

    /**
     * Remove items with keys that don't match current session
     * No fallbacks - invalid keys are simply removed
     */
    private function cleanInvalidKeys(array $cart): array
    {
        if (empty($cart['items'])) {
            return $cart;
        }

        $validItems = [];
        $needsRecalc = false;

        foreach ($cart['items'] as $key => $item) {
            if (CartItem::isValidKeyForSession($key)) {
                $validItems[$key] = $item;
            } else {
                // Invalid key - remove silently, no fallback
                $needsRecalc = true;
            }
        }

        if ($needsRecalc) {
            $cart['items'] = $validItems;
            // Use CartItem as SINGLE SOURCE OF TRUTH for totals
            $cart['totals'] = CartItem::calculateTotals($validItems);
        }

        return $cart;
    }

    public function save(array $cart): void
    {
        Session::put(self::SESSION_KEY, $cart);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function hasItems(): bool
    {
        $cart = $this->get();
        return !empty($cart['items']);
    }

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

    public function getItem(string $key): ?array
    {
        $cart = $this->get();
        return $cart['items'][$key] ?? null;
    }

    public function setItem(string $key, array $item): void
    {
        // FAIL-FAST: Validate item before storing
        $this->validateCartItem($item, $key);

        $cart = $this->get();
        $cart['items'][$key] = $item;
        $this->save($cart);
    }

    public function removeItem(string $key): void
    {
        $cart = $this->get();
        unset($cart['items'][$key]);
        $this->save($cart);
    }

    public function getItems(): array
    {
        $cart = $this->get();
        return $cart['items'] ?? [];
    }

    public function getTotals(): array
    {
        $cart = $this->get();
        return $cart['totals'] ?? $this->getEmptyCart()['totals'];
    }

    public function updateTotals(array $totals): void
    {
        $cart = $this->get();
        $cart['totals'] = $totals;
        $this->save($cart);
    }

    public function hasItem(string $key): bool
    {
        $cart = $this->get();
        return isset($cart['items'][$key]);
    }

    public function getItemCount(): int
    {
        $cart = $this->get();
        return count($cart['items'] ?? []);
    }

    public function getTotalQty(): int
    {
        $totals = $this->getTotals();
        return (int) $totals['qty'];
    }

    public function getTotalPrice(): float
    {
        $totals = $this->getTotals();
        return (float) $totals['total'];
    }

    /**
     * Get items grouped by merchant
     *
     * FAIL-FAST: Each item must have valid merchant_id, qty, total_price
     *
     * @throws \RuntimeException if items have missing required fields
     */
    public function getItemsByMerchant(): array
    {
        $items = $this->getItems();
        $grouped = [];

        foreach ($items as $key => $item) {
            // FAIL-FAST: Validate each item
            $this->validateCartItem($item, $key);

            $merchantId = (int) $item['merchant_id'];

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
            $grouped[$merchantId]['subtotal'] += (float) $item['total_price'];
            $grouped[$merchantId]['qty'] += (int) $item['qty'];
        }

        return $grouped;
    }

    /**
     * Get items for a specific merchant
     *
     * FAIL-FAST: Each item must have valid merchant_id
     *
     * @throws \RuntimeException if items have missing merchant_id
     */
    public function getItemsForMerchant(int $merchantId): array
    {
        if ($merchantId <= 0) {
            throw new \InvalidArgumentException(
                "Invalid merchantId: {$merchantId}. Must be > 0."
            );
        }

        $items = $this->getItems();
        $merchantItems = [];

        foreach ($items as $key => $item) {
            // FAIL-FAST: merchant_id must exist
            if (!isset($item['merchant_id'])) {
                throw new \RuntimeException(
                    "Cart item '{$key}' missing required field: merchant_id. " .
                    "Data is corrupted. Keys present: " . implode(', ', array_keys($item))
                );
            }

            $itemMerchantId = (int) $item['merchant_id'];
            if ($itemMerchantId <= 0) {
                throw new \RuntimeException(
                    "Cart item '{$key}' has invalid merchant_id: {$itemMerchantId}. Must be > 0."
                );
            }

            if ($itemMerchantId === $merchantId) {
                $merchantItems[$key] = $item;
            }
        }

        return $merchantItems;
    }

    /**
     * Get unique merchant IDs from cart
     *
     * FAIL-FAST: Each item must have valid merchant_id
     *
     * @throws \RuntimeException if items have missing merchant_id
     */
    public function getMerchantIds(): array
    {
        $items = $this->getItems();
        $ids = [];

        foreach ($items as $key => $item) {
            // FAIL-FAST: merchant_id must exist
            if (!isset($item['merchant_id'])) {
                throw new \RuntimeException(
                    "Cart item '{$key}' missing required field: merchant_id. " .
                    "Data is corrupted."
                );
            }

            $merchantId = (int) $item['merchant_id'];
            if ($merchantId <= 0) {
                throw new \RuntimeException(
                    "Cart item '{$key}' has invalid merchant_id: {$merchantId}. Must be > 0."
                );
            }

            if (!in_array($merchantId, $ids)) {
                $ids[] = $merchantId;
            }
        }

        return $ids;
    }

    /**
     * Validate cart item has all required fields
     *
     * FAIL-FAST: Missing or invalid fields = Exception
     *
     * @throws \RuntimeException if validation fails
     */
    private function validateCartItem(array $item, string $key): void
    {
        $requiredFields = [
            'merchant_item_id',
            'merchant_id',
            'catalog_item_id',
            'qty',
            'total_price',
        ];

        foreach ($requiredFields as $field) {
            if (!isset($item[$field])) {
                throw new \RuntimeException(
                    "Cart item '{$key}' missing required field: {$field}. " .
                    "Keys present: " . implode(', ', array_keys($item))
                );
            }
        }

        // Validate critical values
        if ((int) $item['merchant_id'] <= 0) {
            throw new \RuntimeException(
                "Cart item '{$key}' has invalid merchant_id: {$item['merchant_id']}. Must be > 0."
            );
        }

        if ((int) $item['merchant_item_id'] <= 0) {
            throw new \RuntimeException(
                "Cart item '{$key}' has invalid merchant_item_id: {$item['merchant_item_id']}. Must be > 0."
            );
        }

        if ((int) $item['qty'] <= 0) {
            throw new \RuntimeException(
                "Cart item '{$key}' has invalid qty: {$item['qty']}. Must be > 0."
            );
        }

        if ((float) $item['total_price'] <= 0) {
            throw new \RuntimeException(
                "Cart item '{$key}' has invalid total_price: {$item['total_price']}. Must be > 0."
            );
        }
    }
}
