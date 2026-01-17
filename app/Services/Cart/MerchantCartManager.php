<?php

namespace App\Services\Cart;

use App\Models\MerchantItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * MerchantCartManager - Core cart operations
 *
 * SINGLE SERVICE for all cart operations.
 * Replaces: MerchantCartHelper, MerchantCart model, CartMerchantController logic
 */
class MerchantCartManager
{
    private CartStorage $storage;
    private StockReservation $reservation;

    public function __construct(CartStorage $storage, StockReservation $reservation)
    {
        $this->storage = $storage;
        $this->reservation = $reservation;
    }

    // ===================== ADD ITEM =====================

    /**
     * Add item to cart
     *
     * @param int $merchantItemId The merchant_item_id (REQUIRED)
     * @param int $qty Quantity to add
     * @param string|null $size Size variant
     * @param string|null $color Color variant
     * @param string|null $keys Custom keys
     * @param string|null $values Custom values
     * @return array ['success' => bool, 'message' => string, 'cart' => array]
     */
    public function add(
        int $merchantItemId,
        int $qty = 1,
        ?string $size = null,
        ?string $color = null,
        ?string $keys = null,
        ?string $values = null
    ): array {
        // Validate merchant item exists and is active
        $merchantItem = MerchantItem::with(['catalogItem', 'user', 'qualityBrand'])->find($merchantItemId);

        if (!$merchantItem) {
            return $this->error(__('Item not found'));
        }

        if ($merchantItem->status !== 1) {
            return $this->error(__('Item is not available'));
        }

        // Validate merchant is active
        $merchant = $merchantItem->user;
        if (!$merchant || $merchant->is_merchant !== 2) {
            return $this->error(__('Merchant is not active'));
        }

        // Validate minimum quantity
        $minQty = max(1, (int) ($merchantItem->minimum_qty ?? 1));
        if ($qty < $minQty) {
            return $this->error(__('Minimum quantity is') . ' ' . $minQty);
        }

        // Auto-select size if not provided
        if ($size === null && !empty($merchantItem->size)) {
            $size = $this->getFirstAvailableSize($merchantItem);
        }

        // Auto-select color if not provided
        if ($color === null && !empty($merchantItem->color_all)) {
            $colors = $this->toArray($merchantItem->color_all);
            $color = !empty($colors) ? ltrim($colors[0], '#') : null;
        }

        // Validate stock (unless preorder)
        $stock = CartItem::getEffectiveStock($merchantItem, $size);
        $isPreorder = (bool) ($merchantItem->preordered ?? false);

        if (!$isPreorder && $stock <= 0) {
            return $this->error(__('Out of stock'));
        }

        // Generate cart key
        $cartKey = CartItem::generateKey($merchantItemId, $size, $color);

        // Check existing item in cart
        $existingItem = $this->storage->getItem($cartKey);
        $existingQty = $existingItem ? (int) $existingItem['qty'] : 0;
        $newTotalQty = $existingQty + $qty;

        // Validate total quantity against stock
        if (!$isPreorder && $stock > 0 && $newTotalQty > $stock) {
            $available = $stock - $existingQty;
            if ($available <= 0) {
                return $this->error(__('Stock limit reached'));
            }
            return $this->error(__('Only') . ' ' . $available . ' ' . __('more available'));
        }

        // Reserve stock
        if (!$isPreorder && $stock > 0) {
            if (!$this->reservation->update($merchantItemId, $newTotalQty, $size)) {
                return $this->error(__('Could not reserve stock'));
            }
        }

        // Create or update cart item
        if ($existingItem) {
            // Update existing
            $existingItem['qty'] = $newTotalQty;
            $cartItem = CartItem::fromArray($existingItem);
            $cartItem = $cartItem->withQty($newTotalQty);
        } else {
            // Create new
            $cartItem = CartItem::fromMerchantItem($merchantItem, $qty, $size, $color, $keys, $values);
        }

        // Save to storage
        $this->storage->setItem($cartKey, $cartItem->toArray());

        // Recalculate totals
        $this->recalculateTotals();

        return $this->success(__('Item added to cart'), $this->getCart());
    }

    // ===================== UPDATE QUANTITY =====================

