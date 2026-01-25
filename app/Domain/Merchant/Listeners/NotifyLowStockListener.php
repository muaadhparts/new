<?php

namespace App\Domain\Merchant\Listeners;

use App\Domain\Merchant\Events\StockUpdatedEvent;
use App\Domain\Identity\Models\User;
use App\Domain\Merchant\Models\MerchantItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Notify Low Stock Listener
 *
 * Notifies merchant when stock falls below threshold.
 */
class NotifyLowStockListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /**
     * Default low stock threshold
     */
    protected int $defaultThreshold = 5;

    /**
     * Handle the event.
     */
    public function handle(StockUpdatedEvent $event): void
    {
        // Only check if stock decreased
        if (!$event->wasDecreased()) {
            return;
        }

        // Check if now low stock
        if (!$event->isLowStock($this->getThreshold($event->merchantId))) {
            return;
        }

        // Don't notify if was already low stock
        if ($event->previousStock <= $this->getThreshold($event->merchantId)) {
            return;
        }

        $this->sendLowStockNotification($event);
    }

    /**
     * Get threshold for merchant
     */
    protected function getThreshold(int $merchantId): int
    {
        // Could be fetched from merchant settings
        return $this->defaultThreshold;
    }

    /**
     * Send the notification
     */
    protected function sendLowStockNotification(StockUpdatedEvent $event): void
    {
        $merchant = User::find($event->merchantId);
        $item = MerchantItem::with('catalogItem')->find($event->merchantItemId);

        if (!$merchant || !$item) {
            return;
        }

        Log::warning('Low stock alert', [
            'merchant_id' => $event->merchantId,
            'merchant_item_id' => $event->merchantItemId,
            'item_name' => $item->catalogItem?->name,
            'current_stock' => $event->newStock,
            'threshold' => $this->getThreshold($event->merchantId),
        ]);

        // Send notification (email, SMS, push, etc.)
        // Notification::send($merchant, new LowStockNotification($item, $event->newStock));
    }

    /**
     * Handle a job failure.
     */
    public function failed(StockUpdatedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to send low stock notification', [
            'merchant_item_id' => $event->merchantItemId,
            'error' => $exception->getMessage(),
        ]);
    }
}
