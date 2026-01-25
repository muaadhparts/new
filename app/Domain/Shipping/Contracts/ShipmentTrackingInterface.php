<?php

namespace App\Domain\Shipping\Contracts;

use App\Domain\Shipping\Models\ShipmentTracking;
use Illuminate\Support\Collection;

/**
 * ShipmentTrackingInterface - Contract for shipment tracking
 *
 * All tracking operations MUST go through this interface.
 */
interface ShipmentTrackingInterface
{
    /**
     * Create a new tracking record
     */
    public function createTrackingRecord(
        int $purchaseId,
        int $merchantId,
        string $status,
        array $data = []
    ): ShipmentTracking;

    /**
     * Create API-integrated shipment
     */
    public function createApiShipment(
        int $purchaseId,
        int $merchantId,
        int $shippingId,
        array $shipmentData
    ): ShipmentTracking;

    /**
     * Create manual shipment
     */
    public function createManualShipment(
        int $purchaseId,
        int $merchantId,
        ?string $trackingNumber = null,
        ?string $companyName = null
    ): ShipmentTracking;

    /**
     * Update tracking from API
     */
    public function updateFromApi(ShipmentTracking $tracking, array $apiData): ShipmentTracking;

    /**
     * Update tracking manually
     */
    public function updateManually(ShipmentTracking $tracking, string $status, ?string $notes = null): ShipmentTracking;

    /**
     * Get current status for a purchase
     */
    public function getCurrentStatus(int $purchaseId, ?int $merchantId = null): ?array;

    /**
     * Get tracking history
     */
    public function getTrackingHistory(int $purchaseId, ?int $merchantId = null): array;

    /**
     * Cancel shipment
     */
    public function cancelShipment(ShipmentTracking $tracking, ?string $reason = null): bool;
}
