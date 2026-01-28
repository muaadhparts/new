<?php

namespace App\Domain\Shipping\Listeners;

use App\Domain\Shipping\Events\ShipmentCreatedEvent;
use App\Domain\Commerce\Models\Purchase;
use App\Classes\MuaadhMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Notify Customer Of Shipment Listener
 *
 * Notifies customer when a shipment is created for their order.
 *
 * Channel Independence: This listener handles shipment notifications
 * for ALL channels (Web, Mobile, API, WhatsApp).
 */
class NotifyCustomerOfShipmentListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $backoff = 60;
    public string $queue = 'notifications';

    /**
     * Handle the event.
     */
    public function handle(ShipmentCreatedEvent $event): void
    {
        $purchase = Purchase::find($event->purchaseId);

        if (!$purchase) {
            Log::warning('NotifyCustomerOfShipment: Purchase not found', [
                'purchase_id' => $event->purchaseId,
            ]);
            return;
        }

        $customerEmail = $purchase->customer_email;

        if (!$customerEmail) {
            Log::warning('NotifyCustomerOfShipment: Customer email not found', [
                'purchase_id' => $event->purchaseId,
            ]);
            return;
        }

        $this->sendShipmentNotification($purchase, $event);

        Log::channel('domain')->info('Shipment notification sent to customer', [
            'event_id' => $event->eventId,
            'purchase_id' => $event->purchaseId,
            'shipment_id' => $event->shipmentId,
            'tracking_number' => $event->trackingNumber,
        ]);
    }

    /**
     * Send shipment notification email
     */
    protected function sendShipmentNotification(Purchase $purchase, ShipmentCreatedEvent $event): void
    {
        $mailer = new MuaadhMailer();

        $mailer->sendAutoPurchaseMail([
            'order_id' => $purchase->id,
            'purchase_number' => $purchase->purchase_number,
            'customer_name' => $purchase->customer_name,
            'customer_email' => $purchase->customer_email,
            'type' => 'shipment_created',
            'tracking_number' => $event->trackingNumber,
            'carrier' => $event->carrier,
        ], $purchase->customer_email);
    }

    /**
     * Handle a job failure.
     */
    public function failed(ShipmentCreatedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to notify customer of shipment', [
            'purchase_id' => $event->purchaseId,
            'shipment_id' => $event->shipmentId,
            'error' => $exception->getMessage(),
        ]);
    }
}
