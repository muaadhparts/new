<?php

namespace App\Domain\Catalog\Listeners;

use App\Domain\Catalog\Events\ProductReviewedEvent;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Identity\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Notify Merchant Of Review Listener
 *
 * Notifies merchants when their products receive reviews.
 */
class NotifyMerchantOfReviewListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /**
     * Handle the event.
     */
    public function handle(ProductReviewedEvent $event): void
    {
        // Find all merchants selling this item
        $merchantIds = MerchantItem::where('catalog_item_id', $event->catalogItemId)
            ->where('status', 1)
            ->pluck('merchant_id')
            ->unique();

        if ($merchantIds->isEmpty()) {
            return;
        }

        $merchants = User::whereIn('id', $merchantIds)
            ->where('status', 1)
            ->get();

        foreach ($merchants as $merchant) {
            $this->notifyMerchant($merchant, $event);
        }
    }

    /**
     * Notify a merchant about the review
     */
    protected function notifyMerchant(User $merchant, ProductReviewedEvent $event): void
    {
        $catalogItem = CatalogItem::find($event->catalogItemId);

        Log::info('Merchant notified of new review', [
            'merchant_id' => $merchant->id,
            'catalog_item_id' => $event->catalogItemId,
            'item_name' => $catalogItem?->name,
            'rating' => $event->rating,
            'is_positive' => $event->isPositive(),
        ]);

        // Notification::send($merchant, new NewProductReviewNotification($event));
    }

    /**
     * Handle a job failure.
     */
    public function failed(ProductReviewedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to notify merchant of review', [
            'catalog_item_id' => $event->catalogItemId,
            'error' => $exception->getMessage(),
        ]);
    }
}
