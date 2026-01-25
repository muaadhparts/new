<?php

namespace App\Http\Controllers\Merchant;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Shipping\Models\ShipmentTracking;
use App\Domain\Shipping\Services\TryotoService;
use App\Domain\Shipping\Services\ShipmentTrackingService;
use Illuminate\Http\Request;

class ShipmentController extends MerchantBaseController
{
    protected $tryotoService;
    protected $trackingService;

    public function __construct()
    {
        parent::__construct();
        $this->tryotoService = new TryotoService();
        $this->trackingService = app(ShipmentTrackingService::class);
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

        // Get unique tracking numbers with latest status
        $shipments = ShipmentTracking::where('merchant_id', $merchantId)
            ->whereIn('id', function ($sub) use ($merchantId, $status) {
                $sub->selectRaw('MAX(id)')
                    ->from('shipment_trackings')
                    ->where('merchant_id', $merchantId)
                    ->when($status, function ($q) use ($status) {
                        $q->where('status', $status);
                    })
                    ->groupBy('tracking_number');
            })
            ->with('purchase:id,purchase_number,customer_name,pay_amount')
            ->orderBy('occurred_at', 'desc')
            ->paginate(20);

        return view('merchant.shipments.index', compact('stats', 'shipments', 'status'));
    }

    /**
     * View shipment details
     */
    public function show($trackingNumber)
    {
        $merchantId = $this->user->id;

        $shipment = ShipmentTracking::where('merchant_id', $merchantId)
            ->where('tracking_number', $trackingNumber)
            ->latest('occurred_at')
            ->firstOrFail();

        $history = ShipmentTracking::where('tracking_number', $trackingNumber)
            ->orderBy('occurred_at', 'desc')
            ->get();

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

        $shipment = ShipmentTracking::where('merchant_id', $merchantId)
            ->where('tracking_number', $trackingNumber)
            ->firstOrFail();

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

        $shipment = ShipmentTracking::where('merchant_id', $merchantId)
            ->where('tracking_number', $trackingNumber)
            ->firstOrFail();

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
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = ShipmentTracking::where('merchant_id', $merchantId)
            ->with('purchase:id,purchase_number,customer_name,pay_amount')
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($dateFrom, function ($q) use ($dateFrom) {
                $q->whereDate('occurred_at', '>=', $dateFrom);
            })
            ->when($dateTo, function ($q) use ($dateTo) {
                $q->whereDate('occurred_at', '<=', $dateTo);
            })
            ->orderBy('occurred_at', 'desc')
            ->get();

        $filename = 'shipments_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($query) {
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

            foreach ($query as $shipment) {
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

        $tryotoService = (new TryotoService())->forMerchant($merchantId);

        foreach ($trackingNumbers as $tracking) {
            $shipment = ShipmentTracking::where('merchant_id', $merchantId)
                ->where('tracking_number', $tracking)
                ->where('integration_type', ShipmentTracking::INTEGRATION_API)
                ->first();

            if ($shipment) {
                $result = $tryotoService->trackShipment($tracking);
                if ($result['success']) {
                    $updated++;
                } else {
                    $failed++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => __(':updated updated, :failed failed', ['updated' => $updated, 'failed' => $failed]),
        ]);
    }
}
