<?php

namespace App\Http\Controllers\Operator;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Identity\Models\User;
use App\Domain\Shipping\Models\ShipmentTracking;
use App\Domain\Shipping\Services\TryotoService;
use App\Domain\Shipping\Services\ShipmentTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShipmentController extends OperatorBaseController
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
     * Operator Shipments Dashboard
     */
    public function index(Request $request)
    {
        $status = $request->get('status');
        $merchantId = $request->get('merchant_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $search = $request->get('search');

        // Get operator statistics
        $stats = $this->trackingService->getOperatorStats();

        // Get all merchants for filter dropdown
        $merchants = User::where('is_merchant', 2)
            ->select('id', 'shop_name', 'name')
            ->orderBy('shop_name')
            ->get();

        // Get shipments with filters
        $shipments = ShipmentTracking::whereIn('id', function ($sub) use ($status, $merchantId, $dateFrom, $dateTo, $search) {
                $sub->selectRaw('MAX(id)')
                    ->from('shipment_trackings')
                    ->when($status, function ($q) use ($status) {
                        $q->where('status', $status);
                    })
                    ->when($merchantId, function ($q) use ($merchantId) {
                        $q->where('merchant_id', $merchantId);
                    })
                    ->when($dateFrom, function ($q) use ($dateFrom) {
                        $q->whereDate('occurred_at', '>=', $dateFrom);
                    })
                    ->when($dateTo, function ($q) use ($dateTo) {
                        $q->whereDate('occurred_at', '<=', $dateTo);
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
            ->with(['purchase:id,purchase_number,customer_name,pay_amount,currency_sign', 'merchant:id,shop_name,name'])
            ->orderBy('occurred_at', 'desc')
            ->paginate(25);

        return view('operator.shipments.index', compact('stats', 'shipments', 'merchants', 'status', 'merchantId', 'dateFrom', 'dateTo', 'search'));
    }

    /**
     * View shipment details
     */
    public function show($trackingNumber)
    {
        $shipment = ShipmentTracking::where('tracking_number', $trackingNumber)
            ->latest('occurred_at')
            ->firstOrFail();

        $history = ShipmentTracking::where('tracking_number', $trackingNumber)
            ->orderBy('occurred_at', 'desc')
            ->get();

        $purchase = Purchase::with('user')->find($shipment->purchase_id);
        $merchant = User::find($shipment->merchant_id);

        // Try to get live status for API shipments
        $liveStatus = ['success' => false];
        if ($shipment->integration_type === ShipmentTracking::INTEGRATION_API) {
            $tryotoService = (new TryotoService())->forMerchant($shipment->merchant_id);
            $liveStatus = $tryotoService->trackShipment($trackingNumber);
        }

        return view('operator.shipments.show', compact('shipment', 'history', 'purchase', 'merchant', 'liveStatus'));
    }

    /**
     * Cancel shipment
     */
    public function cancel(Request $request, $trackingNumber)
    {
        $shipment = ShipmentTracking::where('tracking_number', $trackingNumber)
            ->firstOrFail();

        // Check if can be cancelled
        if (in_array($shipment->status, [ShipmentTracking::STATUS_DELIVERED, ShipmentTracking::STATUS_CANCELLED, ShipmentTracking::STATUS_RETURNED])) {
            return back()->with('error', __('This shipment cannot be cancelled'));
        }

        $reason = $request->get('reason', 'Cancelled by admin');

        // API shipments: call Tryoto API
        if ($shipment->integration_type === ShipmentTracking::INTEGRATION_API) {
            $tryotoService = (new TryotoService())->forMerchant($shipment->merchant_id);
            $result = $tryotoService->cancelShipment($trackingNumber, $reason);
        } else {
            // Manual shipments: use tracking service
            $result = $this->trackingService->cancelShipment($shipment->purchase_id, $shipment->merchant_id, $reason);
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
        $shipment = ShipmentTracking::where('tracking_number', $trackingNumber)
            ->firstOrFail();

        // Only API shipments can be refreshed
        if ($shipment->integration_type !== ShipmentTracking::INTEGRATION_API) {
            return back()->with('error', __('Only API shipments can be refreshed'));
        }

        // Use refreshShipmentStatus for better data (includes tracking number updates)
        $tryotoService = (new TryotoService())->forMerchant($shipment->merchant_id);
        $result = $tryotoService->refreshShipmentStatus($trackingNumber);

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
        $merchantId = $request->get('merchant_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = ShipmentTracking::with(['purchase:id,purchase_number,customer_name,pay_amount', 'merchant:id,shop_name'])
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($merchantId, function ($q) use ($merchantId) {
                $q->where('merchant_id', $merchantId);
            })
            ->when($dateFrom, function ($q) use ($dateFrom) {
                $q->whereDate('occurred_at', '>=', $dateFrom);
            })
            ->when($dateTo, function ($q) use ($dateTo) {
                $q->whereDate('occurred_at', '<=', $dateTo);
            })
            ->orderBy('occurred_at', 'desc')
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
                'Purchase Number',
                'Customer',
                'Merchant',
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
                    $shipment->merchant->shop_name ?? 'N/A',
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
        $trackingNumbers = $request->get('tracking_numbers', []);

        $updated = 0;
        $failed = 0;

        foreach ($trackingNumbers as $tracking) {
            $shipment = ShipmentTracking::where('tracking_number', $tracking)
                ->where('integration_type', ShipmentTracking::INTEGRATION_API)
                ->first();

            if ($shipment) {
                $tryotoService = (new TryotoService())->forMerchant($shipment->merchant_id);
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

    /**
     * Shipment Reports
     */
    public function reports(Request $request)
    {
        $period = $request->get('period', 'month');
        $merchantId = $request->get('merchant_id');

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
        $overallStats = ShipmentTracking::whereIn('id', function ($sub) use ($dateFrom, $merchantId) {
                $sub->selectRaw('MAX(id)')
                    ->from('shipment_trackings')
                    ->where('occurred_at', '>=', $dateFrom)
                    ->when($merchantId, function ($q) use ($merchantId) {
                        $q->where('merchant_id', $merchantId);
                    })
                    ->groupBy('tracking_number');
            })
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Top Merchants by Shipments
        $topMerchants = ShipmentTracking::where('occurred_at', '>=', $dateFrom)
            ->whereNotNull('merchant_id')
            ->select('merchant_id', DB::raw('COUNT(DISTINCT tracking_number) as total'))
            ->groupBy('merchant_id')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->with('merchant:id,shop_name,name')
            ->get();

        // Daily Shipments Chart Data
        $dailyShipments = ShipmentTracking::where('occurred_at', '>=', $dateFrom)
            ->when($merchantId, function ($q) use ($merchantId) {
                $q->where('merchant_id', $merchantId);
            })
            ->select(
                DB::raw('DATE(occurred_at) as date'),
                DB::raw('COUNT(DISTINCT tracking_number) as count')
            )
            ->groupBy(DB::raw('DATE(occurred_at)'))
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
        $companiesPerformance = ShipmentTracking::whereIn('id', function ($sub) use ($dateFrom, $merchantId) {
                $sub->selectRaw('MAX(id)')
                    ->from('shipment_trackings')
                    ->where('occurred_at', '>=', $dateFrom)
                    ->when($merchantId, function ($q) use ($merchantId) {
                        $q->where('merchant_id', $merchantId);
                    })
                    ->groupBy('tracking_number');
            })
            ->select('company_name', 'status', DB::raw('COUNT(*) as count'))
            ->groupBy('company_name', 'status')
            ->get()
            ->groupBy('company_name');

        // Get merchants for filter
        $merchants = User::where('is_merchant', 2)
            ->select('id', 'shop_name', 'name')
            ->orderBy('shop_name')
            ->get();

        return view('operator.shipments.reports', compact(
            'overallStats',
            'topMerchants',
            'dailyShipments',
            'statusDistribution',
            'successRate',
            'companiesPerformance',
            'merchants',
            'period',
            'merchantId',
            'total'
        ));
    }

    /**
     * Webhook handler for Tryoto status updates
     * NOTE: Use TryotoWebhookController instead - this is kept for backwards compatibility
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();

        // Log the webhook
        \Log::channel('tryoto')->info('Webhook received (Operator controller)', $payload);

        // Validate the webhook
        $trackingNumber = $payload['trackingNumber'] ?? null;

        if (!$trackingNumber) {
            return response()->json(['error' => 'Missing tracking number'], 400);
        }

        // Find existing shipment
        $existingTracking = ShipmentTracking::getLatestByTracking($trackingNumber);

        if (!$existingTracking) {
            return response()->json(['error' => 'Shipment not found'], 404);
        }

        // Create new status log
        $newStatus = $payload['status'] ?? 'unknown';
        $statusDate = isset($payload['statusDate']) ? \Carbon\Carbon::parse($payload['statusDate']) : now();

        // Check if this status is newer
        if ($existingTracking->occurred_at && $statusDate->lte($existingTracking->occurred_at)) {
            return response()->json(['message' => 'Status already up to date']);
        }

        $oldStatus = $existingTracking->status;

        // Use tracking service to update
        $newTracking = $this->trackingService->updateFromApi(
            purchaseId: $existingTracking->purchase_id,
            merchantId: $existingTracking->merchant_id,
            status: $newStatus,
            location: $payload['location'] ?? null,
            message: $payload['message'] ?? null,
            rawData: $payload,
            occurredAt: $statusDate
        );

        // Fire event to send notifications
        event(new \App\Events\ShipmentStatusChanged($newTracking, $oldStatus));

        return response()->json(['success' => true]);
    }
}
