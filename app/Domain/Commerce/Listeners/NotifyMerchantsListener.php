<?php

namespace App\Domain\Commerce\Listeners;

use App\Domain\Commerce\Events\OrderPlacedEvent;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Notify Merchants Listener
 *
 * Notifies merchants when they receive a new order.
 */
class NotifyMerchantsListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $backoff = 30;

    /**
     * Handle the event.
     */
    public function handle(OrderPlacedEvent $event): void
    {
        if (empty($event->merchantIds)) {
            return;
        }

        $merchants = User::whereIn('id', $event->merchantIds)
            ->where('status', 1)
            ->get();

        foreach ($merchants as $merchant) {
            $this->notifyMerchant($merchant, $event);
        }

        Log::info('Merchants notified of new order', [
            'purchase_id' => $event->purchaseId,
            'merchant_count' => $merchants->count(),
        ]);
    }

    /**
     * Notify a single merchant
     */
    protected function notifyMerchant(User $merchant, OrderPlacedEvent $event): void
    {
        // Implementation depends on notification system
        // Could be database notification, email, SMS, push, etc.

        Log::info('Would notify merchant of new order', [
            'merchant_id' => $merchant->id,
            'merchant_email' => $merchant->email,
            'purchase_id' => $event->purchaseId,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(OrderPlacedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to notify merchants', [
            'purchase_id' => $event->purchaseId,
            'merchant_ids' => $event->merchantIds,
            'error' => $exception->getMessage(),
        ]);
    }
}
