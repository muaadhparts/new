<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\ShipmentStatusLog;
use App\Services\TryotoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShipmentTrackingController extends Controller
{
    protected $tryotoService;

    public function __construct(TryotoService $tryotoService)
    {
        $this->tryotoService = $tryotoService;
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
            $shipment = ShipmentStatusLog::where('tracking_number', $trackingNumber)
                ->latest('status_date')
                ->first();

            if ($shipment) {
                $history = ShipmentStatusLog::where('tracking_number', $trackingNumber)
                    ->orderBy('status_date', 'desc')
                    ->get();

                $purchase = Purchase::find($shipment->purchase_id);

                // Try to get live status from Tryoto
                $liveStatus = $this->tryotoService->trackShipment($trackingNumber);
                if ($liveStatus['success'] && isset($liveStatus['events'])) {
                    // Merge live events if available
                }
            }
        } elseif ($purchaseNumber) {
            // Search by purchase number
            $purchase = Purchase::where('purchase_number', $purchaseNumber)->first();

            if ($purchase) {
                $shipment = ShipmentStatusLog::where('purchase_id', $purchase->id)
                    ->latest('status_date')
                    ->first();

                if ($shipment) {
                    $history = ShipmentStatusLog::where('purchase_id', $purchase->id)
                        ->orderBy('status_date', 'desc')
                        ->get();
                }
            }
        }

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

        $shipment = ShipmentStatusLog::where('tracking_number', $trackingNumber)
            ->latest('status_date')
            ->first();

        if (!$shipment) {
            return response()->json(['success' => false, 'error' => 'Shipment not found']);
        }

        $history = ShipmentStatusLog::where('tracking_number', $trackingNumber)
            ->orderBy('status_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'current' => [
                'status' => $shipment->status,
                'status_ar' => $shipment->status_ar,
                'message' => $shipment->message,
                'message_ar' => $shipment->message_ar,
                'location' => $shipment->location,
                'date' => $shipment->status_date?->format('Y-m-d H:i'),
                'company' => $shipment->company_name,
            ],
            'history' => $history->map(function ($log) {
                return [
                    'status' => $log->status,
                    'status_ar' => $log->status_ar,
                    'message_ar' => $log->message_ar,
                    'location' => $log->location,
                    'date' => $log->status_date?->format('Y-m-d H:i'),
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
            ->whereNotNull('merchant_shipping_id')
            ->latest()
            ->get();

        $shipments = [];

        foreach ($purchases as $purchase) {
            $logs = ShipmentStatusLog::where('purchase_id', $purchase->id)
                ->select('tracking_number', 'company_name', 'status', 'status_ar', 'status_date')
                ->orderBy('status_date', 'desc')
                ->get()
                ->groupBy('tracking_number');

            foreach ($logs as $tracking => $trackingLogs) {
                $latest = $trackingLogs->first();
                $shipments[] = [
                    'purchase_number' => $purchase->purchase_number,
                    'tracking_number' => $tracking,
                    'company' => $latest->company_name,
                    'status' => $latest->status,
                    'status_ar' => $latest->status_ar,
                    'date' => $latest->status_date,
                    'purchase_id' => $purchase->id,
                ];
            }
        }

        return view('frontend.tracking.my-shipments', compact('shipments'));
    }
}
