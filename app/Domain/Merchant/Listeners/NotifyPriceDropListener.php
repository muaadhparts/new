<?php

namespace App\Domain\Merchant\Listeners;

use App\Domain\Merchant\Events\PriceChangedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Notify Price Drop Listener
 *
 * Notifies customers who have favorited the item when price drops.
 *
 * Channel Independence: This listener handles price drop notifications
 * for ALL channels (Web, Mobile, API, WhatsApp).
 */
class NotifyPriceDropListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $backoff = 60;
    public string $queue = 'notifications';

    /**
     * Handle the event.
     */
    public function handle(PriceChangedEvent $event): void
    {
        // Only notify for price drops (not increases)
        if (!$event->wasDecreased() && !$event->discountWasApplied()) {
            return;
        }

        Log::channel('domain')->info('Price drop detected', [
            'event_id' => $event->eventId,
            'merchant_item_id' => $event->merchantItemId,
            'catalog_item_id' => $event->catalogItemId,
            'previous_price' => $event->previousPrice,
            'new_price' => $event->newPrice,
            'change_percent' => $event->priceChangePercent(),
        ]);

        // TODO: Notify customers who have this item in wishlist
        // $this->notifyWishlistCustomers($event);
    }

    /**
     * Handle a job failure.
     */
    public function failed(PriceChangedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to process price drop notification', [
            'merchant_item_id' => $event->merchantItemId,
            'error' => $exception->getMessage(),
        ]);
    }
}
