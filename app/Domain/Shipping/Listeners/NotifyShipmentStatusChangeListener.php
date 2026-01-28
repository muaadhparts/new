<?php

namespace App\Domain\Shipping\Listeners;

use App\Domain\Shipping\Events\ShipmentStatusChangedEvent;
use App\Domain\Commerce\Models\Purchase;
use App\Classes\MuaadhMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Notify Shipment Status Change Listener
 *
 * Notifies customer when shipment status changes.
 *
 * Channel Independence: This listener handles status change notifications
 * for ALL channels (Web, Mobile, API, WhatsApp).
 */
class NotifyShipmentStatusChangeListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $backoff = 60;
    public string $queue = 'notifications';

    /**
     * Statuses that warrant customer notification
     */
    protected array $notifiableStatuses = [
        ShipmentStatusChangedEvent::STATUS_PICKED_UP,
        ShipmentStatusChangedEvent::STATUS_IN_TRANSIT,
        ShipmentStatusChangedEvent::STATUS_OUT_FOR_DELIVERY,
        ShipmentStatusChangedEvent::STATUS_DELIVERED,
        ShipmentStatusChangedEvent::STATUS_FAILED,
        ShipmentStatusChangedEvent::STATUS_RETURNED,
    ];

    /**
     * Handle the event.
     */
    public function handle(ShipmentStatusChangedEvent $event): void
    {
        // Only notify for significant status changes
        if (!in_array($event->newStatus, $this->notifiableStatuses)) {
            return;
        }

        $purchase = Purchase::find($event->purchaseId);

        if (!$purchase) {
            Log::warning('NotifyShipmentStatusChange: Purchase not found', [
                'purchase_id' => $event->purchaseId,
            ]);
            return;
        }

        $customerEmail = $purchase->customer_email;

        if (!$customerEmail) {
            Log::warning('NotifyShipmentStatusChange: Customer email not found', [
                'purchase_id' => $event->purchaseId,
            ]);
            return;
        }

        $this->sendStatusNotification($purchase, $event);

        Log::channel('domain')->info('Shipment status notification sent', [
            'event_id' => $event->eventId,
            'purchase_id' => $event->purchaseId,
            'shipment_id' => $event->shipmentId,
            'new_status' => $event->newStatus,
        ]);
    }

    /**
     * Send status change notification
     */
    protected function sendStatusNotification(Purchase $purchase, ShipmentStatusChangedEvent $event): void
    {
        $mailer = new MuaadhMailer();

        $mailer->sendAutoPurchaseMail([
            'order_id' => $purchase->id,
            'purchase_number' => $purchase->purchase_number,
            'customer_name' => $purchase->customer_name,
            'customer_email' => $purchase->customer_email,
            'type' => 'shipment_status_update',
            'previous_status' => $event->previousStatus,
            'new_status' => $event->newStatus,
            'location' => $event->location,
            'status_label' => $this->getStatusLabel($event->newStatus),
        ], $purchase->customer_email);
    }

    /**
     * Get human-readable status label
     */
    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            ShipmentStatusChangedEvent::STATUS_PICKED_UP => __('Picked Up'),
            ShipmentStatusChangedEvent::STATUS_IN_TRANSIT => __('In Transit'),
            ShipmentStatusChangedEvent::STATUS_OUT_FOR_DELIVERY => __('Out for Delivery'),
            ShipmentStatusChangedEvent::STATUS_DELIVERED => __('Delivered'),
            ShipmentStatusChangedEvent::STATUS_FAILED => __('Delivery Failed'),
            ShipmentStatusChangedEvent::STATUS_RETURNED => __('Returned'),
            default => $status,
        };
    }

    /**
     * Handle a job failure.
     */
    public function failed(ShipmentStatusChangedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to send shipment status notification', [
            'purchase_id' => $event->purchaseId,
            'shipment_id' => $event->shipmentId,
            'new_status' => $event->newStatus,
            'error' => $exception->getMessage(),
        ]);
    }
}
