<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Domain\Shipping\Models\ShipmentTracking;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Shipping\Services\ShipmentTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * تتبع الشحنات للعميل
 *
 * - عرض شحنات العميل
 * - عرض تفاصيل التتبع (قراءة فقط)
 */
class ShipmentTrackingController extends Controller
{
    protected ShipmentTrackingService $trackingService;

    public function __construct()
    {
        $this->middleware('auth');
        $this->trackingService = app(ShipmentTrackingService::class);
    }

    /**
     * قائمة شحنات العميل
     */
    public function index()
    {
        $userId = Auth::id();

        // Get purchases with tracking
        $purchaseIds = Purchase::where('user_id', $userId)
            ->pluck('id');

        // Get latest tracking for each purchase
        $shipments = ShipmentTracking::whereIn('purchase_id', $purchaseIds)
            ->whereIn('id', function ($sub) use ($purchaseIds) {
                $sub->selectRaw('MAX(id)')
                    ->from('shipment_trackings')
                    ->whereIn('purchase_id', $purchaseIds)
                    ->groupBy('purchase_id', 'merchant_id');
            })
            ->with('purchase:id,purchase_number,created_at,pay_amount,currency_sign')
            ->with('merchant:id,shop_name,name')
            ->orderBy('occurred_at', 'desc')
            ->get();

        return view('user.shipment-tracking.index', compact('shipments'));
    }

    /**
     * تفاصيل تتبع شحنة
     */
    public function show(int $purchaseId)
    {
        $userId = Auth::id();

        // Verify purchase belongs to user
        $purchase = Purchase::where('id', $purchaseId)
            ->where('user_id', $userId)
            ->first();

        if (!$purchase) {
            return redirect()->route('user.shipment-tracking.index')
                ->with('error', __('Purchase not found'));
        }

        // Get all tracking info (may have multiple merchants)
        $trackings = ShipmentTracking::where('purchase_id', $purchaseId)
            ->whereIn('id', function ($sub) use ($purchaseId) {
                $sub->selectRaw('MAX(id)')
                    ->from('shipment_trackings')
                    ->where('purchase_id', $purchaseId)
                    ->groupBy('merchant_id');
            })
            ->with('merchant:id,shop_name,name')
            ->get();

        // Get full history
        $histories = [];
        foreach ($trackings as $tracking) {
            $histories[$tracking->merchant_id] = $this->trackingService->getTrackingHistory(
                $purchaseId,
                $tracking->merchant_id
            );
        }

        return view('user.shipment-tracking.show', compact('purchase', 'trackings', 'histories'));
    }

    /**
     * تتبع عام برقم التتبع (بدون تسجيل دخول)
     */
    public function track(Request $request)
    {
        $trackingNumber = $request->input('tracking');
        $purchaseNumber = $request->input('purchase');

        // Default view data
        $data = [
            'trackingNumber' => $trackingNumber,
            'orderNumber' => $purchaseNumber,
            'shipment' => null,
            'purchase' => null,
            'history' => collect([]),
            // Pre-computed tracking display values (DATA_FLOW_POLICY)
            'statusIcon' => 'fa-box',
            'statusColor' => 'info',
            'progressPercent' => 0,
            'stepsDisplay' => $this->buildStepsDisplay(null),
        ];

        // If no input, show search form
        if (!$trackingNumber && !$purchaseNumber) {
            return view('frontend.tracking.index', $data);
        }

        // Search by tracking number
        if ($trackingNumber) {
            $shipment = ShipmentTracking::getLatestByTracking($trackingNumber);

            if ($shipment) {
                $data['shipment'] = $shipment;
                $data['purchase'] = $shipment->purchase;
                $data['history'] = ShipmentTracking::getHistoryByTracking($trackingNumber);
                // Pre-compute display values (DATA_FLOW_POLICY)
                $data = array_merge($data, $this->buildShipmentDisplayValues($shipment));
            }

            return view('frontend.tracking.index', $data);
        }

        // Search by purchase number
        if ($purchaseNumber) {
            $purchase = Purchase::where('purchase_number', $purchaseNumber)->first();

            if ($purchase) {
                $shipment = ShipmentTracking::getLatestForPurchase($purchase->id);

                if ($shipment) {
                    $data['shipment'] = $shipment;
                    $data['purchase'] = $purchase;
                    $data['history'] = ShipmentTracking::getHistoryForPurchase($purchase->id);
                    // Pre-compute display values (DATA_FLOW_POLICY)
                    $data = array_merge($data, $this->buildShipmentDisplayValues($shipment));
                }
            }

            return view('frontend.tracking.index', $data);
        }

        return view('frontend.tracking.index', $data);
    }

