<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\MerchantCheckout\MerchantCheckoutService;
use App\Services\MerchantCheckout\MerchantPurchaseCreator;
use App\Services\MerchantCheckout\MerchantSessionManager;
use App\Services\Cart\MerchantCartManager;
use App\Models\Muaadhsetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Branch Checkout Controller - API First
 *
 * Clean checkout flow for branch-scoped system
 * All methods return JSON for API or render views
 *
 * NOTE: Checkout is now branch-scoped (branchId parameter),
 *       but payment/shipping methods remain merchant-scoped (from branch->user)
 */
class CheckoutMerchantController extends Controller
{
    protected MerchantCheckoutService $checkoutService;
    protected MerchantPurchaseCreator $purchaseCreator;
    protected MerchantSessionManager $sessionManager;
    protected MerchantCartManager $cartService;

    public function __construct(
        MerchantCheckoutService $checkoutService,
        MerchantPurchaseCreator $purchaseCreator,
        MerchantSessionManager $sessionManager,
        MerchantCartManager $cartService
    ) {
        $this->checkoutService = $checkoutService;
        $this->purchaseCreator = $purchaseCreator;
        $this->sessionManager = $sessionManager;
        $this->cartService = $cartService;

        $this->middleware('auth');
    }

    // =========================================================================
    // STEP 1: ADDRESS
    // =========================================================================

    /**
     * GET /branch/{branchId}/checkout/address
     * Display address form
     */
    public function showAddress(int $branchId): View|JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $result = $this->checkoutService->initializeAddressStep($branchId);

        if (!$result['success']) {
            if (request()->wantsJson()) {
                return response()->json($result, 400);
            }
            return redirect()->route('merchant-cart.index')
                ->with('unsuccess', $result['message'] ?? __('Unable to proceed to checkout'));
        }

        if (request()->wantsJson()) {
            return response()->json($result);
        }

        // Get Google Maps API key
        $googleMapsApiKey = \App\Models\ApiCredential::getCredential('google_maps', 'api_key');

        // Get currency
        $curr = $this->checkoutService->getPriceCalculator()->getMonetaryUnit();

        // Build address data for view (from customer defaults + saved address)
        $customer = $result['data']['customer'] ?? [];
        $savedAddress = $result['data']['saved_address'] ?? [];

        // Merge customer defaults with saved address, mapping field names for view
        $address = array_merge($customer, [
            'customer_name' => $customer['name'] ?? '',
            'customer_email' => $customer['email'] ?? '',
            'customer_phone' => $customer['phone'] ?? '',
            'customer_address' => $customer['address'] ?? '',
            'customer_city' => $customer['city'] ?? '',
            'customer_state' => $customer['state'] ?? '',
            'customer_zip' => $customer['zip'] ?? '',
            'customer_country' => $savedAddress['customer_country'] ?? '',
            'country_id' => $customer['country_id'] ?? 0,
            'state_id' => $savedAddress['state_id'] ?? 0,
            'city_id' => $savedAddress['city_id'] ?? 0,
            'latitude' => $customer['latitude'] ?? '',
            'longitude' => $customer['longitude'] ?? '',
        ]);

