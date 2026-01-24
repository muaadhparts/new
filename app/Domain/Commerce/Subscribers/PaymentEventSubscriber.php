<?php

namespace App\Domain\Commerce\Subscribers;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Payment Event Subscriber
 *
 * Handles all payment-related events in one place.
 */
class PaymentEventSubscriber
{
    /**
     * Handle payment initiated events.
     */
    public function handlePaymentInitiated($event): void
    {
        Log::channel('payments')->info('Payment initiated', [
            'order_id' => $event->purchase->id ?? null,
            'method' => $event->method ?? null,
            'amount' => $event->amount ?? null,
        ]);
    }

    /**
     * Handle payment completed events.
     */
    public function handlePaymentCompleted($event): void
    {
        Log::channel('payments')->info('Payment completed', [
            'order_id' => $event->purchase->id ?? null,
            'transaction_id' => $event->transactionId ?? null,
        ]);
    }

    /**
     * Handle payment failed events.
     */
    public function handlePaymentFailed($event): void
    {
        Log::channel('payments')->error('Payment failed', [
            'order_id' => $event->purchase->id ?? null,
            'error' => $event->error ?? null,
        ]);
    }

    /**
     * Handle payment refunded events.
     */
    public function handlePaymentRefunded($event): void
    {
        Log::channel('payments')->info('Payment refunded', [
            'order_id' => $event->purchase->id ?? null,
            'amount' => $event->amount ?? null,
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            'App\Domain\Commerce\Events\PaymentInitiated' => 'handlePaymentInitiated',
            'App\Domain\Commerce\Events\PaymentCompleted' => 'handlePaymentCompleted',
            'App\Domain\Commerce\Events\PaymentFailed' => 'handlePaymentFailed',
            'App\Domain\Commerce\Events\PaymentRefunded' => 'handlePaymentRefunded',
        ];
    }
}
