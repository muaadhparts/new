<?php

namespace App\Domain\Commerce\Services;

use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Catalog\Models\CatalogEvent;
use Illuminate\Support\Facades\Log;

/**
 * StockUpdateService
 *
 * Handles stock updates after purchase completion.
 * Updates merchant_items stock and sends low stock notifications.
 */
class StockUpdateService
{
    private const LOW_STOCK_THRESHOLD = 5;

    /**
     * Update stock levels from cart items after purchase
     *
     * @param object $cart Cart with items
     * @throws \RuntimeException If cart items format is invalid
     */
    public function updateStockFromCart(object $cart): void
    {
        if (!isset($cart->items) || !is_iterable($cart->items)) {
            throw new \RuntimeException('Invalid cart format: items must be iterable');
        }

        foreach ($cart->items as $key => $cartItem) {
            $this->processCartItem($cartItem, $key);
        }
    }

    /**
     * Update stock levels from cart items array
     *
     * @param array $cartItems
     */
    public function updateStockFromArray(array $cartItems): void
    {
        foreach ($cartItems as $key => $cartItem) {
            $this->processCartItem($cartItem, $key);
        }
    }

    /**
     * Process a single cart item for stock update
     */
    private function processCartItem(array $cartItem, string $key): void
    {
        $newStock = $cartItem['stock'] ?? null;

        if ($newStock === null) {
            return;
        }

        $catalogItemId = $this->extractCatalogItemId($cartItem, $key);
        $merchantId = $this->extractMerchantId($cartItem, $key);

        if ($catalogItemId <= 0 || $merchantId <= 0) {
            Log::warning('Stock update skipped: missing catalog_item_id or merchant_id', [
                'key' => $key,
                'catalog_item_id' => $catalogItemId,
                'merchant_id' => $merchantId,
            ]);
            return;
        }

        $this->updateMerchantItemStock($catalogItemId, $merchantId, (int) $newStock);
    }

    /**
     * Extract catalog item ID from cart item
     */
    private function extractCatalogItemId(array $cartItem, string $key): int
    {
        return (int) (
            $cartItem['catalog_item_id'] ??
            $cartItem['item']['id'] ??
            $cartItem['id'] ??
            0
        );
    }

    /**
     * Extract merchant ID from cart item
     */
    private function extractMerchantId(array $cartItem, string $key): int
    {
        return (int) (
            $cartItem['merchant_id'] ??
            $cartItem['user_id'] ??
            $cartItem['item']['user_id'] ??
            0
        );
    }

    /**
     * Update merchant item stock and send notifications
     */
    private function updateMerchantItemStock(int $catalogItemId, int $merchantId, int $newStock): void
    {
        try {
            $merchantItem = MerchantItem::where('catalog_item_id', $catalogItemId)
                ->where('user_id', $merchantId)
                ->first();

            if (!$merchantItem) {
                Log::warning('MerchantItem not found for stock update', [
                    'catalog_item_id' => $catalogItemId,
                    'merchant_id' => $merchantId,
                ]);
                return;
            }

            $oldStock = $merchantItem->stock;
            $merchantItem->stock = $newStock;
            $merchantItem->save();

            Log::info('Stock updated', [
                'merchant_item_id' => $merchantItem->id,
                'catalog_item_id' => $catalogItemId,
                'merchant_id' => $merchantId,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
            ]);

            // Send low stock notification if threshold reached
            if ($newStock <= self::LOW_STOCK_THRESHOLD && $oldStock > self::LOW_STOCK_THRESHOLD) {
                $this->sendLowStockNotification($merchantItem);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update stock', [
                'catalog_item_id' => $catalogItemId,
                'merchant_id' => $merchantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send low stock notification to merchant
     */
    private function sendLowStockNotification(MerchantItem $merchantItem): void
    {
        try {
            $catalogEvent = new CatalogEvent();
            $catalogEvent->catalog_item_id = $merchantItem->catalog_item_id;
            $catalogEvent->user_id = $merchantItem->user_id;
            $catalogEvent->save();

            Log::info('Low stock notification sent', [
                'merchant_item_id' => $merchantItem->id,
                'merchant_id' => $merchantItem->user_id,
                'stock' => $merchantItem->stock,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to send low stock notification', [
                'merchant_item_id' => $merchantItem->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if stock is available for cart items
     *
     * @param array $cartItems
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateStockAvailability(array $cartItems): array
    {
        $errors = [];

        foreach ($cartItems as $key => $cartItem) {
            $catalogItemId = $this->extractCatalogItemId($cartItem, $key);
            $merchantId = $this->extractMerchantId($cartItem, $key);
            $requestedQty = (int) ($cartItem['qty'] ?? 0);

            if ($catalogItemId <= 0 || $merchantId <= 0) {
                continue;
            }

            $merchantItem = MerchantItem::where('catalog_item_id', $catalogItemId)
                ->where('user_id', $merchantId)
                ->first();

            if (!$merchantItem) {
                $errors[$key] = __('Item no longer available');
                continue;
            }

            if ($merchantItem->stock_check && $merchantItem->stock < $requestedQty) {
                $errors[$key] = __('Insufficient stock. Available: :stock', [
                    'stock' => $merchantItem->stock,
                ]);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Decrease stock by quantity purchased
     *
     * @param int $catalogItemId
     * @param int $merchantId
     * @param int $quantity
     * @return bool
     */
    public function decreaseStock(int $catalogItemId, int $merchantId, int $quantity): bool
    {
        $merchantItem = MerchantItem::where('catalog_item_id', $catalogItemId)
            ->where('user_id', $merchantId)
            ->first();

        if (!$merchantItem) {
            return false;
        }

        $newStock = max(0, $merchantItem->stock - $quantity);
        $this->updateMerchantItemStock($catalogItemId, $merchantId, $newStock);

        return true;
    }
}
