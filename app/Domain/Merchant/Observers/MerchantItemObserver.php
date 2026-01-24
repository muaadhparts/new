<?php

namespace App\Domain\Merchant\Observers;

use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Merchant\Models\MerchantStockUpdate;

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
    }

    /**
     * Check and notify for low stock
     */
    protected function checkLowStock(MerchantItem $merchantItem): void
    {
        $threshold = $merchantItem->merchant?->merchantSetting?->low_stock_threshold ?? 5;

        if ($merchantItem->stock <= 0) {
            // Out of stock - could dispatch event
            event(new \App\Domain\Merchant\Events\StockUpdatedEvent(
                $merchantItem,
                $merchantItem->getOriginal('stock'),
                0,
                'system'
            ));
        } elseif ($merchantItem->stock <= $threshold) {
            // Low stock - could dispatch event
            event(new \App\Domain\Merchant\Events\StockUpdatedEvent(
                $merchantItem,
                $merchantItem->getOriginal('stock'),
                $merchantItem->stock,
                'system'
            ));
        }
    }
}
