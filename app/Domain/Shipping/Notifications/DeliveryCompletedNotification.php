<?php

namespace App\Domain\Shipping\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Domain\Shipping\Models\ShipmentTracking;

/**
 * Delivery Completed Notification
 *
 * Sent to customer when delivery is completed.
 */
class DeliveryCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ShipmentTracking $shipment,
        protected ?string $receiverName = null
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
            ->subject(__('notifications.shipping.delivered_subject'))
            ->greeting(__('notifications.shipping.delivered_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.shipping.delivered_line1'));

        if ($this->receiverName) {
            $message->line(__('notifications.shipping.received_by', ['name' => $this->receiverName]));
        }

        if ($this->shipment->delivered_at) {
            $message->line(__('notifications.shipping.delivered_at', [
                'time' => $this->shipment->delivered_at->format('Y-m-d H:i'),
            ]));
        }

        return $message
            ->line(__('notifications.shipping.review_request'))
            ->action(__('notifications.shipping.leave_review'), url('/reviews/create'))
            ->line(__('notifications.order.thank_you'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'delivery_completed',
            'shipment_id' => $this->shipment->id,
            'tracking_number' => $this->shipment->tracking_number,
            'receiver_name' => $this->receiverName,
            'delivered_at' => $this->shipment->delivered_at?->toISOString(),
        ];
    }

    /**
     * Get the shipment
     */
    public function getShipment(): ShipmentTracking
    {
        return $this->shipment;
    }

    /**
     * Get receiver name
     */
    public function getReceiverName(): ?string
    {
        return $this->receiverName;
    }
}
