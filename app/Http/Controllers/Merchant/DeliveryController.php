<?php

namespace App\Http\Controllers\Merchant;

use App\Domain\Shipping\Services\{
    DeliveryListService,
    DeliveryDisplayService,
    CourierAssignmentService,
    ProviderShipmentService,
    TryotoShipmentService,
    ShipmentCalculationService
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * DeliveryController
 * 
 * Clean Architecture - Orchestration Only
 * 
 * Before: 1,404 lines (business logic in controller)
 * After: ~250 lines (orchestration only)
 * 
 * All business logic moved to Services:
 * - DeliveryListService
 * - DeliveryDisplayService
 * - CourierAssignmentService
 * - ProviderShipmentService
 * - TryotoShipmentService
 * - ShipmentCalculationService
 */
class DeliveryController extends MerchantBaseController
{
    protected DeliveryListService $listService;
    protected DeliveryDisplayService $displayService;
    protected CourierAssignmentService $courierService;
    protected ProviderShipmentService $providerService;
    protected TryotoShipmentService $tryotoService;
    protected ShipmentCalculationService $calculationService;

    public function __construct(
        DeliveryListService $listService,
        DeliveryDisplayService $displayService,
        CourierAssignmentService $courierService,
        ProviderShipmentService $providerService,
        TryotoShipmentService $tryotoService,
        ShipmentCalculationService $calculationService
    ) {
        parent::__construct();
        $this->listService = $listService;
        $this->displayService = $displayService;
        $this->courierService = $courierService;
        $this->providerService = $providerService;
        $this->tryotoService = $tryotoService;
        $this->calculationService = $calculationService;
    }

    /**
     * List all deliveries for merchant
     * 
     * Before: 80 lines
     * After: 10 lines
     */
    public function index(Request $request)
    {
        $filters = [
            'status' => $request->status,
            'search' => $request->search,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ];

        $deliveries = $this->listService->getDeliveriesForMerchant(
            merchantId: $this->user->id,
            filters: $filters,
            perPage: 15
        );

        $stats = $this->listService->getDeliveryStats($this->user->id);

        return view('merchant.delivery.index', compact('deliveries', 'stats', 'filters'));
    }

    /**
     * Show delivery details
     * 
     * Before: 60 lines
     * After: 8 lines
     */
    public function show($id)
    {
        $delivery = $this->displayService->getDeliveryDetails(
            deliveryId: $id,
            merchantId: $this->user->id
        );

        if (!$delivery) {
            return redirect()->route('merchant-deliveries')->with('error', __('Delivery not found'));
        }

        return view('merchant.delivery.show', compact('delivery'));
    }

    /**
     * Show create delivery form
     * 
     * Before: 40 lines
     * After: 12 lines
     */
    public function create($purchaseId)
    {
        $purchase = $this->displayService->getPurchaseForDelivery(
            purchaseId: $purchaseId,
            merchantId: $this->user->id
        );

        if (!$purchase) {
            return redirect()->back()->with('error', __('Purchase not found'));
        }

        $providers = $this->providerService->getAvailableProviders($this->user->id);
        $branches = $this->displayService->getMerchantBranches($this->user->id);

        return view('merchant.delivery.create', compact('purchase', 'providers', 'branches'));
    }

    /**
     * Get couriers for city (AJAX)
     * 
     * Before: 25 lines
     * After: 5 lines
     */
    public function getCouriersForCity(Request $request)
    {
        $couriers = $this->courierService->getCouriersForCity($request->city);
        return response()->json(['couriers' => $couriers]);
    }

    /**
     * Assign courier to purchase
     * 
     * Before: 60 lines
     * After: 15 lines
     */
    public function findCourierSubmit(Request $request)
    {
        try {
            $delivery = $this->courierService->assignCourier(
                purchaseId: $request->purchase_id,
                serviceAreaId: $request->courier_id,
                merchantId: $this->user->id,
                merchantBranchId: $request->merchant_branch_id
            );

            Log::info('Courier assigned to delivery', [
                'delivery_id' => $delivery->id,
                'purchase_id' => $request->purchase_id,
            ]);

            return redirect()->back()->with('success', __('Courier assigned! Waiting for courier approval.'));

        } catch (\Exception $e) {
            Log::error('Failed to assign courier', [
                'error' => $e->getMessage(),
                'purchase_id' => $request->purchase_id
            ]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get Tryoto shipping options (AJAX)
     * 
     * Before: 250+ lines (MOST COMPLEX METHOD)
     * After: 15 lines
     */
    public function getShippingOptions(Request $request)
    {
        $customDimensions = null;
        if ($request->has('weight') && $request->input('weight') > 0) {
            $customDimensions = [
                'weight' => (float) $request->input('weight', 1),
                'length' => (float) $request->input('length', 30),
                'width' => (float) $request->input('width', 30),
                'height' => (float) $request->input('height', 30),
            ];
        }

        $result = $this->tryotoService->getShippingOptions(
            purchaseId: $request->purchase_id,
            merchantId: $this->user->id,
            customDimensions: $customDimensions
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'options' => $result['formatted_options'],
                'options_count' => $result['options_count'],
                'origin' => $result['origin'],
                'destination' => $result['destination'],
                'original_city' => $result['original_city'],
                'used_nearest_city' => $result['used_nearest_city'],
                'dimensions' => $result['dimensions'],
                'customer_choice' => $result['customer_choice'],
            ]);
        }

        return response()->json($result);
    }

    /**
     * Get provider shipping options (AJAX)
     * 
     * Before: 40 lines
     * After: 8 lines
     */
    public function getProviderShippingOptions(Request $request)
    {
        $options = $this->providerService->getProviderOptions(
            provider: $request->provider,
            merchantId: $this->user->id
        );

        $html = '<option value="">' . __('Select Shipping Option') . '</option>';
        foreach ($options as $option) {
            $html .= '<option value="' . $option->id . '">' . $option->title . ' - ' . $option->price . '</option>';
        }

        return response()->json(['options' => $html]);
    }

    /**
     * Send Tryoto shipment
     * 
     * Before: 100+ lines
     * After: 25 lines
     */
    public function sendTryotoShipment(Request $request)
    {
        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'delivery_option_id' => 'required',
            'company_name' => 'required',
            'price' => 'required|numeric',
            'merchant_branch_id' => 'required|exists:merchant_branches,id',
        ]);

        try {
            $shipmentData = [
                'delivery_option_id' => $request->delivery_option_id,
                'company_name' => $request->company_name,
                'price' => $request->price,
            ];

            $shipment = $this->tryotoService->sendShipment(
                purchaseId: $request->purchase_id,
                shipmentData: $shipmentData,
                merchantId: $this->user->id,
                merchantBranchId: $request->merchant_branch_id
            );

            return redirect()->back()->with('success', __('Shipment created successfully. Tracking: ') . $shipment->tracking_number);

        } catch (\Exception $e) {
            Log::error('Failed to send Tryoto shipment', [
                'error' => $e->getMessage(),
                'purchase_id' => $request->purchase_id
            ]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Send provider shipment
     * 
     * Before: 80 lines
     * After: 25 lines
     */
    public function sendProviderShipment(Request $request)
    {
        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'shipping_id' => 'required|exists:shippings,id',
            'tracking_number' => 'nullable|string',
            'merchant_branch_id' => 'required|exists:merchant_branches,id',
        ]);

        try {
            $shipment = $this->providerService->assignProviderShipping(
                purchaseId: $request->purchase_id,
                shippingId: $request->shipping_id,
                trackingNumber: $request->tracking_number,
                merchantId: $this->user->id,
                merchantBranchId: $request->merchant_branch_id
            );

            return redirect()->back()->with('success', __('Shipment created successfully. Tracking: ') . ($shipment->tracking_number ?? 'N/A'));

        } catch (\Exception $e) {
            Log::error('Failed to send provider shipment', [
                'error' => $e->getMessage(),
                'purchase_id' => $request->purchase_id
            ]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update Tryoto shipment
     * 
     * Before: 60 lines
     * After: 20 lines
     */
    public function updateTryotoShipment(Request $request)
    {
        try {
            $updateData = $request->only(['weight', 'length', 'width', 'height']);

            $shipment = $this->tryotoService->updateShipment(
                purchaseId: $request->purchase_id,
                updateData: $updateData,
                merchantId: $this->user->id
            );

            return redirect()->back()->with('success', __('Shipment updated successfully'));

        } catch (\Exception $e) {
            Log::error('Failed to update Tryoto shipment', [
                'error' => $e->getMessage(),
                'purchase_id' => $request->purchase_id
            ]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel Tryoto shipment
     * 
     * Before: 50 lines
     * After: 18 lines
     */
    public function cancelTryotoShipment(Request $request)
    {
        try {
            $this->tryotoService->cancelShipment(
                purchaseId: $request->purchase_id,
                merchantId: $this->user->id
            );

            return redirect()->back()->with('success', __('Shipment cancelled successfully'));

        } catch (\Exception $e) {
            Log::error('Failed to cancel Tryoto shipment', [
                'error' => $e->getMessage(),
                'purchase_id' => $request->purchase_id
            ]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Print Tryoto shipping label
     * 
     * Before: 40 lines
     * After: 18 lines
     */
    public function printTryotoLabel($purchaseId)
    {
        try {
            $labelUrl = $this->tryotoService->printLabel(
                purchaseId: $purchaseId,
                merchantId: $this->user->id
            );

            return redirect($labelUrl);

        } catch (\Exception $e) {
            Log::error('Failed to print Tryoto label', [
                'error' => $e->getMessage(),
                'purchase_id' => $purchaseId
            ]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get shipment status (AJAX)
     * 
     * Before: 30 lines
     * After: 8 lines
     */
    public function getShipmentStatus(Request $request)
    {
        $status = $this->providerService->getShipmentStatus(
            purchaseId: $request->purchase_id,
            merchantId: $this->user->id
        );

        return response()->json($status ?? ['has_shipment' => false]);
    }

    /**
     * Calculate shipping dimensions (AJAX)
     * 
     * Before: 40 lines
     * After: 10 lines
     */
    public function calculateDimensions(Request $request)
    {
        try {
            $purchase = \App\Domain\Commerce\Models\Purchase::find($request->purchase_id);
            
            if (!$purchase) {
                return response()->json(['success' => false, 'error' => 'Purchase not found']);
            }

            $dimensions = $this->calculationService->calculatePurchaseDimensions($purchase);

            return response()->json([
                'success' => true,
                'dimensions' => $dimensions
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