    /**
     * Update item quantity
     *
     * @param string $cartKey The cart item key
     * @param int $qty New quantity
     * @return array
     */
    public function updateQty(string $cartKey, int $qty): array
    {
        $item = $this->storage->getItem($cartKey);

        if (!$item) {
            return $this->error(__('Item not found in cart'));
        }

        $minQty = (int) ($item['min_qty'] ?? 1);
        if ($qty < $minQty) {
            return $this->error(__('Minimum quantity is') . ' ' . $minQty);
        }

        // Validate against current stock
        $merchantItem = MerchantItem::find($item['merchant_item_id']);
        if (!$merchantItem || $merchantItem->status !== 1) {
            // Remove invalid item
            $this->remove($cartKey);
            return $this->error(__('Item no longer available'));
        }

        $stock = CartItem::getEffectiveStock($merchantItem, $item['size'] ?? null);
        $isPreorder = (bool) ($item['preordered'] ?? false);

        if (!$isPreorder && $stock > 0 && $qty > $stock) {
            return $this->error(__('Only') . ' ' . $stock . ' ' . __('available'));
        }

        // Update reservation
        if (!$isPreorder && $stock > 0) {
            $this->reservation->update($item['merchant_item_id'], $qty, $item['size'] ?? null);
        }

        // Update item
        $cartItem = CartItem::fromArray($item);
        $cartItem = $cartItem->withQty($qty);
        $this->storage->setItem($cartKey, $cartItem->toArray());

        // Recalculate totals
        $this->recalculateTotals();

        return $this->success(__('Quantity updated'), $this->getCart());
    }

    /**
     * Increase item quantity by 1
     */
    public function increase(string $cartKey): array
    {
        $item = $this->storage->getItem($cartKey);

        if (!$item) {
            return $this->error(__('Item not found in cart'));
        }

        $currentQty = (int) ($item['qty'] ?? 1);
        return $this->updateQty($cartKey, $currentQty + 1);
    }

    /**
     * Decrease item quantity by 1
     */
    public function decrease(string $cartKey): array
    {
        $item = $this->storage->getItem($cartKey);

        if (!$item) {
            return $this->error(__('Item not found in cart'));
        }

        $currentQty = (int) ($item['qty'] ?? 1);
        $minQty = (int) ($item['min_qty'] ?? 1);

        if ($currentQty <= $minQty) {
            return $this->error(__('Minimum quantity is') . ' ' . $minQty);
        }

        return $this->updateQty($cartKey, $currentQty - 1);
    }

    // ===================== REMOVE ITEM =====================

    /**
     * Remove item from cart
     *
     * @param string $cartKey
     * @return array
     */
    public function remove(string $cartKey): array
    {
        $item = $this->storage->getItem($cartKey);

        if (!$item) {
            return $this->error(__('Item not found in cart'));
        }

        // Release stock reservation
        $this->reservation->release(
            (int) $item['merchant_item_id'],
            $item['size'] ?? null
        );

        // Remove from storage
        $this->storage->removeItem($cartKey);

        // Recalculate totals
        $this->recalculateTotals();

        return $this->success(__('Item removed from cart'), $this->getCart());
    }

    // ===================== CLEAR CART =====================

    /**
     * Clear all items from cart
     */
    public function clear(): array
    {
        // Release all reservations
        $this->reservation->releaseAll();

        // Clear storage
        $this->storage->clear();

        return $this->success(__('Cart cleared'), $this->storage->getEmptyCart());
    }

    // ===================== GET CART =====================

    /**
     * Get full cart data
     */
    public function getCart(): array
    {
        $cart = $this->storage->get();

        // Add grouped by merchant
        $cart['by_merchant'] = $this->storage->getItemsByMerchant();

        return $cart;
    }

    /**
     * Get cart items
     */
    public function getItems(): array
    {
        return $this->storage->getItems();
    }

    /**
     * Get item by key
     */
    public function getItem(string $cartKey): ?array
    {
        return $this->storage->getItem($cartKey);
    }

    /**
     * Get cart totals
     */
    public function getTotals(): array
    {
        return $this->storage->getTotals();
    }

    /**
     * Get total quantity
     */
    public function getTotalQty(): int
    {
        return $this->storage->getTotalQty();
    }

