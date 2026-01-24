<?php

namespace App\Domain\Commerce\Observers;

use App\Domain\Commerce\Models\MerchantPurchase;

/**
 * Merchant Purchase Observer
 *
 * Handles MerchantPurchase model lifecycle events.
 */
class MerchantPurchaseObserver
{
    /**
     * Handle the MerchantPurchase "creating" event.
     */
    public function creating(MerchantPurchase $merchantPurchase): void
    {
        // Set default status
        if (empty($merchantPurchase->status)) {
            $merchantPurchase->status = 'pending';
        }

        // Calculate totals if not set
        if (empty($merchantPurchase->total)) {
            $merchantPurchase->total = $merchantPurchase->subtotal
                + ($merchantPurchase->shipping_cost ?? 0)
                + ($merchantPurchase->tax ?? 0)
                - ($merchantPurchase->discount ?? 0);
        }
    }

    /**
     * Handle the MerchantPurchase "updating" event.
     */
    public function updating(MerchantPurchase $merchantPurchase): void
    {
        // Track status changes
        if ($merchantPurchase->isDirty('status')) {
            $newStatus = $merchantPurchase->status;

            // Set timestamp based on status
            switch ($newStatus) {
                case 'accepted':
                    $merchantPurchase->accepted_at = now();
                    break;
                case 'processing':
                    $merchantPurchase->processing_at = now();
                    break;
                case 'shipped':
                    $merchantPurchase->shipped_at = now();
                    break;
                case 'delivered':
                    $merchantPurchase->delivered_at = now();
                    break;
                case 'cancelled':
                    $merchantPurchase->cancelled_at = now();
                    break;
            }
        }
    }

    /**
     * Handle the MerchantPurchase "updated" event.
     */
    public function updated(MerchantPurchase $merchantPurchase): void
    {
        // Update parent purchase status if all merchant purchases are complete
        if ($merchantPurchase->wasChanged('status')) {
            $this->syncParentPurchaseStatus($merchantPurchase);
        }
    }

    /**
     * Sync parent purchase status based on merchant purchases
     */
    protected function syncParentPurchaseStatus(MerchantPurchase $merchantPurchase): void
    {
        $purchase = $merchantPurchase->purchase;
        if (!$purchase) {
            return;
        }

        $statuses = $purchase->merchantPurchases()->pluck('status')->unique();

        // All delivered = delivered
        if ($statuses->count() === 1 && $statuses->first() === 'delivered') {
            $purchase->update(['status' => 'delivered']);
        }
        // All cancelled = cancelled
        elseif ($statuses->count() === 1 && $statuses->first() === 'cancelled') {
            $purchase->update(['status' => 'cancelled']);
        }
        // Any shipped = shipped
        elseif ($statuses->contains('shipped')) {
            $purchase->update(['status' => 'shipped']);
        }
        // Any processing = processing
        elseif ($statuses->contains('processing')) {
            $purchase->update(['status' => 'processing']);
        }
    }
}