        return view('merchant.checkout.address', [
            'branch_id' => $branchId,
            'branch' => $result['data']['branch'] ?? [],
            'address' => $address,
            'cart' => $result['data']['cart'] ?? [],
            'googleMapsApiKey' => $googleMapsApiKey,
            'curr' => $curr,
            'gs' => \DB::table('muaadhsettings')->first(),
        ]);
    }

    /**
     * GET /branch/{branchId}/checkout/address/api
     * API endpoint for address step data
     */
    public function getAddressData(int $branchId): JsonResponse
    {
        $result = $this->checkoutService->initializeAddressStep($branchId);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * POST /branch/{branchId}/checkout/address
     * Process address submission
     */
    public function processAddress(Request $request, int $branchId): JsonResponse
    {
        $validated = $request->validate([
            'customer_address' => 'required|string|max:500',
            'customer_city' => 'nullable|string|max:100',
            'customer_state' => 'nullable|string|max:100',
            'customer_zip' => 'nullable|string|max:20',
            'customer_country' => 'nullable|string|max:100',
            'country_id' => 'nullable|integer',
            'state_id' => 'nullable|integer',
            'city_id' => 'nullable|integer',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $result = $this->checkoutService->processAddressStep($branchId, $validated);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    // =========================================================================
    // STEP 2: SHIPPING
    // =========================================================================

    /**
     * GET /branch/{branchId}/checkout/shipping
     * Display shipping options
     */
    public function showShipping(int $branchId): View|JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $result = $this->checkoutService->initializeShippingStep($branchId);

        if (!$result['success']) {
            if (request()->wantsJson()) {
                return response()->json($result, 400);
            }
            $redirectUrl = $result['redirect'] ?? route('branch.checkout.address', $branchId);
            return redirect()->to($redirectUrl)
                ->with('unsuccess', $result['message'] ?? __('Please complete previous step'));
        }

        if (request()->wantsJson()) {
            return response()->json($result);
        }

        // Get currency
        $curr = $this->checkoutService->getPriceCalculator()->getMonetaryUnit();

        return view('merchant.checkout.shipping', [
            'branch_id' => $branchId,
            'branch' => $result['data']['branch'] ?? [],
            'address' => $result['data']['address'] ?? [],
            'cart' => $result['data']['cart'] ?? [],
            'totals' => $result['data']['totals'] ?? [],
            'shipping_providers' => $result['data']['shipping_options'] ?? [],
            'packaging' => $result['data']['packaging_options'] ?? [],
            'couriers' => $result['data']['courier_options'] ?? [],
            'curr' => $curr,
            'gs' => \DB::table('muaadhsettings')->first(),
        ]);
    }

    /**
     * GET /branch/{branchId}/checkout/shipping/api
     * API endpoint for shipping step data
     */
    public function getShippingData(int $branchId): JsonResponse
    {
        $result = $this->checkoutService->initializeShippingStep($branchId);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * POST /branch/{branchId}/checkout/shipping
     * Process shipping selection
     */
    public function processShipping(Request $request, int $branchId): JsonResponse
    {
        $validated = $request->validate([
            'delivery_type' => 'required|in:shipping,local_courier',
            // Shipping fields
            'shipping_id' => 'nullable',
            'shipping_name' => 'nullable|string',
            'shipping_cost' => 'nullable|numeric',
            'shipping_original_cost' => 'nullable|numeric',
            'shipping_is_free' => 'nullable|in:0,1',
            'shipping_provider' => 'nullable|string',
            // Courier fields
            'courier_id' => 'nullable|integer',
            'courier_name' => 'nullable|string',
            'courier_fee' => 'nullable|numeric',
            'service_area_id' => 'nullable|integer',
            'merchant_branch_id' => 'nullable|integer',
            // Packing fields (removed but kept for compatibility)
            'packing_id' => 'nullable|integer',
            'packing_name' => 'nullable|string',
            'packing_cost' => 'nullable|numeric',
        ]);

        $result = $this->checkoutService->processShippingStep($branchId, $validated);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * POST /branch/{branchId}/checkout/shipping/calculate
     * Calculate shipping cost (AJAX)
     */
    public function calculateShipping(Request $request, int $branchId): JsonResponse
    {
        $priceCalculator = $this->checkoutService->getPriceCalculator();
        $cartService = $this->checkoutService->getCartService();

        $cartSummary = $cartService->getBranchCartSummary($branchId);
        $itemsTotal = $cartSummary['total_price'];

        $deliveryType = $request->input('delivery_type', 'shipping');

        if ($deliveryType === 'local_courier') {
            $addressData = $this->sessionManager->getAddressData($branchId);
            $courierInfo = $priceCalculator->calculateCourierFee(
                (int)$request->input('courier_id', 0),
                (int)($addressData['city_id'] ?? 0)
            );
            return response()->json([
                'success' => true,
                'delivery_type' => 'local_courier',
                'courier' => $courierInfo,
                'shipping' => null,
            ]);
        }

        $shippingInfo = $priceCalculator->calculateShippingCost(
            (int)$request->input('shipping_id', 0),
            $itemsTotal
        );

        return response()->json([
            'success' => true,
            'delivery_type' => 'shipping',
            'shipping' => $shippingInfo,
            'courier' => null,
        ]);
    }

    /**
     * POST /branch/{branchId}/checkout/delivery-options
     * Get available delivery options for the branch
     */
    public function getDeliveryOptions(Request $request, int $branchId): JsonResponse
    {
        $result = $this->checkoutService->initializeShippingStep($branchId);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? __('Unable to load shipping options'),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'shipping_options' => $result['data']['shipping_options'] ?? [],
            'courier_options' => $result['data']['courier_options'] ?? [],
            'packaging_options' => $result['data']['packaging_options'] ?? [],
        ]);
    }

    // =========================================================================
    // STEP 3: PAYMENT
    // =========================================================================

    /**
     * GET /branch/{branchId}/checkout/payment
     * Display payment methods
     */
    public function showPayment(int $branchId): View|JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $result = $this->checkoutService->initializePaymentStep($branchId);

        if (!$result['success']) {
            if (request()->wantsJson()) {
                return response()->json($result, 400);
            }

            if (isset($result['redirect'])) {
                return redirect()->to($result['redirect'])
                    ->with('unsuccess', $result['message'] ?? __('Please complete previous steps'));
            }

            return redirect()->route('merchant-cart.index')
                ->with('unsuccess', $result['message'] ?? __('Unable to proceed'));
        }

        if (request()->wantsJson()) {
            return response()->json($result);
        }

        // Get currency and price calculator
        $priceCalculator = $this->checkoutService->getPriceCalculator();
        $curr = $priceCalculator->getMonetaryUnit();

        return view('merchant.checkout.payment', [
            'branch_id' => $branchId,
            'branch' => $result['data']['branch'] ?? [],
            'cart' => $result['data']['cart'] ?? [],
            'totals' => $result['data']['totals'] ?? [],
            'shipping' => $result['data']['shipping'] ?? [],
            'address' => $result['data']['address'] ?? [],
            'payment_methods' => $result['data']['payment_methods'] ?? [],
            'curr' => $curr,
            'gs' => \DB::table('muaadhsettings')->first(),
        ]);
    }

    /**
     * GET /branch/{branchId}/checkout/payment/api
     * API endpoint for payment step data
     */
    public function getPaymentData(int $branchId): JsonResponse
    {
        $result = $this->checkoutService->initializePaymentStep($branchId);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * GET /branch/{branchId}/checkout/totals
     * Get current totals (AJAX)
     */
    public function getTotals(int $branchId): JsonResponse
    {
        $priceCalculator = $this->checkoutService->getPriceCalculator();
        $cartService = $this->checkoutService->getCartService();

        $addressData = $this->sessionManager->getAddressData($branchId);
        $shippingData = $this->sessionManager->getShippingData($branchId);
        $discountData = $this->sessionManager->getDiscountData($branchId);
        $cartSummary = $cartService->getBranchCartSummary($branchId);

        $totals = $priceCalculator->calculateTotals($cartSummary['items'], [
            'discount_amount' => $discountData['amount'] ?? 0,
            'tax_rate' => $addressData['tax_rate'] ?? 0,
            'shipping_cost' => $shippingData['shipping_cost'] ?? 0,
            'packing_cost' => 0, // Packing removed
            'courier_fee' => $shippingData['courier_fee'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'totals' => $totals,
        ]);
    }

    // =========================================================================
    // STEP 4: RETURN (SUCCESS/FAILURE)
    // =========================================================================

    /**
     * GET /branch/{branchId}/checkout/return/{status?}
     * Show purchase result
     */
    public function showReturn(int $branchId, ?string $status = null, Request $request = null): View|JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $request = $request ?? request();
        $status = $status ?? $request->query('status', 'success');
        $purchase = $this->sessionManager->getTempPurchase();
        $cart = $this->sessionManager->getTempCart();

        // Check if there are more branches to checkout
        $hasMoreBranches = $this->cartService->hasOtherBranches($branchId);

        if (!$purchase && $status === 'success') {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'no_purchase',
                    'message' => __('No purchase found'),
                ], 404);
            }
            return redirect()->route('merchant-cart.index');
        }

        // Get error message from session if exists
        $errorMessage = session('unsuccess');

        // Clear temp data after displaying success
        if ($status === 'success' && $purchase) {
            $this->sessionManager->clearTempPurchase();
            $this->sessionManager->clearTempCart();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $status === 'success',
                'status' => $status,
                'purchase' => $purchase,
                'has_more_branches' => $hasMoreBranches,
            ]);
        }

        return view('merchant.checkout.return', [
            'branch_id' => $branchId,
            'status' => $status,
            'purchase' => $purchase,
            'has_more_branches' => $hasMoreBranches,
            'error_message' => $errorMessage,
            'gs' => \DB::table('muaadhsettings')->first(),
        ]);
    }

    // =========================================================================
    // DISCOUNT CODE
    // =========================================================================

    /**
     * POST /branch/{branchId}/checkout/discount/apply
     * Apply discount code
     */
    public function applyDiscount(Request $request, int $branchId): JsonResponse
    {
        $code = $request->input('code');

        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => __('Please enter a discount code'),
            ], 400);
        }

        // TODO: Implement discount code validation logic

        return response()->json([
            'success' => false,
            'message' => __('Invalid discount code'),
        ], 400);
    }

    /**
     * DELETE /branch/{branchId}/checkout/discount
     * Remove discount code
     */
    public function removeDiscount(int $branchId): JsonResponse
    {
        $this->sessionManager->clearDiscountData($branchId);

        return response()->json([
            'success' => true,
            'message' => __('Discount code removed'),
        ]);
    }

    // =========================================================================
    // UTILITIES
    // =========================================================================

    /**
     * GET /branch/{branchId}/checkout/status
     * Get current checkout status
     */
    public function getStatus(int $branchId): JsonResponse
    {
        $currentStep = $this->sessionManager->getCurrentStep($branchId);
        $allData = $this->sessionManager->getAllCheckoutData($branchId);
        $cartSummary = $this->cartService->getBranchCartSummary($branchId);

        return response()->json([
            'success' => true,
            'branch_id' => $branchId,
            'current_step' => $currentStep,
            'steps_completed' => [
                'address' => $this->sessionManager->isStepCompleted($branchId, 'address'),
                'shipping' => $this->sessionManager->isStepCompleted($branchId, 'shipping'),
                'payment' => $this->sessionManager->isStepCompleted($branchId, 'payment'),
            ],
            'cart' => [
                'items_count' => $cartSummary['items_count'],
                'total_qty' => $cartSummary['total_qty'],
                'total_price' => $cartSummary['total_price'],
            ],
            'data' => $allData,
        ]);
    }

    /**
     * DELETE /branch/{branchId}/checkout
     * Cancel checkout and clear session
     */
    public function cancelCheckout(int $branchId): JsonResponse
    {
        $this->sessionManager->clearAllCheckoutData($branchId);

        return response()->json([
            'success' => true,
            'message' => __('Checkout cancelled'),
            'redirect' => route('merchant-cart.index'),
        ]);
    }

    /**
     * POST /branch/{branchId}/checkout/location-draft
     * Save location draft from map
     */
    public function saveLocationDraft(Request $request, int $branchId): JsonResponse
    {
        $validated = $request->validate([
            'address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'city' => 'nullable|string',
            'city_id' => 'nullable|integer',
            'country_id' => 'nullable|integer',
        ]);

        $this->sessionManager->saveLocationDraft($branchId, $validated);

        return response()->json([
            'success' => true,
            'message' => __('Location saved'),
        ]);
    }

    /**
     * POST /branch/{branchId}/checkout/preview-totals
     * Calculate totals preview without saving to session (AJAX)
     */
    public function previewTotals(Request $request, int $branchId): JsonResponse
    {
        $priceCalculator = $this->checkoutService->getPriceCalculator();
        $cartService = $this->checkoutService->getCartService();

        // Get cart and session data
        $cartSummary = $cartService->getBranchCartSummary($branchId);
        $addressData = $this->sessionManager->getAddressData($branchId);
        $discountData = $this->sessionManager->getDiscountData($branchId);

        // Get delivery costs from request
        $deliveryType = $request->input('delivery_type', 'shipping');
        $shippingCost = (float) $request->input('shipping_cost', 0);
        $courierFee = (float) $request->input('courier_fee', 0);
        $packingCost = 0; // Packing removed

        // Calculate totals
        $totals = $priceCalculator->calculateTotals($cartSummary['items'], [
            'discount_amount' => $discountData['amount'] ?? 0,
            'tax_rate' => $addressData['tax_rate'] ?? 0,
            'shipping_cost' => $deliveryType === 'shipping' ? $shippingCost : 0,
            'packing_cost' => $packingCost,
            'courier_fee' => $deliveryType === 'local_courier' ? $courierFee : 0,
        ]);

        $curr = $priceCalculator->getMonetaryUnit();

        return response()->json([
            'success' => true,
            'totals' => $totals,
            'formatted' => [
                'subtotal' => $curr->sign . number_format($totals['subtotal'], 2),
                'shipping_cost' => $curr->sign . number_format($totals['shipping_cost'], 2),
                'courier_fee' => $curr->sign . number_format($totals['courier_fee'], 2),
                'packing_cost' => $curr->sign . number_format($totals['packing_cost'], 2),
                'tax_amount' => $curr->sign . number_format($totals['tax_amount'], 2),
                'grand_total' => $curr->sign . number_format($totals['grand_total'], 2),
            ],
        ]);
    }

    /**
     * POST /branch/{branchId}/checkout/calculate-tax
     * Calculate tax for location (AJAX)
     */
    public function calculateTax(Request $request, int $branchId): JsonResponse
    {
        $countryId = (int) $request->input('country_id', 0);
        $stateId = (int) $request->input('state_id', 0);

        $priceCalculator = $this->checkoutService->getPriceCalculator();
        $cartService = $this->checkoutService->getCartService();

        // Get tax rate for location
        $taxInfo = $priceCalculator->getTaxRateForLocation($countryId, $stateId);

        // Get cart total
        $cartSummary = $cartService->getBranchCartSummary($branchId);
        $subtotal = $cartSummary['total_price'];

        // Calculate tax amount
        $taxAmount = $priceCalculator->calculateTax($subtotal, $taxInfo['tax_rate']);

        // Calculate total
        $total = $subtotal + $taxAmount;

        $curr = $priceCalculator->getMonetaryUnit();

        return response()->json([
            'success' => true,
            'subtotal' => round($subtotal, 2),
            'tax_rate' => $taxInfo['tax_rate'],
            'tax_amount' => round($taxAmount, 2),
            'total' => round($total, 2),
            'formatted' => [
                'subtotal' => $curr->sign . number_format($subtotal, 2),
                'tax_amount' => $curr->sign . number_format($taxAmount, 2),
                'total' => $curr->sign . number_format($total, 2),
            ],
        ]);
    }
}
