<?php

namespace App\Domain\Shipping\Observers;

use App\Domain\Shipping\Models\Shipment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Shipment Observer
 *
 * Handles Shipment model lifecycle events.
 */
class ShipmentObserver
{
    /**
     * Handle the Shipment "creating" event.
     */
    public function creating(Shipment $shipment): void
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
     * Handle the Shipment "created" event.
     */
    public function created(Shipment $shipment): void
    {
        Log::channel('shipping')->info('Shipment created', [
            'tracking_number' => $shipment->tracking_number,
            'purchase_id' => $shipment->purchase_id,
            'courier' => $shipment->courier,
        ]);
    }

    /**
     * Handle the Shipment "updating" event.
     */
    public function updating(Shipment $shipment): void
    {
        if ($shipment->isDirty('status')) {
            $shipment->status_updated_at = now();

            // Set delivered_at when status is delivered
            if ($shipment->status === 'delivered' && empty($shipment->delivered_at)) {
                $shipment->delivered_at = now();
            }
        }
    }

    /**
     * Handle the Shipment "updated" event.
     */
    public function updated(Shipment $shipment): void
    {
        if ($shipment->wasChanged('status')) {
            Log::channel('shipping')->info('Shipment status updated', [
                'tracking_number' => $shipment->tracking_number,
                'old_status' => $shipment->getOriginal('status'),
                'new_status' => $shipment->status,
            ]);
        }
    }

    /**
     * Generate unique tracking number.
     */
    protected function generateTrackingNumber(): string
    {
        $prefix = 'TRK';
        $date = now()->format('ymd');
        $random = strtoupper(Str::random(8));

        return "{$prefix}{$date}{$random}";
    }
}
