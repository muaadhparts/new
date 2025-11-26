<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Order;
use App\Models\ShipmentStatusLog;
use App\Services\TryotoService;
use Illuminate\Http\Request;

class ShipmentController extends VendorBaseController
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
        $vendorId = $this->user->id;
        $status = $request->get('status');

        // Get statistics
        $stats = $this->tryotoService->getVendorStatistics($vendorId);

        // Get shipments with filters
        $query = ShipmentStatusLog::where('vendor_id', $vendorId)
            ->select('tracking_number', 'order_id', 'company_name', 'status', 'status_ar', 'status_date', 'shipment_id')
            ->orderBy('created_at', 'desc');

        // Get unique tracking numbers with latest status
        $shipments = ShipmentStatusLog::where('vendor_id', $vendorId)
            ->whereIn('id', function ($sub) use ($vendorId, $status) {
                $sub->selectRaw('MAX(id)')
                    ->from('shipment_status_logs')
                    ->where('vendor_id', $vendorId)
                    ->when($status, function ($q) use ($status) {
                        $q->where('status', $status);
                    })
                    ->groupBy('tracking_number');
            })
            ->with('order:id,order_number,customer_name,pay_amount')
            ->orderBy('status_date', 'desc')
            ->paginate(20);

        return view('vendor.shipments.index', compact('stats', 'shipments', 'status'));
    }

    /**
     * View shipment details
     */
    public function show($trackingNumber)
    {
        $vendorId = $this->user->id;

        $shipment = ShipmentStatusLog::where('vendor_id', $vendorId)
            ->where('tracking_number', $trackingNumber)
            ->latest('status_date')
            ->firstOrFail();

        $history = ShipmentStatusLog::where('tracking_number', $trackingNumber)
            ->orderBy('status_date', 'desc')
            ->get();

        $order = Order::find($shipment->order_id);

        // Try to get live status
        $liveStatus = $this->tryotoService->trackShipment($trackingNumber);

        return view('vendor.shipments.show', compact('shipment', 'history', 'order', 'liveStatus'));
    }

    /**
     * Cancel shipment
     */
    public function cancel(Request $request, $trackingNumber)
    {
        $vendorId = $this->user->id;

        $shipment = ShipmentStatusLog::where('vendor_id', $vendorId)
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
        $vendorId = $this->user->id;

        $shipment = ShipmentStatusLog::where('vendor_id', $vendorId)
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
        $vendorId = $this->user->id;
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = ShipmentStatusLog::where('vendor_id', $vendorId)
            ->with('order:id,order_number,customer_name,pay_amount')
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
                    $shipment->order->order_number ?? 'N/A',
                    $shipment->order->customer_name ?? 'N/A',
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
        $vendorId = $this->user->id;
        $trackingNumbers = $request->get('tracking_numbers', []);

        $updated = 0;
        $failed = 0;

        foreach ($trackingNumbers as $tracking) {
            $shipment = ShipmentStatusLog::where('vendor_id', $vendorId)
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
