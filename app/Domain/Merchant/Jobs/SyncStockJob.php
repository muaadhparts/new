<?php

namespace App\Domain\Merchant\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Merchant\Events\StockUpdatedEvent;

/**
 * Sync Stock Job
 *
 * Syncs stock from external sources or updates stock status.
 */
class SyncStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $merchantId,
        public array $stockData = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->stockData as $item) {
            $merchantItem = MerchantItem::where('user_id', $this->merchantId)
                ->where('id', $item['id'])
                ->first();

            if (!$merchantItem) {
                continue;
            }

            $oldStock = $merchantItem->stock;
            $newStock = $item['stock'];

            if ($oldStock !== $newStock) {
                $merchantItem->update(['stock' => $newStock]);

                event(new StockUpdatedEvent(
                    $merchantItem,
                    $oldStock,
                    $newStock,
                    'sync'
                ));
            }
        }

        \Log::info('Stock sync completed', [
            'merchant_id' => $this->merchantId,
            'items_count' => count($this->stockData),
        ]);
    }
}
