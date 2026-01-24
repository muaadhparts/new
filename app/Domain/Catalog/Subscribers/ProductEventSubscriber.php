<?php

namespace App\Domain\Catalog\Subscribers;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Product Event Subscriber
 *
 * Handles all product-related events in one place.
 */
class ProductEventSubscriber
{
    /**
     * Handle product created events.
     */
    public function handleProductCreated($event): void
    {
        Log::channel('catalog')->info('Product created', [
            'catalog_item_id' => $event->catalogItem->id ?? null,
            'sku' => $event->catalogItem->sku ?? null,
        ]);
    }

    /**
     * Handle product updated events.
     */
    public function handleProductUpdated($event): void
    {
        Log::channel('catalog')->info('Product updated', [
            'catalog_item_id' => $event->catalogItem->id ?? null,
        ]);
    }

    /**
     * Handle product viewed events.
     */
    public function handleProductViewed($event): void
    {
        Log::channel('catalog')->debug('Product viewed', [
            'catalog_item_id' => $event->catalogItem->id ?? null,
            'user_id' => $event->userId ?? null,
        ]);
    }

    /**
     * Handle product reviewed events.
     */
    public function handleProductReviewed($event): void
    {
        Log::channel('catalog')->info('Product reviewed', [
            'catalog_item_id' => $event->catalogItem->id ?? null,
            'rating' => $event->rating ?? null,
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            'App\Domain\Catalog\Events\ProductCreated' => 'handleProductCreated',
            'App\Domain\Catalog\Events\ProductUpdated' => 'handleProductUpdated',
            'App\Domain\Catalog\Events\ProductViewed' => 'handleProductViewed',
            'App\Domain\Catalog\Events\ProductReviewed' => 'handleProductReviewed',
        ];
    }
}
