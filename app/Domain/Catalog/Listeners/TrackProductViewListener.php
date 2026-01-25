<?php

namespace App\Domain\Catalog\Listeners;

use App\Domain\Catalog\Events\ProductViewedEvent;
use App\Domain\Catalog\Models\CatalogItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Track Product View Listener
 *
 * Tracks product views for analytics and popularity ranking.
 */
class TrackProductViewListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /**
     * The queue this job should be dispatched to.
     */
    public string $queue = 'analytics';

    /**
     * Handle the event.
     */
    public function handle(ProductViewedEvent $event): void
    {
        $this->incrementViewCount($event->catalogItemId);
        $this->logViewForAnalytics($event);
    }

    /**
     * Increment the view count
     */
    protected function incrementViewCount(int $catalogItemId): void
    {
        CatalogItem::where('id', $catalogItemId)
            ->increment('views');
    }

    /**
     * Log view for analytics
     */
    protected function logViewForAnalytics(ProductViewedEvent $event): void
    {
        // Could store in analytics table for detailed tracking
        // DB::table('product_views')->insert([
        //     'catalog_item_id' => $event->catalogItemId,
        //     'user_id' => $event->customerId,
        //     'session_id' => $event->sessionId,
        //     'source' => $event->source,
        //     'created_at' => $event->occurredAt,
        // ]);

        Log::debug('Product view tracked', [
            'catalog_item_id' => $event->catalogItemId,
            'user_id' => $event->customerId,
            'source' => $event->source,
            'is_authenticated' => $event->isAuthenticated(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(ProductViewedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to track product view', [
            'catalog_item_id' => $event->catalogItemId,
            'error' => $exception->getMessage(),
        ]);
    }
}
