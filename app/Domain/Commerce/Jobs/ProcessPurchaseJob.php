<?php

namespace App\Domain\Commerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Commerce\Events\PurchasePlacedEvent;

/**
 * Process Purchase Job
 *
 * Processes a new purchase after placement.
 */
class ProcessPurchaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Purchase $purchase
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Update purchase status to confirmed
        $this->purchase->update(['status' => 'confirmed']);

        // Create merchant purchases
        $this->createMerchantPurchases();

        // Dispatch purchase placed event
        event(PurchasePlacedEvent::fromPurchase($this->purchase));
    }

    /**
     * Create merchant-specific purchases
     */
    protected function createMerchantPurchases(): void
    {
        $cartItems = $this->purchase->getCartItems();
        $groupedByMerchant = collect($cartItems)->groupBy('user_id');

        foreach ($groupedByMerchant as $merchantId => $items) {
            $this->purchase->merchantPurchases()->create([
                'user_id' => $merchantId,
                'status' => 'pending',
                'cart' => $items->toArray(),
                'total' => $items->sum(fn($i) => $i['price'] * $i['quantity']),
            ]);
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Log failure
        \Log::error('ProcessPurchaseJob failed', [
            'purchase_id' => $this->purchase->id,
            'error' => $exception->getMessage(),
        ]);

        // Mark purchase as failed
        $this->purchase->update([
            'status' => 'failed',
            'notes' => 'Processing failed: ' . $exception->getMessage(),
        ]);
    }
}
