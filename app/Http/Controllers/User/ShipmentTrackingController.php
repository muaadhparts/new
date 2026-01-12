<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ShipmentTracking;
use App\Models\Purchase;
use App\Services\ShipmentTrackingService;
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

        if (!$trackingNumber && !$purchaseNumber) {
            return view('frontend.tracking.search');
        }

        if ($trackingNumber) {
            $latest = ShipmentTracking::getLatestByTracking($trackingNumber);

            if (!$latest) {
                return view('frontend.tracking.search', [
                    'error' => __('Tracking number not found'),
                    'searched' => $trackingNumber,
                ]);
            }

            $history = ShipmentTracking::getHistoryByTracking($trackingNumber);

            return view('frontend.tracking.result', compact('latest', 'history'));
        }

        if ($purchaseNumber) {
            $purchase = Purchase::where('purchase_number', $purchaseNumber)->first();

            if (!$purchase) {
                return view('frontend.tracking.search', [
                    'error' => __('Purchase number not found'),
                    'searched' => $purchaseNumber,
                ]);
            }

            $trackings = ShipmentTracking::where('purchase_id', $purchase->id)
                ->whereIn('id', function ($sub) use ($purchase) {
                    $sub->selectRaw('MAX(id)')
                        ->from('shipment_trackings')
                        ->where('purchase_id', $purchase->id)
                        ->groupBy('merchant_id');
                })
                ->with('merchant:id,shop_name,name')
                ->get();

            if ($trackings->isEmpty()) {
                return view('frontend.tracking.search', [
                    'error' => __('No tracking information available yet'),
                    'searched' => $purchaseNumber,
                ]);
            }

            $histories = [];
            foreach ($trackings as $tracking) {
                $histories[$tracking->merchant_id] = $this->trackingService->getTrackingHistory(
                    $purchase->id,
                    $tracking->merchant_id
                );
            }

            return view('frontend.tracking.result-purchase', compact('purchase', 'trackings', 'histories'));
        }
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
