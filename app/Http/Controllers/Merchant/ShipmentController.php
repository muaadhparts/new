<?php

namespace App\Http\Controllers\Merchant;

use App\Models\Purchase;
use App\Models\ShipmentStatusLog;
use App\Services\TryotoService;
use Illuminate\Http\Request;

class ShipmentController extends MerchantBaseController
{
    protected $tryotoService;

    public function __construct()
    {
        parent::__construct();
        $this->tryotoService = new TryotoService();
    }

    /**
     * Shipments Dashboard
     */
    public function index(Request $request)
    {
        $merchantId = $this->user->id;
        $status = $request->get('status');

        // Get statistics
        $stats = $this->tryotoService->getMerchantStatistics($merchantId);

        // Get shipments with filters
        $query = ShipmentStatusLog::where('merchant_id', $merchantId)
            ->select('tracking_number', 'purchase_id', 'company_name', 'status', 'status_ar', 'status_date', 'shipment_id')
            ->orderBy('created_at', 'desc');

        // Get unique tracking numbers with latest status
        $shipments = ShipmentStatusLog::where('merchant_id', $merchantId)
            ->whereIn('id', function ($sub) use ($merchantId, $status) {
                $sub->selectRaw('MAX(id)')
                    ->from('shipment_status_logs')
                    ->where('merchant_id', $merchantId)
                    ->when($status, function ($q) use ($status) {
                        $q->where('status', $status);
                    })
                    ->groupBy('tracking_number');
            })
            ->with('purchase:id,purchase_number,customer_name,pay_amount')
            ->orderBy('status_date', 'desc')
            ->paginate(20);

        return view('merchant.shipments.index', compact('stats', 'shipments', 'status'));
    }

    /**
     * View shipment details
     */
    public function show($trackingNumber)
    {
        $merchantId = $this->user->id;

        $shipment = ShipmentStatusLog::where('merchant_id', $merchantId)
            ->where('tracking_number', $trackingNumber)
            ->latest('status_date')
            ->firstOrFail();

        $history = ShipmentStatusLog::where('tracking_number', $trackingNumber)
            ->orderBy('status_date', 'desc')
            ->get();

        $purchase = Purchase::find($shipment->purchase_id);

        // Try to get live status
        $liveStatus = $this->tryotoService->trackShipment($trackingNumber);

        return view('merchant.shipments.show', compact('shipment', 'history', 'purchase', 'liveStatus'));
    }

    /**
     * Cancel shipment
     */
    public function cancel(Request $request, $trackingNumber)
    {
        $merchantId = $this->user->id;

        $shipment = ShipmentStatusLog::where('merchant_id', $merchantId)
            ->where('tracking_number', $trackingNumber)
            ->firstOrFail();

        // Check if can be cancelled
        if (in_array($shipment->status, ['delivered', 'cancelled', 'returned'])) {
            return back()->with('error', __('This shipment cannot be cancelled'));
        }

        $reason = $request->get('reason', 'Cancelled by merchant');
        $result = $this->tryotoService->cancelShipment($trackingNumber, $reason);

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

        $shipment = ShipmentStatusLog::where('merchant_id', $merchantId)
            ->where('tracking_number', $trackingNumber)
            ->firstOrFail();

        // Use refreshShipmentStatus for better data (includes tracking number updates)
        $result = $this->tryotoService->refreshShipmentStatus($trackingNumber);

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

        $query = ShipmentStatusLog::where('merchant_id', $merchantId)
            ->with('purchase:id,purchase_number,customer_name,pay_amount')
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($dateFrom, function ($q) use ($dateFrom) {
                $q->whereDate('status_date', '>=', $dateFrom);
            })
            ->when($dateTo, function ($q) use ($dateTo) {
                $q->whereDate('status_date', '<=', $dateTo);
            })
            ->orderBy('status_date', 'desc')
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
                'Order Number',
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
                    $shipment->status_date?->format('Y-m-d H:i'),
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

        foreach ($trackingNumbers as $tracking) {
            $shipment = ShipmentStatusLog::where('merchant_id', $merchantId)
                ->where('tracking_number', $tracking)
                ->first();

            if ($shipment) {
                $result = $this->tryotoService->trackShipment($tracking);
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
