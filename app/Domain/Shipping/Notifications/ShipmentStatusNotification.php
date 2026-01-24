<?php

namespace App\Domain\Shipping\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Domain\Shipping\Models\ShipmentTracking;

/**
 * Shipment Status Notification
 *
 * Sent to customer when shipment status changes.
 */
class ShipmentStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ShipmentTracking $shipment,
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
            ->subject(__('notifications.shipping.status_subject', ['status' => $this->getStatusLabel($this->newStatus)]))
            ->greeting(__('notifications.shipping.status_greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.shipping.status_line', [
                'status' => $this->getStatusLabel($this->newStatus),
            ]));

        // Add status-specific messages
        switch ($this->newStatus) {
            case 'picked_up':
                $message->line(__('notifications.shipping.picked_up_info'));
                break;
            case 'in_transit':
                $message->line(__('notifications.shipping.in_transit_info'));
                break;
            case 'out_for_delivery':
                $message->line(__('notifications.shipping.out_for_delivery_info'));
                break;
            case 'delivered':
                $message->line(__('notifications.shipping.delivered_info'));
                break;
        }

        if ($this->shipment->tracking_number) {
            $message->line(__('notifications.shipping.tracking_number', [
                'number' => $this->shipment->tracking_number,
            ]));
        }

        return $message
            ->action(__('notifications.shipping.track_shipment'), url('/tracking/' . $this->shipment->id))
            ->line(__('notifications.order.thank_you'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'shipment_status',
            'shipment_id' => $this->shipment->id,
            'tracking_number' => $this->shipment->tracking_number,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
        ];
    }

    /**
     * Get human-readable status label
     */
    protected function getStatusLabel(string $status): string
    {
        return __("statuses.shipment.{$status}");
    }

    /**
     * Get the shipment
     */
    public function getShipment(): ShipmentTracking
    {
        return $this->shipment;
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
