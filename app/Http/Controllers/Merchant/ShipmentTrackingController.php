<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Domain\Shipping\Models\ShipmentTracking;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Shipping\Models\Shipping;
use App\Domain\Shipping\Services\ShipmentTrackingService;
use App\Domain\Shipping\Services\TryotoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * تتبع الشحنات للتاجر
 *
 * - عرض قائمة الشحنات
 * - عرض تفاصيل التتبع
 * - تحديث الحالة (للشحنات اليدوية فقط)
 * - تحديث من API (للشحنات الخارجية)
 */
class ShipmentTrackingController extends MerchantBaseController
{
    protected ShipmentTrackingService $trackingService;

    public function __construct()
    {
        parent::__construct();
        $this->trackingService = app(ShipmentTrackingService::class);
    }

    /**
     * قائمة شحنات التاجر
     */
    public function index(Request $request)
    {
        $merchantId = $this->user->id;

        // Get latest status for each shipment
        $query = ShipmentTracking::where('merchant_id', $merchantId)
            ->whereIn('id', function ($sub) use ($merchantId) {
                $sub->selectRaw('MAX(id)')
                    ->from('shipment_trackings')
                    ->where('merchant_id', $merchantId)
                    ->groupBy('purchase_id');
            })
            ->with('purchase:id,purchase_number,customer_name,customer_city,created_at')
            ->orderBy('occurred_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by integration type
        if ($request->filled('type')) {
            $query->where('integration_type', $request->type);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhereHas('purchase', function ($pq) use ($search) {
                      $pq->where('purchase_number', 'like', "%{$search}%")
                         ->orWhere('customer_name', 'like', "%{$search}%");
                  });
            });
        }

        $shipments = $query->paginate(20);

        // Stats
        $stats = $this->trackingService->getMerchantStats($merchantId);

        // PRE-COMPUTED: All status options (DATA_FLOW_POLICY - no static Model calls in view)
        $allStatusOptions = [];
        foreach (ShipmentTracking::getAllStatuses() as $status) {
            $allStatusOptions[] = [
                'value' => $status,
                'label' => ShipmentTracking::getStatusTranslation($status),
            ];
        }

        return view('merchant.shipment-tracking.index', compact('shipments', 'stats', 'allStatusOptions'));
    }

    /**
     * تفاصيل شحنة معينة
     */
    public function show(int $purchaseId)
    {
        $merchantId = $this->user->id;

        $shipmentInfo = $this->trackingService->getShipmentInfo($purchaseId, $merchantId);

        if (!$shipmentInfo) {
            return redirect()->route('merchant.shipment-tracking.index')
                ->with('error', __('Shipment not found'));
        }

        // PRE-COMPUTED: Add occurred_at_formatted to events (DATA_FLOW_POLICY)
        if (isset($shipmentInfo['events'])) {
            $shipmentInfo['events'] = array_map(function ($event) {
                $event['occurred_at_formatted'] = $event['occurred_at']
                    ? \Carbon\Carbon::parse($event['occurred_at'])->format('Y-m-d H:i')
                    : '-';
                return $event;
            }, $shipmentInfo['events']);
        }

        // PRE-COMPUTED: Manual update status options (DATA_FLOW_POLICY - no static Model calls in view)
        $manualUpdateStatuses = [];
        foreach (ShipmentTracking::getManualUpdateStatuses() as $status) {
            $manualUpdateStatuses[] = [
                'value' => $status,
                'label' => ShipmentTracking::getStatusTranslation($status),
            ];
        }

        $purchase = Purchase::with('merchantPurchases')
            ->find($purchaseId);

        return view('merchant.shipment-tracking.show', compact('shipmentInfo', 'purchase', 'manualUpdateStatuses'));
    }

    /**
     * تحديث حالة الشحنة اليدوية
     */
    public function updateStatus(Request $request, int $purchaseId)
    {
        $request->validate([
            'status' => 'required|string|in:' . implode(',', ShipmentTracking::getManualUpdateStatuses()),
            'location' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:500',
        ]);

        $merchantId = $this->user->id;

        // Verify this is a manual shipment
        $latest = ShipmentTracking::getLatestForPurchase($purchaseId, $merchantId);

        if (!$latest) {
            return back()->with('error', __('Shipment not found'));
        }

        if ($latest->integration_type !== ShipmentTracking::INTEGRATION_MANUAL) {
            return back()->with('error', __('Cannot manually update API shipments'));
        }

        if ($latest->is_final) {
            return back()->with('error', __('Cannot update completed shipments'));
        }

        $result = $this->trackingService->updateManually(
            $purchaseId,
            $merchantId,
            $request->status,
            $request->location,
            $request->message
        );

        if ($result) {
            return back()->with('success', __('Shipment status updated successfully'));
        }

        return back()->with('error', __('Failed to update shipment status'));
    }

    /**
     * بدء شحنة يدوية جديدة
     */
    public function startManualShipment(Request $request, int $purchaseId)
    {
        $request->validate([
            'shipping_id' => 'required|exists:shippings,id',
            'tracking_number' => 'nullable|string|max:100',
        ]);

        $merchantId = $this->user->id;

        // Check if tracking already exists
        if (ShipmentTracking::hasTracking($purchaseId, $merchantId)) {
            return back()->with('error', __('Tracking already exists for this purchase'));
        }

        $shipping = Shipping::find($request->shipping_id);

        if (!$shipping || $shipping->integration_type !== 'manual') {
            return back()->with('error', __('Invalid shipping method'));
        }

        // Create manual shipment tracking
        $this->trackingService->createManualShipment(
            purchaseId: $purchaseId,
            merchantId: $merchantId,
            shippingId: $shipping->id,
            provider: $shipping->provider,
            trackingNumber: $request->tracking_number,
            companyName: $shipping->name
        );

        return back()->with('success', __('Shipment tracking started'));
    }

    /**
     * تحديث من API (Tryoto)
     */
    public function refreshFromApi(int $purchaseId)
    {
        $merchantId = $this->user->id;

        $latest = ShipmentTracking::getLatestForPurchase($purchaseId, $merchantId);

        if (!$latest) {
            return response()->json(['success' => false, 'error' => 'Shipment not found']);
        }

        if ($latest->integration_type !== ShipmentTracking::INTEGRATION_API) {
            return response()->json(['success' => false, 'error' => 'Not an API shipment']);
        }

        if (!$latest->tracking_number) {
            return response()->json(['success' => false, 'error' => 'No tracking number']);
        }

        try {
            $tryotoService = (new TryotoService())->forMerchant($merchantId);
            $result = $tryotoService->trackShipment($latest->tracking_number);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'status' => $result['status'],
                    'status_ar' => $result['status_ar'],
                    'location' => $result['location'],
                ]);
            }

            return response()->json(['success' => false, 'error' => $result['error'] ?? 'API error']);
        } catch (\Exception $e) {
            Log::error('ShipmentTracking: API refresh failed', [
                'purchase_id' => $purchaseId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'error' => 'Failed to refresh']);
        }
    }

    /**
     * الحصول على تاريخ التتبع (AJAX)
     */
    public function getHistory(int $purchaseId)
    {
        $merchantId = $this->user->id;

        $history = $this->trackingService->getTrackingHistory($purchaseId, $merchantId);

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }

    /**
     * إحصائيات التتبع
     */
    public function stats()
    {
        $stats = $this->trackingService->getMerchantStats($this->user->id);
        return response()->json($stats);
    }
}
