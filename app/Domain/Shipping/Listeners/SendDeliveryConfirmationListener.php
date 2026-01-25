<?php

namespace App\Domain\Shipping\Listeners;

use App\Domain\Shipping\Events\DeliveryCompletedEvent;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Identity\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Delivery Confirmation Listener
 *
 * Notifies customer when their order has been delivered.
 */
class SendDeliveryConfirmationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /**
     * Handle the event.
     */
    public function handle(DeliveryCompletedEvent $event): void
    {
        $purchase = Purchase::find($event->purchaseId);

        if (!$purchase) {
            return;
        }

        $customer = User::find($event->customerId);

        if (!$customer) {
            return;
        }

        $this->sendDeliveryConfirmation($customer, $purchase, $event);
        $this->updatePurchaseStatus($purchase);
    }

    /**
     * Send delivery confirmation
     */
    protected function sendDeliveryConfirmation(User $customer, Purchase $purchase, DeliveryCompletedEvent $event): void
    {
        Log::info('Delivery confirmation sent', [
            'customer_id' => $customer->id,
            'purchase_id' => $event->purchaseId,
            'received_by' => $event->receivedBy,
        ]);

        // Mail::to($customer->email)->send(new DeliveryConfirmationMail($purchase));
    }

    /**
     * Update purchase status to delivered
     */
    protected function updatePurchaseStatus(Purchase $purchase): void
    {
        if ($purchase->status !== 'delivered') {
            $purchase->update([
                'status' => 'delivered',
                'delivered_at' => now(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(DeliveryCompletedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to send delivery confirmation', [
            'purchase_id' => $event->purchaseId,
            'error' => $exception->getMessage(),
        ]);
    }
}
