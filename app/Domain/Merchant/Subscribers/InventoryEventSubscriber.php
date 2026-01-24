<?php

namespace App\Domain\Merchant\Subscribers;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Inventory Event Subscriber
 *
 * Handles all inventory-related events in one place.
 */
class InventoryEventSubscriber
{
    /**
     * Handle stock updated events.
     */
    public function handleStockUpdated($event): void
    {
        Log::channel('inventory')->info('Stock updated', [
            'merchant_item_id' => $event->merchantItem->id ?? null,
            'old_stock' => $event->oldStock ?? null,
            'new_stock' => $event->newStock ?? null,
        ]);
    }

    /**
     * Handle low stock events.
     */
    public function handleLowStock($event): void
    {
        Log::channel('inventory')->warning('Low stock alert', [
            'merchant_item_id' => $event->merchantItem->id ?? null,
            'current_stock' => $event->currentStock ?? null,
            'threshold' => $event->threshold ?? null,
        ]);
    }

    /**
     * Handle out of stock events.
     */
    public function handleOutOfStock($event): void
    {
        Log::channel('inventory')->error('Out of stock', [
            'merchant_item_id' => $event->merchantItem->id ?? null,
        ]);
    }

    /**
     * Handle stock reserved events.
     */
    public function handleStockReserved($event): void
    {
        Log::channel('inventory')->info('Stock reserved', [
            'merchant_item_id' => $event->merchantItem->id ?? null,
            'quantity' => $event->quantity ?? null,
            'order_id' => $event->orderId ?? null,
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            'App\Domain\Merchant\Events\StockUpdated' => 'handleStockUpdated',
            'App\Domain\Merchant\Events\LowStock' => 'handleLowStock',
            'App\Domain\Merchant\Events\OutOfStock' => 'handleOutOfStock',
            'App\Domain\Merchant\Events\StockReserved' => 'handleStockReserved',
        ];
    }
}
