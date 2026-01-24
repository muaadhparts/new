<?php

namespace App\Domain\Commerce\Subscribers;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Order Event Subscriber
 *
 * Handles all order-related events in one place.
 */
class OrderEventSubscriber
{
    /**
     * Handle order placed events.
     */
    public function handleOrderPlaced($event): void
    {
        Log::channel('orders')->info('Order placed', [
            'order_id' => $event->purchase->id ?? null,
            'user_id' => $event->purchase->user_id ?? null,
            'total' => $event->purchase->total ?? null,
        ]);
    }

    /**
     * Handle order confirmed events.
     */
    public function handleOrderConfirmed($event): void
    {
        Log::channel('orders')->info('Order confirmed', [
            'order_id' => $event->purchase->id ?? null,
        ]);
    }

    /**
     * Handle order cancelled events.
     */
    public function handleOrderCancelled($event): void
    {
        Log::channel('orders')->info('Order cancelled', [
            'order_id' => $event->purchase->id ?? null,
            'reason' => $event->reason ?? null,
        ]);
    }

    /**
     * Handle order completed events.
     */
    public function handleOrderCompleted($event): void
    {
        Log::channel('orders')->info('Order completed', [
            'order_id' => $event->purchase->id ?? null,
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            'App\Domain\Commerce\Events\OrderPlaced' => 'handleOrderPlaced',
            'App\Domain\Commerce\Events\OrderConfirmed' => 'handleOrderConfirmed',
            'App\Domain\Commerce\Events\OrderCancelled' => 'handleOrderCancelled',
            'App\Domain\Commerce\Events\OrderCompleted' => 'handleOrderCompleted',
        ];
    }
}
