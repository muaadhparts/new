<?php

namespace App\Domain\Commerce\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Domain\Commerce\Models\Purchase;

/**
 * Order Placed Notification
 *
 * Sent to customer when order is successfully placed.
 */
class OrderPlacedNotification extends Notification implements ShouldQueue
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
            ->subject(__('notifications.order.placed_subject', ['order' => $this->purchase->order_number]))
            ->greeting(__('notifications.order.placed_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.order.placed_line1', ['order' => $this->purchase->order_number]))
            ->line(__('notifications.order.placed_line2', ['total' => monetaryUnit()->format($this->purchase->total)]))
            ->action(__('notifications.order.view_order'), url('/purchases/' . $this->purchase->id))
            ->line(__('notifications.order.thank_you'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_placed',
            'purchase_id' => $this->purchase->id,
            'order_number' => $this->purchase->order_number,
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
