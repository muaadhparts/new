<?php

namespace App\Domain\Merchant\Observers;

use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Merchant\Models\MerchantStockUpdate;
use App\Domain\Merchant\Events\StockUpdatedEvent;
use App\Domain\Merchant\Events\PriceChangedEvent;

/**
 * Merchant Item Observer
 *
 * Handles MerchantItem model lifecycle events.
 */
class MerchantItemObserver
{
    /**
     * Handle the MerchantItem "creating" event.
     */
    public function creating(MerchantItem $merchantItem): void
    {
        // Set default status
        if (!isset($merchantItem->status)) {
            $merchantItem->status = 1;
        }

        // Set default stock
        if (!isset($merchantItem->stock)) {
            $merchantItem->stock = 0;
        }
    }

    /**
     * Handle the MerchantItem "updating" event.
     */
    public function updating(MerchantItem $merchantItem): void
    {
        // Track stock changes
        if ($merchantItem->isDirty('stock')) {
            $oldStock = $merchantItem->getOriginal('stock');
            $newStock = $merchantItem->stock;
            $difference = $newStock - $oldStock;

            // Log stock update
            MerchantStockUpdate::create([
                'merchant_item_id' => $merchantItem->id,
                'merchant_id' => $merchantItem->merchant_id,
                'previous_stock' => $oldStock,
                'new_stock' => $newStock,
                'change' => $difference,
                'reason' => $merchantItem->stock_update_reason ?? 'manual_update',
                'updated_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Handle the MerchantItem "updated" event.
     */
    public function updated(MerchantItem $merchantItem): void
    {
        // Clear the temporary stock update reason
        unset($merchantItem->stock_update_reason);

        // Check for low stock notification
        if ($merchantItem->wasChanged('stock')) {
            $this->checkLowStock($merchantItem);
        }

        // Check for price changes
        if ($merchantItem->wasChanged('price') || $merchantItem->wasChanged('discount_price')) {
            $this->dispatchPriceChangedEvent($merchantItem);
        }
    }

    /**
     * Dispatch PriceChangedEvent when price or discount changes
     */
    protected function dispatchPriceChangedEvent(MerchantItem $merchantItem): void
    {
        $previousPrice = (float) $merchantItem->getOriginal('price');
        $newPrice = (float) $merchantItem->price;
        $previousDiscount = $merchantItem->getOriginal('discount_price');
        $newDiscount = $merchantItem->discount_price;

        // ═══════════════════════════════════════════════════════════════════
        // EVENT-DRIVEN: Dispatch PriceChangedEvent for ALL price changes
        // Listeners can notify interested customers, update caches, etc.
        // ═══════════════════════════════════════════════════════════════════
        event(new PriceChangedEvent(
            merchantItemId: $merchantItem->id,
            merchantId: $merchantItem->merchant_id,
            catalogItemId: $merchantItem->item_id,
            previousPrice: $previousPrice,
            newPrice: $newPrice,
            previousDiscount: $previousDiscount ? (float) $previousDiscount : null,
            newDiscount: $newDiscount ? (float) $newDiscount : null,
            changedBy: auth()->id()
        ));
    }

    /**
     * Check and notify for low stock
     *
     * Dispatches StockUpdatedEvent for all stock changes.
     * Listeners handle notifications based on stock levels.
     */
    protected function checkLowStock(MerchantItem $merchantItem): void
    {
        $previousStock = (int) $merchantItem->getOriginal('stock');
        $newStock = (int) $merchantItem->stock;

        // ═══════════════════════════════════════════════════════════════════
        // EVENT-DRIVEN: Dispatch StockUpdatedEvent for ALL stock changes
        // Listeners decide what to do based on stock levels
        // ═══════════════════════════════════════════════════════════════════
        event(new StockUpdatedEvent(
            merchantItemId: $merchantItem->id,
            merchantId: $merchantItem->merchant_id,
            catalogItemId: $merchantItem->item_id,
            previousStock: $previousStock,
            newStock: $newStock,
            reason: $merchantItem->stock_update_reason ?? 'system',
            updatedBy: auth()->id()
        ));
    }
}