    /**
     * Build pre-computed display values for shipment tracking (DATA_FLOW_POLICY)
     */
    private function buildShipmentDisplayValues(?ShipmentTracking $shipment): array
    {
        $statusIcons = [
            'created' => 'fa-box',
            'picked_up' => 'fa-truck-loading',
            'in_transit' => 'fa-truck',
            'out_for_delivery' => 'fa-motorcycle',
            'delivered' => 'fa-check-circle',
            'failed' => 'fa-exclamation-circle',
            'returned' => 'fa-undo',
            'cancelled' => 'fa-times-circle',
        ];

        $statusColors = [
            'created' => 'info',
            'picked_up' => 'primary',
            'in_transit' => 'warning',
            'out_for_delivery' => 'warning',
            'delivered' => 'success',
            'failed' => 'danger',
            'returned' => 'secondary',
            'cancelled' => 'dark',
        ];

        $status = $shipment?->status ?? 'created';

        return [
            'statusIcon' => $statusIcons[$status] ?? 'fa-box',
            'statusColor' => $statusColors[$status] ?? 'info',
            'progressPercent' => $this->calculateTrackingProgress($status),
            'stepsDisplay' => $this->buildStepsDisplay($status),
            // Keep mappings for history items
            'statusIcons' => $statusIcons,
            'statusColors' => $statusColors,
        ];
    }

    /**
     * Calculate tracking progress percent (DATA_FLOW_POLICY)
     */
    private function calculateTrackingProgress(?string $status): int
    {
        $steps = ['created', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered'];
        $currentIndex = array_search($status ?? 'created', $steps);
        if ($currentIndex === false) {
            $currentIndex = 0;
        }
        return (int) round((($currentIndex + 1) / count($steps)) * 100);
    }

    /**
     * Build steps display array with isActive pre-computed (DATA_FLOW_POLICY)
     */
    private function buildStepsDisplay(?string $currentStatus): array
    {
        $steps = ['created', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered'];
        $stepNames = [
            'created' => __('Created'),
            'picked_up' => __('Picked Up'),
            'in_transit' => __('In Transit'),
            'out_for_delivery' => __('Out for Delivery'),
            'delivered' => __('Delivered'),
        ];
        $statusIcons = [
            'created' => 'fa-box',
            'picked_up' => 'fa-truck-loading',
            'in_transit' => 'fa-truck',
            'out_for_delivery' => 'fa-motorcycle',
            'delivered' => 'fa-check-circle',
        ];

        $currentIndex = array_search($currentStatus ?? 'created', $steps);
        if ($currentIndex === false) {
            $currentIndex = -1;
        }

        $result = [];
        foreach ($steps as $index => $step) {
            $isActive = $index <= $currentIndex;
            $result[] = [
                'key' => $step,
                'name' => $stepNames[$step],
                'icon' => $statusIcons[$step] ?? 'fa-box',
                'isActive' => $isActive,
                'iconClass' => $isActive ? 'bg-success text-white' : 'bg-light',
                'textClass' => $isActive ? 'fw-bold' : 'text-muted',
            ];
        }

        return $result;
    }

    /**
     * الحصول على حالة التتبع (AJAX)
     */
    public function getStatus(Request $request)
    {
        $trackingNumber = $request->input('tracking');

        if (!$trackingNumber) {
            return response()->json(['success' => false, 'error' => 'Tracking number required']);
        }

        $latest = ShipmentTracking::getLatestByTracking($trackingNumber);

        if (!$latest) {
            return response()->json(['success' => false, 'error' => 'Not found']);
        }

        return response()->json([
            'success' => true,
            'status' => $latest->status,
            'status_ar' => $latest->status_ar,
            'status_en' => $latest->status_en,
            'location' => $latest->location,
            'occurred_at' => $latest->occurred_at?->format('Y-m-d H:i'),
            'progress_percent' => $latest->progress_percent,
            'status_color' => $latest->status_color,
            'is_final' => $latest->is_final,
        ]);
    }
}
