<?php

namespace App\Domain\Shipping\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Domain\Shipping\Models\ShipmentTracking;

/**
 * Shipment Created Notification
 *
 * Sent to customer when shipment is created.
 */
class ShipmentCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ShipmentTracking $shipment
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
            ->subject(__('notifications.shipping.created_subject'))
            ->greeting(__('notifications.shipping.created_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.shipping.created_line1'));

        if ($this->shipment->tracking_number) {
            $message->line(__('notifications.shipping.tracking_number', [
                'number' => $this->shipment->tracking_number,
            ]));
        }

        if ($this->shipment->courier) {
            $message->line(__('notifications.shipping.courier', [
                'name' => $this->shipment->courier->name ?? 'Courier',
            ]));
        }

        return $message
            ->action(__('notifications.shipping.track_shipment'), url('/tracking/' . $this->shipment->id))
            ->line(__('notifications.shipping.delivery_info'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'shipment_created',
            'shipment_id' => $this->shipment->id,
            'tracking_number' => $this->shipment->tracking_number,
            'courier_id' => $this->shipment->courier_id,
            'status' => $this->shipment->status,
        ];
    }

    /**
     * Get the shipment
     */
    public function getShipment(): ShipmentTracking
    {
        return $this->shipment;
    }
}
