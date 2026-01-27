<?php

namespace App\Domain\Commerce\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Domain\Commerce\Models\Purchase;

/**
 * Purchase Placed Notification
 *
 * Sent to customer when purchase is successfully placed.
 */
class PurchasePlacedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Purchase $purchase
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
        return (new MailMessage)
            ->subject(__('notifications.purchase.placed_subject', ['purchase' => $this->purchase->purchase_number]))
            ->greeting(__('notifications.purchase.placed_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.purchase.placed_line1', ['purchase' => $this->purchase->purchase_number]))
            ->line(__('notifications.purchase.placed_line2', ['total' => monetaryUnit()->format($this->purchase->total)]))
            ->action(__('notifications.purchase.view_purchase'), url('/purchases/' . $this->purchase->id))
            ->line(__('notifications.purchase.thank_you'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'purchase_placed',
            'purchase_id' => $this->purchase->id,
            'purchase_number' => $this->purchase->purchase_number,
            'total' => $this->purchase->total,
            'status' => $this->purchase->status,
        ];
    }

    /**
     * Get the purchase
     */
    public function getPurchase(): Purchase
    {
        return $this->purchase;
    }
}
