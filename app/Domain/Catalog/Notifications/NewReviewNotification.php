<?php

namespace App\Domain\Catalog\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Domain\Catalog\Models\CatalogReview;

/**
 * New Review Notification
 *
 * Sent to merchant when they receive a new product review.
 */
class NewReviewNotification extends Notification implements ShouldQueue
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
        $rating = $this->review->rating;
        $starDisplay = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);

        $message = (new MailMessage)
            ->subject(__('notifications.catalog.new_review_subject', ['product' => $productName]))
            ->greeting(__('notifications.catalog.new_review_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.catalog.new_review_line1', ['product' => $productName]))
            ->line(__('notifications.catalog.new_review_rating', ['stars' => $starDisplay, 'rating' => $rating]));

        if ($this->review->comment) {
            $message->line(__('notifications.catalog.new_review_comment'))
                ->line('"' . \Str::limit($this->review->comment, 200) . '"');
        }

        return $message
            ->action(__('notifications.catalog.view_review'), url('/merchant/reviews/' . $this->review->id))
            ->line(__('notifications.catalog.respond_to_review'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_review',
            'review_id' => $this->review->id,
            'catalog_item_id' => $this->review->catalog_item_id,
            'rating' => $this->review->rating,
            'has_comment' => !empty($this->review->comment),
        ];
    }

    /**
     * Get the review
     */
    public function getReview(): CatalogReview
    {
        return $this->review;
    }

    /**
     * Check if review is positive (4+ stars)
     */
    public function isPositive(): bool
    {
        return $this->review->rating >= 4;
    }
}
