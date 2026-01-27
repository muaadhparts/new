<?php

namespace App\Domain\Commerce\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Domain\Commerce\Models\Purchase;

/**
 * Purchase Status Changed Notification
 *
 * Sent to customer when purchase status changes.
 */
class PurchaseStatusChangedNotification extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject(__('notifications.purchase.status_subject', ['purchase' => $this->purchase->purchase_number]))
            ->greeting(__('notifications.purchase.status_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.purchase.status_line1', [
                'purchase' => $this->purchase->purchase_number,
                'status' => __($this->newStatus)
            ]))
            ->action(__('notifications.purchase.view_purchase'), url('/purchases/' . $this->purchase->id))
            ->line(__('notifications.purchase.thank_you'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'purchase_status_changed',
            'purchase_id' => $this->purchase->id,
            'purchase_number' => $this->purchase->purchase_number,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
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
