<?php

namespace App\Domain\Shipping\Actions;

use App\Domain\Shipping\Services\ShipmentTrackingService;
use App\Models\ShipmentTracking;

/**
 * CancelShipmentAction - Cancel a shipment
 *
 * Single-responsibility action for cancelling shipments.
 */
class CancelShipmentAction
{
    public function __construct(
        private ShipmentTrackingService $trackingService
    ) {}

    /**
     * Execute the action
     *
     * @param int $trackingId Shipment tracking ID
     * @param string|null $reason Cancellation reason
     * @return array{success: bool, message: string}
     */
    public function execute(int $trackingId, ?string $reason = null): array
    {
        $tracking = ShipmentTracking::find($trackingId);

        if (!$tracking) {
            return [
                'success' => false,
                'message' => __('Shipment not found'),
            ];
        }

        // Check if can be cancelled
        if (in_array($tracking->status, ['delivered', 'cancelled'])) {
            return [
                'success' => false,
                'message' => __('Cannot cancel shipment in current status'),
            ];
        }

        try {
            $this->trackingService->cancelShipment($tracking, $reason);

            return [
                'success' => true,
                'message' => __('Shipment cancelled successfully'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
