<?php

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\DTOs\TrackingDTO;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Shipping\Models\ShipmentTracking;

/**
 * TrackingDataBuilder - Builds pre-computed data for tracking pages
 *
 * DATA FLOW POLICY: All queries and logic here, structured arrays are output
 */
class TrackingDataBuilder
{
    /**
     * Build tracking data by tracking number or purchase number (for FrontendController)
     *
     * @param string $identifier Tracking number or purchase number
     * @return array{purchase: ?array, tracking: ?TrackingDTO, shipmentLogs: array, statuses: array}
     */
    public function build(string $identifier): array
    {
        $purchase = null;
        $tracking = null;
        $shipmentLogs = [];

        // Try to find by purchase_number first
        $purchaseModel = Purchase::where('purchase_number', '=', $identifier)->first();

        if (!$purchaseModel) {
            // Try to find by tracking number
            $trackingModel = ShipmentTracking::where('tracking_number', $identifier)
                ->orderBy('occurred_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($trackingModel) {
                $purchaseModel = Purchase::find($trackingModel->purchase_id);
                $tracking = TrackingDTO::fromModel($trackingModel);
            }
        }

        if ($purchaseModel) {
            // Build purchase data
            $purchase = $this->buildPurchaseData($purchaseModel);

            // Get all shipment logs for this purchase
            $shipmentLogs = $this->buildShipmentLogs($purchaseModel->id);

            // If we don't have tracking DTO yet, try to build from first log
            if (!$tracking && !empty($shipmentLogs)) {
                $firstTracking = ShipmentTracking::where('purchase_id', $purchaseModel->id)->first();
                if ($firstTracking) {
                    $tracking = TrackingDTO::fromModel($firstTracking);
                }
            }
        }

        return [
            'purchase' => $purchase,
            'tracking' => $tracking,
            'shipmentLogs' => $shipmentLogs,
            'statuses' => ['Pending', 'Processing', 'On Delivery', 'Completed'],
        ];
    }

    /**
     * Build tracking data from request parameters (for ShipmentTrackingController)
     *
     * @param string|null $trackingNumber
     * @param string|null $purchaseNumber
     * @return array
     */
    public function buildFromRequest(?string $trackingNumber, ?string $purchaseNumber): array
    {
        $shipment = null;
        $history = [];
        $purchase = null;
        $integrationType = null;

        if ($trackingNumber) {
            // Search by tracking number
            $shipmentModel = ShipmentTracking::getLatestByTracking($trackingNumber);

            if ($shipmentModel) {
                $historyModels = ShipmentTracking::getHistoryByTracking($trackingNumber);
                $purchaseModel = Purchase::find($shipmentModel->purchase_id);

                $shipment = $this->buildShipmentData($shipmentModel);
                $history = $this->buildHistoryArray($historyModels);
                $purchase = $purchaseModel ? $this->buildPurchaseData($purchaseModel) : null;
                $integrationType = $shipmentModel->integration_type;
            }
        } elseif ($purchaseNumber) {
            // Search by purchase number
            $purchaseModel = Purchase::where('purchase_number', $purchaseNumber)->first();

            if ($purchaseModel) {
                $shipmentModel = ShipmentTracking::where('purchase_id', $purchaseModel->id)
                    ->orderBy('occurred_at', 'desc')
                    ->first();

                if ($shipmentModel) {
                    $historyModels = ShipmentTracking::where('purchase_id', $purchaseModel->id)
                        ->orderBy('occurred_at', 'desc')
                        ->get();

                    $shipment = $this->buildShipmentData($shipmentModel);
                    $history = $this->buildHistoryArray($historyModels);
                    $integrationType = $shipmentModel->integration_type;
                }

                $purchase = $this->buildPurchaseData($purchaseModel);
            }
        }

        return [
            'shipment' => $shipment,
            'history' => $history,
            'purchase' => $purchase,
            'integrationType' => $integrationType,
        ];
    }

    /**
     * Build tracking data from tracking number only (for API)
     *
     * @param string $trackingNumber
     * @return array
     */
    public function buildFromTracking(string $trackingNumber): array
    {
        $shipmentModel = ShipmentTracking::getLatestByTracking($trackingNumber);

        if (!$shipmentModel) {
            return [
                'shipment' => null,
                'history' => [],
            ];
        }

        $historyModels = ShipmentTracking::getHistoryByTracking($trackingNumber);

        return [
            'shipment' => [
                'status' => $shipmentModel->status,
                'status_ar' => $shipmentModel->status_ar,
                'message' => $shipmentModel->message,
                'message_ar' => $shipmentModel->message_ar,
                'location' => $shipmentModel->location,
                'date' => $shipmentModel->occurred_at?->format('Y-m-d H:i'),
                'company' => $shipmentModel->company_name,
            ],
            'history' => $historyModels->map(function ($log) {
                return [
                    'status' => $log->status,
                    'status_ar' => $log->status_ar,
                    'message_ar' => $log->message_ar,
                    'location' => $log->location,
                    'date' => $log->occurred_at?->format('Y-m-d H:i'),
                ];
            })->toArray(),
        ];
    }

