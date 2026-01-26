<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Domain\Shipping\Services\TryotoService;
use App\Domain\Shipping\Services\ShipmentTrackingService;
use App\Domain\Shipping\Services\TrackingDataBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ShipmentTrackingController
 *
 * DATA FLOW POLICY: Uses TrackingDataBuilder service for queries and data preparation
 */
class ShipmentTrackingController extends Controller
{
    public function __construct(
        private TryotoService $tryotoService,
        private ShipmentTrackingService $trackingService,
        private TrackingDataBuilder $trackingDataBuilder,
    ) {}

    /**
     * Track shipment page - Public
     */
    public function index(Request $request)
    {
        $trackingNumber = $request->get('tracking');
        $purchaseNumber = $request->get('purchase');

        // Build tracking data using service
        $trackingData = $this->trackingDataBuilder->buildFromRequest($trackingNumber, $purchaseNumber);

        // Try to get live status from Tryoto for API shipments
        if ($trackingData['shipment'] && $trackingData['integrationType'] === 'api' && $trackingNumber) {
            $liveStatus = $this->tryotoService->trackShipment($trackingNumber);
            if ($liveStatus['success'] && isset($liveStatus['events'])) {
                // Merge live events if available
                $trackingData['liveEvents'] = $liveStatus['events'];
            }
        }

        return view('frontend.tracking.index', [
            'tracking' => $trackingData,
            'trackingNumber' => $trackingNumber,
            'orderNumber' => $purchaseNumber,
        ]);
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

        $trackingData = $this->trackingDataBuilder->buildFromTracking($trackingNumber);

        if (!$trackingData['shipment']) {
            return response()->json(['success' => false, 'error' => 'Shipment not found']);
        }

        return response()->json([
            'success' => true,
            'current' => $trackingData['shipment'],
            'history' => $trackingData['history'],
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

        // Build user shipments using service
        $shipments = $this->trackingDataBuilder->buildUserShipments(Auth::id());

        return view('frontend.tracking.my-shipments', [
            'shipments' => $shipments,
        ]);
    }
}
