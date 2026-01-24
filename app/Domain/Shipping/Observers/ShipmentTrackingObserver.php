<?php

namespace App\Domain\Shipping\Observers;

use App\Domain\Shipping\Models\ShipmentTracking;
use Illuminate\Support\Str;

/**
 * Shipment Tracking Observer
 *
 * Handles ShipmentTracking model lifecycle events.
 */
class ShipmentTrackingObserver
{
    /**
     * Handle the ShipmentTracking "creating" event.
     */
    public function creating(ShipmentTracking $shipment): void
    {
        // Generate tracking number if not set
        if (empty($shipment->tracking_number)) {
            $shipment->tracking_number = $this->generateTrackingNumber();
        }

        // Set default status
        if (empty($shipment->status)) {
            $shipment->status = 'pending';
        }
    }

    /**
     * Handle the ShipmentTracking "created" event.
     */
    public function created(ShipmentTracking $shipment): void
    {
        // Log initial tracking event
        $this->logTrackingEvent($shipment, 'created', __('shipping.tracking.shipment_created'));
    }

    /**
     * Handle the ShipmentTracking "updating" event.
     */
    public function updating(ShipmentTracking $shipment): void
    {
        // Track status changes
        if ($shipment->isDirty('status')) {
            $newStatus = $shipment->status;

            switch ($newStatus) {
                case 'picked_up':
                    $shipment->picked_up_at = now();
                    break;
                case 'in_transit':
                    $shipment->in_transit_at = now();
                    break;
                case 'out_for_delivery':
                    $shipment->out_for_delivery_at = now();
                    break;
                case 'delivered':
                    $shipment->delivered_at = now();
                    break;
                case 'returned':
                    $shipment->returned_at = now();
                    break;
            }
        }
    }

    /**
     * Handle the ShipmentTracking "updated" event.
     */
    public function updated(ShipmentTracking $shipment): void
    {
        // Log status change
        if ($shipment->wasChanged('status')) {
            $this->logTrackingEvent(
                $shipment,
                $shipment->status,
                __("shipping.tracking.status_{$shipment->status}")
            );

            // Dispatch event
            event(new \App\Domain\Shipping\Events\ShipmentStatusChangedEvent(
                $shipment,
                $shipment->getOriginal('status'),
                $shipment->status
            ));
        }
    }

    /**
     * Generate unique tracking number
     */
    protected function generateTrackingNumber(): string
    {
        $prefix = 'TRK';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(8));

        do {
            $trackingNumber = "{$prefix}{$date}{$random}";
        } while (ShipmentTracking::where('tracking_number', $trackingNumber)->exists());

        return $trackingNumber;
    }

    /**
     * Log tracking event
     */
    protected function logTrackingEvent(ShipmentTracking $shipment, string $event, string $description): void
    {
        $history = $shipment->tracking_history ?? [];
        $history[] = [
            'event' => $event,
            'description' => $description,
            'timestamp' => now()->toISOString(),
            'location' => $shipment->current_location,
        ];

        $shipment->tracking_history = $history;
        $shipment->saveQuietly(); // Prevent infinite loop
    }
}
