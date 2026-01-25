<?php

namespace App\Http\Controllers\Merchant;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Shipping\Models\ShipmentTracking;
use App\Domain\Shipping\Services\TryotoService;
use App\Domain\Shipping\Services\ShipmentTrackingService;
use App\Domain\Shipping\Services\MerchantShipmentQueryService;
use Illuminate\Http\Request;

class ShipmentController extends MerchantBaseController
{
    public function __construct(
        protected ShipmentTrackingService $trackingService,
        protected MerchantShipmentQueryService $queryService
    ) {
        parent::__construct();
    }

    /**
     * Shipments Dashboard
     */
    public function index(Request $request)
    {
        $merchantId = $this->user->id;
        $status = $request->get('status');

        // Get statistics
        $stats = $this->trackingService->getMerchantStats($merchantId);

        // Get paginated shipments using service
        $shipments = $this->queryService->getPaginatedShipments($merchantId, $status);

        return view('merchant.shipments.index', compact('stats', 'shipments', 'status'));
    }

    /**
     * View shipment details
     */
    public function show($trackingNumber)
    {
        $merchantId = $this->user->id;

        $shipment = $this->queryService->getLatestByTrackingNumber($merchantId, $trackingNumber);
        if (!$shipment) {
            abort(404);
        }

        $history = $this->queryService->getTrackingHistory($trackingNumber);

        $purchase = Purchase::find($shipment->purchase_id);

        // Try to get live status for API shipments
        $liveStatus = ['success' => false];
        if ($shipment->integration_type === ShipmentTracking::INTEGRATION_API) {
            $tryotoService = (new TryotoService())->forMerchant($merchantId);
            $liveStatus = $tryotoService->trackShipment($trackingNumber);
        }

        return view('merchant.shipments.show', compact('shipment', 'history', 'purchase', 'liveStatus'));
    }

    /**
     * Cancel shipment
     */
    public function cancel(Request $request, $trackingNumber)
    {
        $merchantId = $this->user->id;

        $shipment = $this->queryService->getByTrackingNumber($merchantId, $trackingNumber);
        if (!$shipment) {
            abort(404);
        }

        // Check if can be cancelled
        if (in_array($shipment->status, [ShipmentTracking::STATUS_DELIVERED, ShipmentTracking::STATUS_CANCELLED, ShipmentTracking::STATUS_RETURNED])) {
            return back()->with('error', __('This shipment cannot be cancelled'));
        }

        $reason = $request->get('reason', 'Cancelled by merchant');

        // API shipments: call Tryoto API
        if ($shipment->integration_type === ShipmentTracking::INTEGRATION_API) {
            $tryotoService = (new TryotoService())->forMerchant($merchantId);
            $result = $tryotoService->cancelShipment($trackingNumber, $reason);
        } else {
            // Manual shipments: use tracking service
            $result = $this->trackingService->cancelShipment($shipment->purchase_id, $merchantId, $reason);
            $result = ['success' => (bool)$result];
        }

        if ($result['success']) {
            return back()->with('success', __('Shipment cancelled successfully'));
        }

        return back()->with('error', $result['error'] ?? __('Failed to cancel shipment'));
    }

    /**
     * Refresh shipment status
     */
    public function refresh($trackingNumber)
    {
        $merchantId = $this->user->id;

        $shipment = $this->queryService->getByTrackingNumber($merchantId, $trackingNumber);
        if (!$shipment) {
            abort(404);
        }

        // Only API shipments can be refreshed
        if ($shipment->integration_type !== ShipmentTracking::INTEGRATION_API) {
            return back()->with('error', __('Only API shipments can be refreshed'));
        }

        // Use refreshShipmentStatus for better data (includes tracking number updates)
        $tryotoService = (new TryotoService())->forMerchant($merchantId);
        $result = $tryotoService->refreshShipmentStatus($trackingNumber);

        if ($result['success']) {
            $message = __('Shipment status updated');

            // If tracking number was updated
            if (!empty($result['tracking_updated'])) {
                $message .= ' - ' . __('New tracking number: ') . $result['tracking_number'];
            }

            // If AWB (shipping label) is available
            if (!empty($result['awb_url'])) {
                $message .= ' - ' . __('Shipping label available');
            }

            return back()->with('success', $message);
        }

        return back()->with('error', __('Failed to refresh status: ') . ($result['error'] ?? ''));
    }

    /**
     * Export shipments
     */
    public function export(Request $request)
    {
        $merchantId = $this->user->id;

        $filters = [
            'status' => $request->get('status'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $shipments = $this->queryService->getShipmentsForExport($merchantId, $filters);

        $filename = 'shipments_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($shipments) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Headers
            fputcsv($file, [
                'Tracking Number',
                'Purchase Number',
                'Customer',
                'Company',
                'Status',
                'Status (AR)',
                'Location',
                'Date',
            ]);

            foreach ($shipments as $shipment) {
                fputcsv($file, [
                    $shipment->tracking_number,
                    $shipment->purchase->purchase_number ?? 'N/A',
                    $shipment->purchase->customer_name ?? 'N/A',
                    $shipment->company_name,
                    $shipment->status,
                    $shipment->status_ar,
                    $shipment->location,
                    $shipment->occurred_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Bulk refresh statuses
     */
    public function bulkRefresh(Request $request)
    {
        $merchantId = $this->user->id;
        $trackingNumbers = $request->get('tracking_numbers', []);

        $updated = 0;
        $failed = 0;

        // Fetch all API shipments at once
        $apiShipments = $this->queryService->getApiShipmentsByTrackingNumbers($merchantId, $trackingNumbers);
        $validTrackingNumbers = $apiShipments->pluck('tracking_number')->unique()->toArray();

        $tryotoService = (new TryotoService())->forMerchant($merchantId);

        foreach ($validTrackingNumbers as $tracking) {
            $result = $tryotoService->trackShipment($tracking);
            if ($result['success']) {
                $updated++;
            } else {
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => __(':updated updated, :failed failed', ['updated' => $updated, 'failed' => $failed]),
        ]);
    }
}
