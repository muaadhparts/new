<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\ShipmentTracking;
use App\Services\TryotoService;
use App\Services\ShipmentTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShipmentTrackingController extends Controller
{
    protected $tryotoService;
    protected $trackingService;

    public function __construct(TryotoService $tryotoService, ShipmentTrackingService $trackingService)
    {
        $this->tryotoService = $tryotoService;
        $this->trackingService = $trackingService;
    }

    /**
     * Track shipment page - Public
     */
    public function index(Request $request)
    {
        $trackingNumber = $request->get('tracking');
        $purchaseNumber = $request->get('purchase');
        $shipment = null;
        $history = collect();
        $purchase = null;

        if ($trackingNumber) {
            // Search by tracking number
            $shipment = ShipmentTracking::getLatestByTracking($trackingNumber);

            if ($shipment) {
                $history = ShipmentTracking::getHistoryByTracking($trackingNumber);
                $purchase = Purchase::find($shipment->purchase_id);

                // Try to get live status from Tryoto for API shipments
                if ($shipment->integration_type === ShipmentTracking::INTEGRATION_API) {
                    $liveStatus = $this->tryotoService->trackShipment($trackingNumber);
                    if ($liveStatus['success'] && isset($liveStatus['events'])) {
                        // Merge live events if available
                    }
                }
            }
        } elseif ($purchaseNumber) {
            // Search by purchase number
            $purchase = Purchase::where('purchase_number', $purchaseNumber)->first();

            if ($purchase) {
                // Get all tracking for this purchase (may have multiple merchants)
                $shipment = ShipmentTracking::where('purchase_id', $purchase->id)
                    ->orderBy('occurred_at', 'desc')
                    ->first();

                if ($shipment) {
                    $history = ShipmentTracking::where('purchase_id', $purchase->id)
                        ->orderBy('occurred_at', 'desc')
                        ->get();
                }
            }
        }

        $orderNumber = $purchaseNumber; // For view compatibility
        return view('frontend.tracking.index', compact('shipment', 'history', 'purchase', 'trackingNumber', 'orderNumber'));
    }

    /**
     * API: Get tracking status (AJAX)
     */
    public function getStatus(Request $request)
    {
        $trackingNumber = $request->get('tracking');

        if (!$trackingNumber) {
            return response()->json(['success' => false, 'error' => 'Tracking number required']);
        }

        $shipment = ShipmentTracking::getLatestByTracking($trackingNumber);

        if (!$shipment) {
            return response()->json(['success' => false, 'error' => 'Shipment not found']);
        }

        $history = ShipmentTracking::getHistoryByTracking($trackingNumber);

        return response()->json([
            'success' => true,
            'current' => [
                'status' => $shipment->status,
                'status_ar' => $shipment->status_ar,
                'message' => $shipment->message,
                'message_ar' => $shipment->message_ar,
                'location' => $shipment->location,
                'date' => $shipment->occurred_at?->format('Y-m-d H:i'),
                'company' => $shipment->company_name,
            ],
            'history' => $history->map(function ($log) {
                return [
                    'status' => $log->status,
                    'status_ar' => $log->status_ar,
                    'message_ar' => $log->message_ar,
                    'location' => $log->location,
                    'date' => $log->occurred_at?->format('Y-m-d H:i'),
                ];
            }),
        ]);
    }

    /**
     * Refresh tracking from Tryoto API
     */
    public function refresh(Request $request)
    {
        $trackingNumber = $request->get('tracking');

        if (!$trackingNumber) {
            return response()->json(['success' => false, 'error' => 'Tracking number required']);
        }

        $result = $this->tryotoService->trackShipment($trackingNumber);

        return response()->json($result);
    }

    /**
     * User's shipments list
     */
    public function myShipments()
    {
        if (!Auth::check()) {
            return redirect()->route('user.login');
        }

        $userId = Auth::id();

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
                $shipments[] = [
                    'purchase_number' => $purchase->purchase_number,
                    'tracking_number' => $tracking->tracking_number,
                    'company' => $tracking->company_name,
                    'status' => $tracking->status,
                    'status_ar' => $tracking->status_ar,
                    'date' => $tracking->occurred_at,
                    'purchase_id' => $purchase->id,
                    'integration_type' => $tracking->integration_type,
                ];
            }
        }

        return view('frontend.tracking.my-shipments', compact('shipments'));
    }
}
