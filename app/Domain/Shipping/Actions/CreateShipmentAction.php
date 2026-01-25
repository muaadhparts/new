<?php

namespace App\Domain\Shipping\Actions;

use App\Domain\Shipping\Services\ShipmentTrackingService;
use App\Domain\Commerce\Models\MerchantPurchase;
use App\Domain\Shipping\Models\ShipmentTracking;

/**
 * CreateShipmentAction - Create shipment tracking record
 *
 * Single-responsibility action for creating shipments.
 */
class CreateShipmentAction
{
    public function __construct(
        private ShipmentTrackingService $trackingService
    ) {}

    /**
     * Execute the action
     *
     * @param int $merchantPurchaseId Merchant purchase ID
     * @param array $shipmentData Shipment data
     * @return array{success: bool, message: string, tracking?: ShipmentTracking}
     */
    public function execute(int $merchantPurchaseId, array $shipmentData): array
    {
        $merchantPurchase = MerchantPurchase::find($merchantPurchaseId);

        if (!$merchantPurchase) {
            return [
                'success' => false,
                'message' => __('Order not found'),
            ];
        }

        // Check if already has tracking
        if ($merchantPurchase->shipmentTracking) {
            return [
                'success' => false,
                'message' => __('Shipment already exists for this order'),
            ];
        }

        try {
            $tracking = $this->trackingService->createTrackingRecord(
                $merchantPurchase,
                $shipmentData
            );

            return [
                'success' => true,
                'message' => __('Shipment created successfully'),
                'tracking' => $tracking,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create API shipment (Tryoto, etc.)
     *
     * @param int $merchantPurchaseId
     * @param string $provider
     * @param array $options
     * @return array
     */
    public function createApiShipment(
        int $merchantPurchaseId,
        string $provider,
        array $options = []
    ): array {
        $merchantPurchase = MerchantPurchase::find($merchantPurchaseId);

        if (!$merchantPurchase) {
            return [
                'success' => false,
                'message' => __('Order not found'),
            ];
        }

        try {
            $tracking = $this->trackingService->createApiShipment(
                $merchantPurchase,
                $provider,
                $options
            );

            return [
                'success' => true,
                'message' => __('API shipment created successfully'),
                'tracking' => $tracking,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
