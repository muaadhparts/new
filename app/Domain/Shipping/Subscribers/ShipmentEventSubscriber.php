<?php

namespace App\Domain\Shipping\Subscribers;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Shipment Event Subscriber
 *
 * Handles all shipment-related events in one place.
 */
class ShipmentEventSubscriber
{
    /**
     * Handle shipment created events.
     */
    public function handleShipmentCreated($event): void
    {
        Log::channel('shipping')->info('Shipment created', [
            'shipment_id' => $event->shipment->id ?? null,
            'order_id' => $event->shipment->purchase_id ?? null,
            'courier' => $event->shipment->courier_id ?? null,
        ]);
    }

    /**
     * Handle shipment picked up events.
     */
    public function handleShipmentPickedUp($event): void
    {
        Log::channel('shipping')->info('Shipment picked up', [
            'shipment_id' => $event->shipment->id ?? null,
            'tracking_number' => $event->shipment->tracking_number ?? null,
        ]);
    }

    /**
     * Handle shipment in transit events.
     */
    public function handleShipmentInTransit($event): void
    {
        Log::channel('shipping')->info('Shipment in transit', [
            'shipment_id' => $event->shipment->id ?? null,
            'location' => $event->location ?? null,
        ]);
    }

    /**
     * Handle shipment delivered events.
     */
    public function handleShipmentDelivered($event): void
    {
        Log::channel('shipping')->info('Shipment delivered', [
            'shipment_id' => $event->shipment->id ?? null,
            'delivered_at' => $event->deliveredAt ?? now(),
        ]);
    }

    /**
     * Handle shipment failed events.
     */
    public function handleShipmentFailed($event): void
    {
        Log::channel('shipping')->error('Shipment delivery failed', [
            'shipment_id' => $event->shipment->id ?? null,
            'reason' => $event->reason ?? null,
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            'App\Domain\Shipping\Events\ShipmentCreated' => 'handleShipmentCreated',
            'App\Domain\Shipping\Events\ShipmentPickedUp' => 'handleShipmentPickedUp',
            'App\Domain\Shipping\Events\ShipmentInTransit' => 'handleShipmentInTransit',
            'App\Domain\Shipping\Events\ShipmentDelivered' => 'handleShipmentDelivered',
            'App\Domain\Shipping\Events\ShipmentFailed' => 'handleShipmentFailed',
        ];
    }
}
