<?php

namespace App\Domain\Catalog\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Domain\Catalog\Models\CatalogReview;

/**
 * Review Approved Notification
 *
 * Sent to customer when their review is approved.
 */
class ReviewApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected CatalogReview $review
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $productName = $this->review->catalogItem->name ?? 'Product';

        return (new MailMessage)
            ->subject(__('notifications.catalog.review_approved_subject'))
            ->greeting(__('notifications.catalog.review_approved_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.catalog.review_approved_line1', ['product' => $productName]))
            ->line(__('notifications.catalog.review_approved_line2'))
            ->action(__('notifications.catalog.view_product'), url('/products/' . $this->review->catalog_item_id))
            ->line(__('notifications.catalog.thank_you_review'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'review_approved',
            'review_id' => $this->review->id,
            'catalog_item_id' => $this->review->catalog_item_id,
            'approved_at' => now()->toISOString(),
        ];
    }

    /**
     * Get the review
     */
    public function getReview(): CatalogReview
    {
        return $this->review;
    }
}
