<?php

namespace App\Domain\Commerce\Listeners;

use App\Domain\Commerce\Events\PaymentReceivedEvent;
use App\Domain\Commerce\Models\Purchase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Update Purchase Status Listener
 *
 * Updates purchase status when payment is received.
 */
class UpdatePurchaseStatusListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /**
     * Handle the event.
     */
    public function handle(PaymentReceivedEvent $event): void
    {
        $purchase = Purchase::find($event->purchaseId);

        if (!$purchase) {
            Log::warning('UpdatePurchaseStatus: Purchase not found', [
                'purchase_id' => $event->purchaseId,
            ]);
            return;
        }

        // Only update if currently pending payment
        if ($purchase->status !== 'pending') {
            Log::info('UpdatePurchaseStatus: Purchase not in pending status', [
                'purchase_id' => $event->purchaseId,
                'current_status' => $purchase->status,
            ]);
            return;
        }

        // Check if full payment received
        if ($event->isFullPayment((float) $purchase->total)) {
            $purchase->update([
                'status' => 'processing',
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            Log::info('Purchase status updated to processing', [
                'purchase_id' => $event->purchaseId,
                'payment_amount' => $event->amount,
            ]);
        } else {
            Log::warning('Partial payment received', [
                'purchase_id' => $event->purchaseId,
                'expected' => $purchase->total,
                'received' => $event->amount,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(PaymentReceivedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to update purchase status after payment', [
            'purchase_id' => $event->purchaseId,
            'error' => $exception->getMessage(),
        ]);
    }
}
