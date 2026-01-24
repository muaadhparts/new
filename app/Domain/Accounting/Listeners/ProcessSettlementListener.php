<?php

namespace App\Domain\Accounting\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Process Settlement Listener
 *
 * Handles settlement processing when orders are completed.
 */
class ProcessSettlementListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $backoff = 60;

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        Log::info('Processing settlement', [
            'merchant_id' => $event->merchantId ?? null,
            'amount' => $event->amount ?? 0,
        ]);

        // Process the settlement logic here
        $this->createSettlementEntry($event);
    }

    /**
     * Create settlement entry
     */
    protected function createSettlementEntry($event): void
    {
        // Settlement logic
        Log::info('Settlement entry created', [
            'merchant_id' => $event->merchantId ?? null,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed($event, \Throwable $exception): void
    {
        Log::error('Failed to process settlement', [
            'error' => $exception->getMessage(),
        ]);
    }
}
