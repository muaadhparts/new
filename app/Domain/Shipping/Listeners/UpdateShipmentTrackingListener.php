<?php

namespace App\Domain\Shipping\Listeners;

use App\Domain\Shipping\Events\ShipmentStatusChangedEvent;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Shipping\Models\ShipmentTracking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Update Shipment Tracking Listener
 *
 * Updates tracking history when shipment status changes.
 */
class UpdateShipmentTrackingListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /**
     * Handle the event.
     */
    public function handle(ShipmentStatusChangedEvent $event): void
    {
        // Add tracking history entry
        $this->addTrackingHistory($event);

        // Update purchase status if needed
        $this->syncPurchaseStatus($event);

        Log::info('Shipment tracking updated', [
            'shipment_id' => $event->shipmentId,
            'previous_status' => $event->previousStatus,
            'new_status' => $event->newStatus,
            'location' => $event->location,
        ]);
    }

    /**
     * Add tracking history entry
     */
    protected function addTrackingHistory(ShipmentStatusChangedEvent $event): void
    {
        $shipment = ShipmentTracking::find($event->shipmentId);

        if (!$shipment) {
            return;
        }

        // Update current status
        $shipment->update([
            'status' => $event->newStatus,
            'current_location' => $event->location,
            'last_update' => now(),
        ]);

        // Add to history (if using JSON column or related table)
        // $shipment->addHistoryEntry($event->newStatus, $event->location, $event->notes);
    }

    /**
     * Sync purchase status with shipment
     */
    protected function syncPurchaseStatus(ShipmentStatusChangedEvent $event): void
    {
        $purchase = Purchase::find($event->purchaseId);

        if (!$purchase) {
            return;
        }

        $statusMap = [
            'picked_up' => 'shipped',
            'in_transit' => 'shipped',
            'out_for_delivery' => 'out_for_delivery',
            'delivered' => 'delivered',
            'failed' => 'delivery_failed',
            'returned' => 'returned',
        ];

        $newPurchaseStatus = $statusMap[$event->newStatus] ?? null;

        if ($newPurchaseStatus && $purchase->status !== $newPurchaseStatus) {
            $purchase->update(['status' => $newPurchaseStatus]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(ShipmentStatusChangedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to update shipment tracking', [
            'shipment_id' => $event->shipmentId,
            'error' => $exception->getMessage(),
        ]);
    }
}
