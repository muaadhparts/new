<?php

namespace App\Domain\Commerce\Subscribers;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Purchase Event Subscriber
 *
 * Handles all purchase-related events in one place.
 */
class PurchaseEventSubscriber
{
    /**
     * Handle purchase placed events.
     */
    public function handlePurchasePlaced($event): void
    {
        Log::channel('purchases')->info('Purchase placed', [
            'purchase_id' => $event->purchaseId ?? null,
            'customer_id' => $event->customerId ?? null,
            'total' => $event->totalAmount ?? null,
        ]);
    }

    /**
     * Handle purchase status changed events.
     */
    public function handlePurchaseStatusChanged($event): void
    {
        Log::channel('purchases')->info('Purchase status changed', [
            'purchase_id' => $event->purchaseId ?? null,
            'previous_status' => $event->previousStatus ?? null,
            'new_status' => $event->newStatus ?? null,
        ]);
    }

    /**
     * Handle payment received events.
     */
    public function handlePaymentReceived($event): void
    {
        Log::channel('purchases')->info('Payment received', [
            'purchase_id' => $event->purchaseId ?? null,
            'amount' => $event->amount ?? null,
            'payment_method' => $event->paymentMethod ?? null,
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            'App\Domain\Commerce\Events\PurchasePlacedEvent' => 'handlePurchasePlaced',
            'App\Domain\Commerce\Events\PurchaseStatusChangedEvent' => 'handlePurchaseStatusChanged',
            'App\Domain\Commerce\Events\PaymentReceivedEvent' => 'handlePaymentReceived',
        ];
    }
}
