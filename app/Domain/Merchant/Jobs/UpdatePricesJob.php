<?php

namespace App\Domain\Merchant\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Merchant\Models\MerchantItem;

/**
 * Update Prices Job
 *
 * Bulk updates prices for merchant items.
 */
class UpdatePricesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $merchantId,
        public array $priceUpdates
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $updated = 0;

        foreach ($this->priceUpdates as $update) {
            $item = MerchantItem::where('user_id', $this->merchantId)
                ->where('id', $update['id'])
                ->first();

            if (!$item) {
                continue;
            }

            $updateData = ['price' => $update['price']];

            // Store previous price if significant change
            if (isset($update['price']) && $item->price != $update['price']) {
                $updateData['previous_price'] = $item->price;
            }

            if (isset($update['discount'])) {
                $updateData['discount'] = $update['discount'];
            }

            $item->update($updateData);
            $updated++;
        }

        \Log::info('Bulk price update completed', [
            'merchant_id' => $this->merchantId,
            'updated' => $updated,
            'total' => count($this->priceUpdates),
        ]);
    }
}
