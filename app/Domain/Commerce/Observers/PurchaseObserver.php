<?php

namespace App\Domain\Commerce\Observers;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Commerce\Models\PurchaseTimeline;
use Illuminate\Support\Str;

/**
 * Purchase Observer
 *
 * Handles Purchase model lifecycle events.
 */
class PurchaseObserver
{
    /**
     * Handle the Purchase "creating" event.
     */
    public function creating(Purchase $purchase): void
    {
        // Generate order number if not set
        if (empty($purchase->order_number)) {
            $purchase->order_number = $this->generateOrderNumber();
        }

        // Set default status
        if (empty($purchase->status)) {
            $purchase->status = 'pending';
        }

        // Set default payment status
        if (empty($purchase->payment_status)) {
            $purchase->payment_status = 'pending';
        }
    }

    /**
     * Handle the Purchase "created" event.
     */
    public function created(Purchase $purchase): void
    {
        // Create initial timeline entry
        PurchaseTimeline::create([
            'purchase_id' => $purchase->id,
            'status' => $purchase->status,
            'comment' => __('order.timeline.created'),
            'actor_type' => 'system',
        ]);
    }

    /**
     * Handle the Purchase "updating" event.
     */
    public function updating(Purchase $purchase): void
    {
        // Track status changes
        if ($purchase->isDirty('status')) {
            $purchase->status_changed_at = now();
        }
    }

    /**
     * Handle the Purchase "updated" event.
     */
    public function updated(Purchase $purchase): void
    {
        // Create timeline entry for status changes
        if ($purchase->wasChanged('status')) {
            PurchaseTimeline::create([
                'purchase_id' => $purchase->id,
                'status' => $purchase->status,
                'comment' => __('order.timeline.status_changed', ['status' => $purchase->status]),
                'actor_type' => 'system',
            ]);
        }
    }

    /**
     * Generate unique order number
     */
    protected function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(6));

        return "{$prefix}-{$date}-{$random}";
    }
}
