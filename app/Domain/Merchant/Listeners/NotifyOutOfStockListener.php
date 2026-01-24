<?php

namespace App\Domain\Merchant\Listeners;

use App\Domain\Merchant\Events\StockUpdatedEvent;
use App\Models\User;
use App\Models\MerchantItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Notify Out Of Stock Listener
 *
 * Notifies merchant when item goes out of stock.
 */
class NotifyOutOfStockListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /**
     * Handle the event.
     */
    public function handle(StockUpdatedEvent $event): void
    {
        // Only notify if item just went out of stock
        if (!$event->isNowOutOfStock()) {
            return;
        }

        $this->sendOutOfStockNotification($event);
    }

    /**
     * Send the notification
     */
    protected function sendOutOfStockNotification(StockUpdatedEvent $event): void
    {
        $merchant = User::find($event->merchantId);
        $item = MerchantItem::with('catalogItem')->find($event->merchantItemId);

        if (!$merchant || !$item) {
            return;
        }

        Log::warning('Out of stock alert', [
            'merchant_id' => $event->merchantId,
            'merchant_item_id' => $event->merchantItemId,
            'item_name' => $item->catalogItem?->name,
            'previous_stock' => $event->previousStock,
        ]);

        // Could also notify customers who had this in their cart or wishlist
        // Notification::send($merchant, new OutOfStockNotification($item));
    }

    /**
     * Handle a job failure.
     */
    public function failed(StockUpdatedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to send out of stock notification', [
            'merchant_item_id' => $event->merchantItemId,
            'error' => $exception->getMessage(),
        ]);
    }
}
