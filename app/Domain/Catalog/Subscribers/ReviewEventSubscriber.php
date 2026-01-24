<?php

namespace App\Domain\Catalog\Subscribers;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Review Event Subscriber
 *
 * Handles all review-related events in one place.
 */
class ReviewEventSubscriber
{
    /**
     * Handle review submitted events.
     */
    public function handleReviewSubmitted($event): void
    {
        Log::channel('reviews')->info('Review submitted', [
            'review_id' => $event->review->id ?? null,
            'catalog_item_id' => $event->review->catalog_item_id ?? null,
            'rating' => $event->review->rating ?? null,
        ]);
    }

    /**
     * Handle review approved events.
     */
    public function handleReviewApproved($event): void
    {
        Log::channel('reviews')->info('Review approved', [
            'review_id' => $event->review->id ?? null,
        ]);
    }

    /**
     * Handle review rejected events.
     */
    public function handleReviewRejected($event): void
    {
        Log::channel('reviews')->info('Review rejected', [
            'review_id' => $event->review->id ?? null,
            'reason' => $event->reason ?? null,
        ]);
    }

    /**
     * Handle review reported events.
     */
    public function handleReviewReported($event): void
    {
        Log::channel('reviews')->warning('Review reported', [
            'review_id' => $event->review->id ?? null,
            'reporter_id' => $event->reporterId ?? null,
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            'App\Domain\Catalog\Events\ReviewSubmitted' => 'handleReviewSubmitted',
            'App\Domain\Catalog\Events\ReviewApproved' => 'handleReviewApproved',
            'App\Domain\Catalog\Events\ReviewRejected' => 'handleReviewRejected',
            'App\Domain\Catalog\Events\ReviewReported' => 'handleReviewReported',
        ];
    }
}