    /**
     * Build user shipments list
     *
     * @param int $userId
     * @return array
     */
    public function buildUserShipments(int $userId): array
    {
        $purchases = Purchase::where('user_id', $userId)
            ->latest()
            ->get();

        $shipments = [];

        foreach ($purchases as $purchase) {
            // Get latest tracking for each merchant in this purchase
            $trackings = ShipmentTracking::where('purchase_id', $purchase->id)
                ->whereIn('id', function ($sub) use ($purchase) {
                    $sub->selectRaw('MAX(id)')
                        ->from('shipment_trackings')
                        ->where('purchase_id', $purchase->id)
                        ->groupBy('merchant_id');
                })
                ->get();

            foreach ($trackings as $tracking) {
                $status = $tracking->status;
                $shipments[] = [
                    'purchase_number' => $purchase->purchase_number,
                    'tracking_number' => $tracking->tracking_number,
                    'company' => $tracking->company_name,
                    'status' => $status,
                    'status_ar' => $tracking->status_ar,
                    'date' => $tracking->occurred_at,
                    'purchase_id' => $purchase->id,
                    'integration_type' => $tracking->integration_type,
                    // PRE-COMPUTED: Display values (DATA_FLOW_POLICY - no @php in view)
                    'statusColor' => $this->getStatusColor($status),
                    'statusIcon' => $this->getStatusIcon($status),
                    'progressPercent' => $this->calculateShipmentProgress($status),
                ];
            }
        }

        return $shipments;
    }

    /**
     * Build purchase data for display
     */
    private function buildPurchaseData(Purchase $purchase): array
    {
        return [
            'id' => $purchase->id,
            'orderNumber' => $purchase->purchase_number ?? "#{$purchase->id}",
            'status' => $purchase->status ?? 'pending',
            'statusLabel' => $this->getStatusLabel($purchase->status ?? 'pending'),
            'formattedDate' => $purchase->created_at?->format('Y-m-d H:i') ?? '',
            'customerName' => $purchase->customer_name ?? '',
            'trackingNumber' => $purchase->tracking_number,
        ];
    }

    /**
     * Build shipment data for display
     */
    private function buildShipmentData(ShipmentTracking $shipment): array
    {
        return [
            'id' => $shipment->id,
            'trackingNumber' => $shipment->tracking_number,
            'status' => $shipment->status,
            'statusAr' => $shipment->status_ar,
            'message' => $shipment->message,
            'messageAr' => $shipment->message_ar,
            'location' => $shipment->location,
            'companyName' => $shipment->company_name,
            'occurredAt' => $shipment->occurred_at?->format('Y-m-d H:i'),
            'integrationType' => $shipment->integration_type,
        ];
    }

    /**
     * Build history array from collection
     */
    private function buildHistoryArray($historyModels): array
    {
        return $historyModels->map(function ($log) {
            return [
                'id' => $log->id,
                'status' => $log->status,
                'statusAr' => $log->status_ar,
                'message' => $log->message,
                'messageAr' => $log->message_ar,
                'location' => $log->location,
                'occurredAt' => $log->occurred_at?->format('Y-m-d H:i'),
            ];
        })->toArray();
    }

    /**
     * Build shipment logs array for display
     */
    private function buildShipmentLogs(int $purchaseId): array
    {
        $logs = ShipmentTracking::where('purchase_id', $purchaseId)
            ->orderBy('occurred_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'status' => $log->status,
                'statusLabel' => $this->getStatusLabel($log->status),
                'location' => $log->location,
                'description' => $log->description,
                'occurredAt' => $log->occurred_at?->format('Y-m-d H:i') ?? '',
                'createdAt' => $log->created_at?->format('Y-m-d H:i') ?? '',
            ];
        })->toArray();
    }

    /**
     * Get localized status label
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => __('Pending'),
            'processing' => __('Processing'),
            'picked_up' => __('Picked Up'),
            'in_transit' => __('In Transit'),
            'out_for_delivery' => __('Out for Delivery'),
            'delivered' => __('Delivered'),
            'completed' => __('Completed'),
            'cancelled' => __('Cancelled'),
            'returned' => __('Returned'),
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * Get status color class (DATA_FLOW_POLICY)
     */
    private function getStatusColor(string $status): string
    {
        return match ($status) {
            'created' => 'info',
            'picked_up' => 'primary',
            'in_transit' => 'warning',
            'out_for_delivery' => 'warning',
            'delivered' => 'success',
            'failed' => 'danger',
            'returned' => 'secondary',
            'cancelled' => 'dark',
            default => 'info',
        };
    }

    /**
     * Get status icon class (DATA_FLOW_POLICY)
     */
    private function getStatusIcon(string $status): string
    {
        return match ($status) {
            'created' => 'fa-box',
            'picked_up' => 'fa-truck-loading',
            'in_transit' => 'fa-truck',
            'out_for_delivery' => 'fa-motorcycle',
            'delivered' => 'fa-check-circle',
            'failed' => 'fa-exclamation-circle',
            'returned' => 'fa-undo',
            'cancelled' => 'fa-times-circle',
            default => 'fa-box',
        };
    }

    /**
     * Calculate shipment progress percent (DATA_FLOW_POLICY)
     */
    private function calculateShipmentProgress(string $status): int
    {
        $steps = ['created', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered'];
        $currentIndex = array_search($status, $steps);
        if ($currentIndex === false) {
            $currentIndex = 0;
        }
        return (int) round((($currentIndex + 1) / count($steps)) * 100);
    }
}
