<?php

namespace App\Domain\Merchant\Actions;

use App\Domain\Merchant\Models\MerchantItem;

/**
 * UpdatePriceAction - Update merchant item price
 *
 * Single-responsibility action for price updates.
 * Handles previous price tracking for discount display.
 *
 * Discount is determined by: previous_price > price
 */
class UpdatePriceAction
{
    /**
     * Execute the action
     *
     * @param int $merchantItemId Merchant item ID
     * @param float $newPrice New price
     * @param bool $trackPrevious Whether to store previous price for discount display
     * @return array{success: bool, message: string, item?: MerchantItem}
     */
    public function execute(
        int $merchantItemId,
        float $newPrice,
        bool $trackPrevious = true
    ): array {
        $merchantItem = MerchantItem::find($merchantItemId);

        if (!$merchantItem) {
            return [
                'success' => false,
                'message' => __('Item not found'),
            ];
        }

        if ($newPrice < 0) {
            return [
                'success' => false,
                'message' => __('Price cannot be negative'),
            ];
        }

        $oldPrice = $merchantItem->price;

        // Track previous price if reducing price (for discount display)
        if ($trackPrevious && $newPrice < $oldPrice) {
            $merchantItem->previous_price = $oldPrice;
        }

        $merchantItem->price = $newPrice;
        $merchantItem->save();

        return [
            'success' => true,
            'message' => __('Price updated successfully'),
            'item' => $merchantItem->fresh(),
        ];
    }

    /**
     * Apply discount
     *
     * @param int $merchantItemId
     * @param float $discountPercent Discount percentage (0-100)
     * @return array
     */
    public function applyDiscount(
        int $merchantItemId,
        float $discountPercent
    ): array {
        $merchantItem = MerchantItem::find($merchantItemId);

        if (!$merchantItem) {
            return [
                'success' => false,
                'message' => __('Item not found'),
            ];
        }

        if ($discountPercent < 0 || $discountPercent > 100) {
            return [
                'success' => false,
                'message' => __('Discount must be between 0 and 100'),
            ];
        }

        $originalPrice = $merchantItem->previous_price ?: $merchantItem->price;
        $newPrice = $originalPrice * (1 - $discountPercent / 100);

        $merchantItem->previous_price = $originalPrice;
        $merchantItem->price = round($newPrice, 2);
        $merchantItem->save();

        return [
            'success' => true,
            'message' => __('Discount applied successfully'),
            'item' => $merchantItem->fresh(),
        ];
    }

    /**
     * Remove discount
     *
     * @param int $merchantItemId
     * @return array
     */
    public function removeDiscount(int $merchantItemId): array
    {
        $merchantItem = MerchantItem::find($merchantItemId);

        if (!$merchantItem) {
            return [
                'success' => false,
                'message' => __('Item not found'),
            ];
        }

        // Restore original price if available
        if ($merchantItem->previous_price > 0) {
            $merchantItem->price = $merchantItem->previous_price;
        }

        $merchantItem->previous_price = null;
        $merchantItem->save();

        return [
            'success' => true,
            'message' => __('Discount removed successfully'),
            'item' => $merchantItem->fresh(),
        ];
    }
}
