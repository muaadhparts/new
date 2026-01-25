<?php

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\Models\ShipmentTracking;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Service for merchant shipment queries.
 * Handles complex subqueries for shipment listing and export.
 */
class MerchantShipmentQueryService
{
    /**
     * Get paginated shipments with latest status per tracking number.
     *
     * @param int $merchantId
     * @param string|null $status Filter by status
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedShipments(int $merchantId, ?string $status = null, int $perPage = 20): LengthAwarePaginator
    {
        return ShipmentTracking::where('merchant_id', $merchantId)
            ->whereIn('id', function ($sub) use ($merchantId, $status) {
                $sub->selectRaw('MAX(id)')
                    ->from('shipment_trackings')
                    ->where('merchant_id', $merchantId)
                    ->when($status, fn($q) => $q->where('status', $status))
                    ->groupBy('tracking_number');
            })
            ->with('purchase:id,purchase_number,customer_name,pay_amount')
            ->orderBy('occurred_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get latest shipment record by tracking number.
     *
     * @param int $merchantId
     * @param string $trackingNumber
     * @return ShipmentTracking|null
     */
    public function getLatestByTrackingNumber(int $merchantId, string $trackingNumber): ?ShipmentTracking
    {
        return ShipmentTracking::where('merchant_id', $merchantId)
            ->where('tracking_number', $trackingNumber)
            ->latest('occurred_at')
            ->first();
    }

    /**
     * Get any shipment by tracking number (for cancel/refresh).
     *
     * @param int $merchantId
     * @param string $trackingNumber
     * @return ShipmentTracking|null
     */
    public function getByTrackingNumber(int $merchantId, string $trackingNumber): ?ShipmentTracking
    {
        return ShipmentTracking::where('merchant_id', $merchantId)
            ->where('tracking_number', $trackingNumber)
            ->first();
    }

    /**
     * Get shipment history by tracking number.
     *
     * @param string $trackingNumber
     * @return Collection
     */
    public function getTrackingHistory(string $trackingNumber): Collection
    {
        return ShipmentTracking::where('tracking_number', $trackingNumber)
            ->orderBy('occurred_at', 'desc')
            ->get();
    }

    /**
     * Get shipments for export.
     *
     * @param int $merchantId
     * @param array $filters ['status', 'date_from', 'date_to']
     * @return Collection
     */
    public function getShipmentsForExport(int $merchantId, array $filters = []): Collection
    {
        $status = $filters['status'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        return ShipmentTracking::where('merchant_id', $merchantId)
            ->with('purchase:id,purchase_number,customer_name,pay_amount')
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($dateFrom, fn($q) => $q->whereDate('occurred_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('occurred_at', '<=', $dateTo))
            ->orderBy('occurred_at', 'desc')
            ->get();
    }

    /**
     * Get API shipments by tracking numbers for bulk refresh.
     *
     * @param int $merchantId
     * @param array $trackingNumbers
     * @return Collection
     */
    public function getApiShipmentsByTrackingNumbers(int $merchantId, array $trackingNumbers): Collection
    {
        return ShipmentTracking::where('merchant_id', $merchantId)
            ->whereIn('tracking_number', $trackingNumbers)
            ->where('integration_type', ShipmentTracking::INTEGRATION_API)
            ->get();
    }
}
