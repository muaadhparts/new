<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ShipmentTracking;

class ShipmentStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $shipmentTracking;
    protected $oldStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct(ShipmentTracking $shipmentTracking, ?string $oldStatus = null)
    {
        $this->shipmentTracking = $shipmentTracking;
        $this->oldStatus = $oldStatus;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $statusText = $this->shipmentTracking->status_ar;

        $subject = $this->getSubjectByStatus();

        return (new MailMessage)
            ->subject($subject)
            ->greeting(__('Hello') . ' ' . ($notifiable->name ?? __('Customer')) . ',')
            ->line($this->getMessageByStatus())
            ->line(__('Tracking Number') . ': ' . $this->shipmentTracking->tracking_number)
            ->line(__('Status') . ': ' . $statusText)
            ->when($this->shipmentTracking->location, function ($message) {
                return $message->line(__('Location') . ': ' . $this->shipmentTracking->location);
            })
            ->action(__('Track Shipment'), route('front.tracking', ['tracking' => $this->shipmentTracking->tracking_number]))
            ->line(__('Thank you for your purchase!'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'shipment_status_updated',
            'tracking_number' => $this->shipmentTracking->tracking_number,
            'purchase_id' => $this->shipmentTracking->purchase_id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->shipmentTracking->status,
            'status_ar' => $this->shipmentTracking->status_ar,
            'message' => $this->shipmentTracking->message_ar ?? $this->shipmentTracking->message,
            'location' => $this->shipmentTracking->location,
            'company_name' => $this->shipmentTracking->company_name,
            'occurred_at' => $this->shipmentTracking->occurred_at?->toISOString(),
        ];
    }

    /**
     * Get subject based on status
     */
    protected function getSubjectByStatus(): string
    {
        return match($this->shipmentTracking->status) {
            'created' => __('Your shipment has been created'),
            'picked_up' => __('Your shipment has been picked up'),
            'in_transit' => __('Your shipment is on the way'),
            'out_for_delivery' => __('Your shipment is out for delivery'),
            'delivered' => __('Your shipment has been delivered!'),
            'failed' => __('Delivery attempt failed'),
            'returned' => __('Your shipment has been returned'),
            'cancelled' => __('Your shipment has been cancelled'),
            default => __('Shipment status update'),
        };
    }

    /**
     * Get message based on status
     */
    protected function getMessageByStatus(): string
    {
        return match($this->shipmentTracking->status) {
            'created' => __('Your purchase has been shipped and a tracking number has been assigned.'),
            'picked_up' => __('Your shipment has been picked up from the warehouse and is being processed.'),
            'in_transit' => __('Your package is currently in transit to your delivery address.'),
            'out_for_delivery' => __('Great news! Your package is out for delivery and will arrive today.'),
            'delivered' => __('Your package has been successfully delivered. We hope you enjoy your purchase!'),
            'failed' => __('We were unable to deliver your package. Please contact us for more information.'),
            'returned' => __('Your shipment has been returned to the sender.'),
            'cancelled' => __('Your shipment has been cancelled.'),
            default => __('The status of your shipment has been updated.'),
        };
    }
}