    /**
     * Get total price
     */
    public function getTotalPrice(): float
    {
        return $this->storage->getTotalPrice();
    }

    /**
     * Check if cart has items
     */
    public function hasItems(): bool
    {
        return $this->storage->hasItems();
    }

    /**
     * Get item count
     */
    public function getItemCount(): int
    {
        return $this->storage->getItemCount();
    }

    /**
     * Get items grouped by merchant
     */
    public function getItemsByMerchant(): array
    {
        return $this->storage->getItemsByMerchant();
    }

    /**
     * Get items for a specific merchant
     */
    public function getItemsForMerchant(int $merchantId): array
    {
        return $this->storage->getItemsForMerchant($merchantId);
    }

    /**
     * Get merchant IDs in cart
     */
    public function getMerchantIds(): array
    {
        return $this->storage->getMerchantIds();
    }

    // ===================== VALIDATION =====================

    /**
     * Validate all items in cart (check stock, availability)
     * Returns array of issues found
     */
    public function validate(): array
    {
        $issues = [];
        $items = $this->storage->getItems();

        foreach ($items as $key => $item) {
            $merchantItem = MerchantItem::with('user')->find($item['merchant_item_id']);

            // Check if item exists
            if (!$merchantItem) {
                $issues[$key] = [
                    'type' => 'not_found',
                    'message' => __('Item no longer exists'),
                ];
                continue;
            }

            // Check if item is active
            if ($merchantItem->status !== 1) {
                $issues[$key] = [
                    'type' => 'inactive',
                    'message' => __('Item is no longer available'),
                ];
                continue;
            }

            // Check if merchant is active
            if (!$merchantItem->user || $merchantItem->user->is_merchant !== 2) {
                $issues[$key] = [
                    'type' => 'merchant_inactive',
                    'message' => __('Merchant is no longer active'),
                ];
                continue;
            }

            // Check stock
            $isPreorder = (bool) ($item['preordered'] ?? false);
            if (!$isPreorder) {
                $stock = CartItem::getEffectiveStock($merchantItem, $item['size'] ?? null);
                $qty = (int) ($item['qty'] ?? 1);

                if ($stock <= 0) {
                    $issues[$key] = [
                        'type' => 'out_of_stock',
                        'message' => __('Item is out of stock'),
                    ];
                } elseif ($qty > $stock) {
                    $issues[$key] = [
                        'type' => 'insufficient_stock',
                        'message' => __('Only') . ' ' . $stock . ' ' . __('available'),
                        'available' => $stock,
                    ];
                }
            }

            // Check price changes
            $currentPrice = $merchantItem->merchantSizePrice();
            $storedPrice = (float) ($item['unit_price'] ?? 0);

            if (abs($currentPrice - $storedPrice) > 0.01) {
                $issues[$key] = [
                    'type' => 'price_changed',
                    'message' => __('Price has changed'),
                    'old_price' => $storedPrice,
                    'new_price' => $currentPrice,
                ];
            }
        }

        return $issues;
    }

    /**
     * Refresh cart items (update prices, remove invalid items)
     */
    public function refresh(): array
    {
        $items = $this->storage->getItems();
        $updated = [];
        $removed = [];

        foreach ($items as $key => $item) {
            $merchantItem = MerchantItem::with(['catalogItem', 'user', 'qualityBrand'])->find($item['merchant_item_id']);

            // Remove invalid items
            if (!$merchantItem || $merchantItem->status !== 1) {
                $this->storage->removeItem($key);
                $removed[] = $key;
                continue;
            }

            // Check merchant
            if (!$merchantItem->user || $merchantItem->user->is_merchant !== 2) {
                $this->storage->removeItem($key);
                $removed[] = $key;
                continue;
            }

            // Update item with fresh data
            $freshItem = CartItem::fromMerchantItem(
                $merchantItem,
                (int) $item['qty'],
                $item['size'] ?? null,
                $item['color'] ?? null,
                $item['keys'] ?? null,
                $item['values'] ?? null
            );

            $this->storage->setItem($key, $freshItem->toArray());
            $updated[] = $key;
        }

        // Recalculate totals
        $this->recalculateTotals();

        return [
            'updated' => $updated,
            'removed' => $removed,
            'cart' => $this->getCart(),
        ];
    }

