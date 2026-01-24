<?php

namespace App\Domain\Commerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Commerce\Events\OrderPlacedEvent;

/**
 * Process Order Job
 *
 * Processes a new order after placement.
 */
class ProcessOrderJob implements ShouldQueue
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
        // Update order status to confirmed
        $this->purchase->update(['status' => 'confirmed']);

        // Create merchant purchases
        $this->createMerchantPurchases();

        // Dispatch order placed event
        event(new OrderPlacedEvent($this->purchase));
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
        \Log::error('ProcessOrderJob failed', [
            'purchase_id' => $this->purchase->id,
            'error' => $exception->getMessage(),
        ]);

        // Mark order as failed
        $this->purchase->update([
            'status' => 'failed',
            'notes' => 'Processing failed: ' . $exception->getMessage(),
        ]);
    }
}
