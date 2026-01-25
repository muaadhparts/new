<?php

namespace App\Domain\Merchant\Listeners;

use App\Domain\Merchant\Events\StockUpdatedEvent;
use App\Domain\Merchant\Models\MerchantStockUpdate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Log Stock Change Listener
 *
 * Logs all stock changes for audit and tracking purposes.
 */
class LogStockChangeListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /**
     * Handle the event.
     */
    public function handle(StockUpdatedEvent $event): void
    {
        // Create audit log entry
        MerchantStockUpdate::create([
            'merchant_id' => $event->merchantId,
            'merchant_item_id' => $event->merchantItemId,
            'catalog_item_id' => $event->catalogItemId,
            'previous_stock' => $event->previousStock,
            'new_stock' => $event->newStock,
            'change' => $event->stockChange(),
            'reason' => $event->reason,
            'updated_by' => $event->updatedBy,
            'created_at' => $event->occurredAt,
        ]);

        Log::info('Stock change logged', [
            'merchant_item_id' => $event->merchantItemId,
            'change' => $event->stockChange(),
            'reason' => $event->reason,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(StockUpdatedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to log stock change', [
            'merchant_item_id' => $event->merchantItemId,
            'error' => $exception->getMessage(),
        ]);
    }
}