    // ===================== CHECKOUT HELPERS =====================

    /**
     * Prepare cart data for checkout
     */
    public function prepareForCheckout(int $merchantId = null): array
    {
        $items = $merchantId
            ? $this->storage->getItemsForMerchant($merchantId)
            : $this->storage->getItems();

        $checkout = [
            'items' => [],
            'totals' => [
                'qty' => 0,
                'subtotal' => 0.0,
                'discount' => 0.0,
                'total' => 0.0,
            ],
        ];

        foreach ($items as $key => $item) {
            $checkout['items'][$key] = $item;
            $checkout['totals']['qty'] += (int) ($item['qty'] ?? 0);
            $checkout['totals']['subtotal'] += (float) ($item['effective_unit_price'] ?? 0) * (int) ($item['qty'] ?? 0);
            $checkout['totals']['discount'] += $this->calculateItemDiscount($item);
            $checkout['totals']['total'] += (float) ($item['total_price'] ?? 0);
        }

        return $checkout;
    }

    /**
     * Confirm checkout (deduct stock, clear cart)
     */
    public function confirmCheckout(int $merchantId = null): bool
    {
        $items = $merchantId
            ? $this->storage->getItemsForMerchant($merchantId)
            : $this->storage->getItems();

        // Prepare reservation confirmation data
        $confirmData = [];
        foreach ($items as $item) {
            $confirmData[$item['merchant_item_id']] = [
                'qty' => $item['qty'],
                'size' => $item['size'] ?? null,
            ];
        }

        // Confirm stock deduction
        if (!$this->reservation->confirm($confirmData)) {
            return false;
        }

        // Clear items from cart
        if ($merchantId) {
            foreach ($items as $key => $item) {
                $this->storage->removeItem($key);
            }
            $this->recalculateTotals();
        } else {
            $this->storage->clear();
        }

        return true;
    }

    // ===================== PRIVATE HELPERS =====================

    /**
     * Recalculate cart totals
     */
    private function recalculateTotals(): void
    {
        $items = $this->storage->getItems();

        $totals = [
            'qty' => 0,
            'subtotal' => 0.0,
            'discount' => 0.0,
            'total' => 0.0,
        ];

        foreach ($items as $item) {
            $qty = (int) ($item['qty'] ?? 0);
            $effectivePrice = (float) ($item['effective_unit_price'] ?? 0);
            $totalPrice = (float) ($item['total_price'] ?? 0);

            $totals['qty'] += $qty;
            $totals['subtotal'] += $effectivePrice * $qty;
            $totals['total'] += $totalPrice;
        }

        $totals['discount'] = $totals['subtotal'] - $totals['total'];

        $this->storage->updateTotals($totals);
    }

    /**
     * Calculate discount amount for an item
     */
    private function calculateItemDiscount(array $item): float
    {
        $qty = (int) ($item['qty'] ?? 0);
        $effectivePrice = (float) ($item['effective_unit_price'] ?? 0);
        $totalPrice = (float) ($item['total_price'] ?? 0);

        return ($effectivePrice * $qty) - $totalPrice;
    }

    /**
     * Get first available size from merchant item
     */
    private function getFirstAvailableSize(MerchantItem $merchantItem): ?string
    {
        $sizes = $this->toArray($merchantItem->size);
        $qtys = $this->toArray($merchantItem->size_qty);

        // Find first size with stock
        foreach ($sizes as $i => $size) {
            if ((int) ($qtys[$i] ?? 0) > 0) {
                return trim($size);
            }
        }

        // Return first size if none have stock
        return !empty($sizes) ? trim($sizes[0]) : null;
    }

    /**
     * Convert value to array
     */
    private function toArray($value): array
    {
        if (is_array($value)) return $value;
        if (is_string($value) && $value !== '') return array_map('trim', explode(',', $value));
        return [];
    }

    /**
     * Success response helper
     */
    private function success(string $message, array $data = []): array
    {
        return [
            'success' => true,
            'message' => $message,
            'cart' => $data,
        ];
    }

    /**
     * Error response helper
     */
    private function error(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'cart' => null,
        ];
    }
}
