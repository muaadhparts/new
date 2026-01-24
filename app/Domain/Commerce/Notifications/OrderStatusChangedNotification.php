<?php

namespace App\Domain\Commerce\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Domain\Commerce\Models\Purchase;

/**
 * Order Status Changed Notification
 *
 * Sent to customer when order status changes.
 */
class OrderStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Purchase $purchase,
        protected string $previousStatus,
        protected string $newStatus
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
        $message = (new MailMessage)
            ->subject(__('notifications.order.status_subject', ['order' => $this->purchase->order_number]))
            ->greeting(__('notifications.order.status_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.order.status_line', [
                'order' => $this->purchase->order_number,
                'status' => $this->getStatusLabel($this->newStatus),
            ]));

        if ($this->newStatus === 'shipped') {
            $message->line(__('notifications.order.shipped_info'));
        } elseif ($this->newStatus === 'delivered') {
            $message->line(__('notifications.order.delivered_info'));
        }

        return $message
            ->action(__('notifications.order.view_order'), url('/purchases/' . $this->purchase->id))
            ->line(__('notifications.order.thank_you'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_status_changed',
            'purchase_id' => $this->purchase->id,
            'order_number' => $this->purchase->order_number,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
        ];
    }

    /**
     * Get human-readable status label
     */
    protected function getStatusLabel(string $status): string
    {
        return __("statuses.order.{$status}");
    }

    /**
     * Get the purchase
     */
    public function getPurchase(): Purchase
    {
        return $this->purchase;
    }

    /**
     * Get previous status
     */
    public function getPreviousStatus(): string
    {
        return $this->previousStatus;
    }

    /**
     * Get new status
     */
    public function getNewStatus(): string
    {
        return $this->newStatus;
    }
}
