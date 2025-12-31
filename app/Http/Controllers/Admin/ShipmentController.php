<?php

namespace App\Http\Controllers\Admin;

use App\Models\Purchase;
use App\Models\User;
use App\Models\ShipmentStatusLog;
use App\Services\TryotoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShipmentController extends AdminBaseController
{
    protected $tryotoService;

    public function __construct()
    {
        parent::__construct();
        $this->tryotoService = new TryotoService();
    }

    /**
     * Admin Shipments Dashboard
     */
    public function index(Request $request)
    {
        $status = $request->get('status');
        $vendorId = $request->get('vendor_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $search = $request->get('search');

        // Get admin statistics
        $stats = $this->tryotoService->getAdminStatistics();

        // Get all vendors for filter dropdown
        $vendors = User::where('is_merchant', 2)
            ->select('id', 'shop_name', 'name')
            ->orderBy('shop_name')
            ->get();

        // Get shipments with filters
        $shipments = ShipmentStatusLog::whereIn('id', function ($sub) use ($status, $vendorId, $dateFrom, $dateTo, $search) {
                $sub->selectRaw('MAX(id)')
                    ->from('shipment_status_logs')
                    ->when($status, function ($q) use ($status) {
                        $q->where('status', $status);
                    })
                    ->when($vendorId, function ($q) use ($vendorId) {
                        $q->where('vendor_id', $vendorId);
                    })
                    ->when($dateFrom, function ($q) use ($dateFrom) {
                        $q->whereDate('status_date', '>=', $dateFrom);
                    })
                    ->when($dateTo, function ($q) use ($dateTo) {
                        $q->whereDate('status_date', '<=', $dateTo);
                    })
                    ->when($search, function ($q) use ($search) {
                        $q->where(function ($sq) use ($search) {
                            $sq->where('tracking_number', 'LIKE', "%{$search}%")
                               ->orWhereHas('purchase', function ($pq) use ($search) {
                                   $pq->where('purchase_number', 'LIKE', "%{$search}%");
                               });
                        });
                    })
                    ->groupBy('tracking_number');
            })
            ->with(['purchase:id,purchase_number,customer_name,pay_amount,currency_sign', 'vendor:id,shop_name,name'])
            ->orderBy('status_date', 'desc')
            ->paginate(25);

        return view('admin.shipments.index', compact('stats', 'shipments', 'vendors', 'status', 'vendorId', 'dateFrom', 'dateTo', 'search'));
    }

    /**
     * View shipment details
     */
    public function show($trackingNumber)
    {
        $shipment = ShipmentStatusLog::where('tracking_number', $trackingNumber)
            ->latest('status_date')
            ->firstOrFail();

        $history = ShipmentStatusLog::where('tracking_number', $trackingNumber)
            ->orderBy('status_date', 'desc')
            ->get();

        $purchase = Purchase::with('user')->find($shipment->purchase_id);
        $vendor = User::find($shipment->vendor_id);

        // Try to get live status
        $liveStatus = $this->tryotoService->trackShipment($trackingNumber);

        return view('admin.shipments.show', compact('shipment', 'history', 'purchase', 'vendor', 'liveStatus'));
    }

    /**
     * Cancel shipment
     */
    public function cancel(Request $request, $trackingNumber)
    {
        $shipment = ShipmentStatusLog::where('tracking_number', $trackingNumber)
            ->firstOrFail();

        // Check if can be cancelled
        if (in_array($shipment->status, ['delivered', 'cancelled', 'returned'])) {
            return back()->with('error', __('This shipment cannot be cancelled'));
        }

        $reason = $request->get('reason', 'Cancelled by admin');
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
        $shipment = ShipmentStatusLog::where('tracking_number', $trackingNumber)
            ->firstOrFail();

        // Use refreshShipmentStatus for better data (includes tracking number updates)
        $result = $this->tryotoService->refreshShipmentStatus($trackingNumber);

        if ($result['success']) {
            $message = __('Shipment status updated');

            // If tracking number was updated
            if (!empty($result['tracking_updated'])) {
                $message .= ' - ' . __('New tracking number: ') . $result['tracking_number'];
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
        $status = $request->get('status');
        $vendorId = $request->get('vendor_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = ShipmentStatusLog::with(['purchase:id,purchase_number,customer_name,pay_amount', 'vendor:id,shop_name'])
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($vendorId, function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            })
            ->when($dateFrom, function ($q) use ($dateFrom) {
                $q->whereDate('status_date', '>=', $dateFrom);
            })
            ->when($dateTo, function ($q) use ($dateTo) {
                $q->whereDate('status_date', '<=', $dateTo);
            })
            ->orderBy('status_date', 'desc')
            ->get();

        $filename = 'all_shipments_' . date('Y-m-d_His') . '.csv';

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
                'Vendor',
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
                    $shipment->vendor->shop_name ?? 'N/A',
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
        $trackingNumbers = $request->get('tracking_numbers', []);

        $updated = 0;
        $failed = 0;

        foreach ($trackingNumbers as $tracking) {
            $result = $this->tryotoService->trackShipment($tracking);
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

    /**
     * Shipment Reports
     */
    public function reports(Request $request)
    {
        $period = $request->get('period', 'month');
        $vendorId = $request->get('vendor_id');

        // Get date range based on period
        $dateFrom = match($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        // Overall Statistics
        $overallStats = ShipmentStatusLog::whereIn('id', function ($sub) use ($dateFrom, $vendorId) {
                $sub->selectRaw('MAX(id)')
                    ->from('shipment_status_logs')
                    ->where('status_date', '>=', $dateFrom)
                    ->when($vendorId, function ($q) use ($vendorId) {
                        $q->where('vendor_id', $vendorId);
                    })
                    ->groupBy('tracking_number');
            })
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Top Vendors by Shipments
        $topVendors = ShipmentStatusLog::where('status_date', '>=', $dateFrom)
            ->whereNotNull('vendor_id')
            ->select('vendor_id', DB::raw('COUNT(DISTINCT tracking_number) as total'))
            ->groupBy('vendor_id')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->with('vendor:id,shop_name,name')
            ->get();

        // Daily Shipments Chart Data
        $dailyShipments = ShipmentStatusLog::where('status_date', '>=', $dateFrom)
            ->when($vendorId, function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            })
            ->select(
                DB::raw('DATE(status_date) as date'),
                DB::raw('COUNT(DISTINCT tracking_number) as count')
            )
            ->groupBy(DB::raw('DATE(status_date)'))
            ->orderBy('date')
            ->get();

        // Status Distribution
        $statusDistribution = [
            'delivered' => $overallStats['delivered'] ?? 0,
            'in_transit' => ($overallStats['in_transit'] ?? 0) + ($overallStats['out_for_delivery'] ?? 0),
            'failed' => $overallStats['failed'] ?? 0,
            'returned' => $overallStats['returned'] ?? 0,
            'cancelled' => $overallStats['cancelled'] ?? 0,
        ];

        // Calculate success rate
        $total = array_sum($statusDistribution);
        $delivered = $statusDistribution['delivered'];
        $successRate = $total > 0 ? round(($delivered / $total) * 100, 1) : 0;

        // Companies Performance
        $companiesPerformance = ShipmentStatusLog::whereIn('id', function ($sub) use ($dateFrom, $vendorId) {
                $sub->selectRaw('MAX(id)')
                    ->from('shipment_status_logs')
                    ->where('status_date', '>=', $dateFrom)
                    ->when($vendorId, function ($q) use ($vendorId) {
                        $q->where('vendor_id', $vendorId);
                    })
                    ->groupBy('tracking_number');
            })
            ->select('company_name', 'status', DB::raw('COUNT(*) as count'))
            ->groupBy('company_name', 'status')
            ->get()
            ->groupBy('company_name');

        // Get vendors for filter
        $vendors = User::where('is_merchant', 2)
            ->select('id', 'shop_name', 'name')
            ->orderBy('shop_name')
            ->get();

        return view('admin.shipments.reports', compact(
            'overallStats',
            'topVendors',
            'dailyShipments',
            'statusDistribution',
            'successRate',
            'companiesPerformance',
            'vendors',
            'period',
            'vendorId',
            'total'
        ));
    }

    /**
     * Webhook handler for Tryoto status updates
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();

        // Log the webhook
        \Log::channel('tryoto')->info('Webhook received', $payload);

        // Validate the webhook
        $trackingNumber = $payload['trackingNumber'] ?? null;

        if (!$trackingNumber) {
            return response()->json(['error' => 'Missing tracking number'], 400);
        }

        // Find existing shipment
        $existingLog = ShipmentStatusLog::where('tracking_number', $trackingNumber)
            ->latest('status_date')
            ->first();

        if (!$existingLog) {
            return response()->json(['error' => 'Shipment not found'], 404);
        }

        // Create new status log
        $newStatus = $payload['status'] ?? 'unknown';
        $statusDate = isset($payload['statusDate']) ? \Carbon\Carbon::parse($payload['statusDate']) : now();

        // Check if this status is newer
        if ($existingLog->status_date && $statusDate->lte($existingLog->status_date)) {
            return response()->json(['message' => 'Status already up to date']);
        }

        $oldStatus = $existingLog->status;

        // Create new log entry
        $newLog = ShipmentStatusLog::create([
            'purchase_id' => $existingLog->purchase_id,
            'vendor_id' => $existingLog->vendor_id,
            'tracking_number' => $trackingNumber,
            'shipment_id' => $payload['shipmentId'] ?? $existingLog->shipment_id,
            'company_name' => $payload['companyName'] ?? $existingLog->company_name,
            'status' => $newStatus,
            'status_ar' => $payload['statusAr'] ?? ShipmentStatusLog::getStatusTranslations()[$newStatus] ?? $newStatus,
            'message' => $payload['message'] ?? null,
            'message_ar' => $payload['messageAr'] ?? null,
            'location' => $payload['location'] ?? null,
            'latitude' => $payload['latitude'] ?? null,
            'longitude' => $payload['longitude'] ?? null,
            'status_date' => $statusDate,
            'raw_data' => $payload,
        ]);

        // Fire event to send notifications
        event(new \App\Events\ShipmentStatusChanged($newLog, $oldStatus));

        return response()->json(['success' => true]);
    }
}
