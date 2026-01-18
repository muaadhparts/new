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
 * Merchant Checkout Controller - API First
 *
 * Clean checkout flow for merchant-only system
 * All methods return JSON for API or render views
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
     * GET /merchant/{merchantId}/checkout/address
     * Display address form
     */
    public function showAddress(int $merchantId): View|JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $result = $this->checkoutService->initializeAddressStep($merchantId);

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
            'merchant_id' => $merchantId,
            'address' => $address,
            'cart' => $result['data']['cart'] ?? [],
            'googleMapsApiKey' => $googleMapsApiKey,
            'curr' => $curr,
            'gs' => \DB::table('muaadhsettings')->first(),
        ]);
    }

    /**
     * GET /merchant/{merchantId}/checkout/address/api
     * API endpoint for address step data
     */
    public function getAddressData(int $merchantId): JsonResponse
    {
        $result = $this->checkoutService->initializeAddressStep($merchantId);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * POST /merchant/{merchantId}/checkout/address
     * Process address submission
     */
    public function processAddress(Request $request, int $merchantId): JsonResponse
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

        $result = $this->checkoutService->processAddressStep($merchantId, $validated);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    // =========================================================================
    // STEP 2: SHIPPING
    // =========================================================================

    /**
     * GET /merchant/{merchantId}/checkout/shipping
     * Display shipping options
     */
    public function showShipping(int $merchantId): View|JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $result = $this->checkoutService->initializeShippingStep($merchantId);

        if (!$result['success']) {
            if (request()->wantsJson()) {
                return response()->json($result, 400);
            }
            $redirectUrl = $result['redirect'] ?? route('merchant.checkout.address', $merchantId);
            return redirect()->to($redirectUrl)
                ->with('unsuccess', $result['message'] ?? __('Please complete previous step'));
        }

        if (request()->wantsJson()) {
            return response()->json($result);
        }

        // Get currency
        $curr = $this->checkoutService->getPriceCalculator()->getMonetaryUnit();

        return view('merchant.checkout.shipping', [
            'merchant_id' => $merchantId,
            'merchant' => $result['data']['merchant'] ?? [],
            'address' => $result['data']['address'] ?? [],
            'cart' => $result['data']['cart'] ?? [],
            'totals' => $result['data']['totals'] ?? [],
            'shipping_providers' => $result['data']['shipping_options'] ?? [], // Now grouped by provider
            'packaging' => $result['data']['packaging_options'] ?? [],
            'couriers' => $result['data']['courier_options'] ?? [],
            'curr' => $curr,
            'gs' => \DB::table('muaadhsettings')->first(),
        ]);
    }

    /**
     * GET /merchant/{merchantId}/checkout/shipping/api
     * API endpoint for shipping step data
     */
    public function getShippingData(int $merchantId): JsonResponse
    {
        $result = $this->checkoutService->initializeShippingStep($merchantId);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * POST /merchant/{merchantId}/checkout/shipping
     * Process shipping selection
     */
    public function processShipping(Request $request, int $merchantId): JsonResponse
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
            // Packing fields
            'packing_id' => 'nullable|integer',
            'packing_name' => 'nullable|string',
            'packing_cost' => 'nullable|numeric',
        ]);

        $result = $this->checkoutService->processShippingStep($merchantId, $validated);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * POST /merchant/{merchantId}/checkout/shipping/calculate
     * Calculate shipping cost (AJAX)
     */
    public function calculateShipping(Request $request, int $merchantId): JsonResponse
    {
        $priceCalculator = $this->checkoutService->getPriceCalculator();
        $cartService = $this->checkoutService->getCartService();

        $cartSummary = $cartService->getMerchantCartSummary($merchantId);
        $itemsTotal = $cartSummary['total_price'];

        $deliveryType = $request->input('delivery_type', 'shipping');

        if ($deliveryType === 'local_courier') {
            $addressData = $this->sessionManager->getAddressData($merchantId);
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
     * POST /merchant/{merchantId}/checkout/delivery-options
     * Get available delivery options for the merchant
     */
    public function getDeliveryOptions(Request $request, int $merchantId): JsonResponse
    {
        $result = $this->checkoutService->initializeShippingStep($merchantId);

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
     * GET /merchant/{merchantId}/checkout/payment
     * Display payment methods
     */
    public function showPayment(int $merchantId): View|JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $result = $this->checkoutService->initializePaymentStep($merchantId);

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
            'merchant_id' => $merchantId,
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
     * GET /merchant/{merchantId}/checkout/payment/api
     * API endpoint for payment step data
     */
    public function getPaymentData(int $merchantId): JsonResponse
    {
        $result = $this->checkoutService->initializePaymentStep($merchantId);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * GET /merchant/{merchantId}/checkout/totals
     * Get current totals (AJAX)
     */
    public function getTotals(int $merchantId): JsonResponse
    {
        $priceCalculator = $this->checkoutService->getPriceCalculator();
        $cartService = $this->checkoutService->getCartService();

        $addressData = $this->sessionManager->getAddressData($merchantId);
        $shippingData = $this->sessionManager->getShippingData($merchantId);
        $discountData = $this->sessionManager->getDiscountData($merchantId);
        $cartSummary = $cartService->getMerchantCartSummary($merchantId);

        $totals = $priceCalculator->calculateTotals($cartSummary['items'], [
            'discount_amount' => $discountData['amount'] ?? 0,
            'tax_rate' => $addressData['tax_rate'] ?? 0,
            'shipping_cost' => $shippingData['shipping_cost'] ?? 0,
            'packing_cost' => $shippingData['packing_cost'] ?? 0,
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
     * GET /merchant/{merchantId}/checkout/return/{status?}
     * Show purchase result
     */
    public function showReturn(int $merchantId, ?string $status = null, Request $request = null): View|JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $request = $request ?? request();
        $status = $status ?? $request->query('status', 'success');
        $purchase = $this->sessionManager->getTempPurchase();
        $cart = $this->sessionManager->getTempCart();

        // Check if there are more merchants to checkout
        $hasMoreMerchants = $this->cartService->hasOtherMerchants($merchantId);

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
                'has_more_merchants' => $hasMoreMerchants,
            ]);
        }

        return view('merchant.checkout.return', [
            'merchant_id' => $merchantId,
            'status' => $status,
            'purchase' => $purchase,
            'has_more_merchants' => $hasMoreMerchants,
            'error_message' => $errorMessage,
            'gs' => \DB::table('muaadhsettings')->first(),
        ]);
    }

    // =========================================================================
    // DISCOUNT CODE
    // =========================================================================

    /**
     * POST /merchant/{merchantId}/checkout/discount/apply
     * Apply discount code
     */
    public function applyDiscount(Request $request, int $merchantId): JsonResponse
    {
        $code = $request->input('code');

        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => __('Please enter a discount code'),
            ], 400);
        }

        // TODO: Implement discount code validation logic
        // This should check the discount_codes table and validate:
        // - Code exists and is active
        // - Code is valid for this merchant
        // - Code hasn't expired
        // - Usage limit hasn't been reached
        // - Minimum purchase amount met

        return response()->json([
            'success' => false,
            'message' => __('Invalid discount code'),
        ], 400);
    }

    /**
     * DELETE /merchant/{merchantId}/checkout/discount
     * Remove discount code
     */
    public function removeDiscount(int $merchantId): JsonResponse
    {
        $this->sessionManager->clearDiscountData($merchantId);

        return response()->json([
            'success' => true,
            'message' => __('Discount code removed'),
        ]);
    }

    // =========================================================================
    // UTILITIES
    // =========================================================================

    /**
     * GET /merchant/{merchantId}/checkout/status
     * Get current checkout status
     */
    public function getStatus(int $merchantId): JsonResponse
    {
        $currentStep = $this->sessionManager->getCurrentStep($merchantId);
        $allData = $this->sessionManager->getAllCheckoutData($merchantId);
        $cartSummary = $this->cartService->getMerchantCartSummary($merchantId);

        return response()->json([
            'success' => true,
            'merchant_id' => $merchantId,
            'current_step' => $currentStep,
            'steps_completed' => [
                'address' => $this->sessionManager->isStepCompleted($merchantId, 'address'),
                'shipping' => $this->sessionManager->isStepCompleted($merchantId, 'shipping'),
                'payment' => $this->sessionManager->isStepCompleted($merchantId, 'payment'),
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
     * DELETE /merchant/{merchantId}/checkout
     * Cancel checkout and clear session
     */
    public function cancelCheckout(int $merchantId): JsonResponse
    {
        $this->sessionManager->clearAllCheckoutData($merchantId);

        return response()->json([
            'success' => true,
            'message' => __('Checkout cancelled'),
            'redirect' => route('merchant-cart.index'),
        ]);
    }

    /**
     * POST /merchant/{merchantId}/checkout/location-draft
     * Save location draft from map
     */
    public function saveLocationDraft(Request $request, int $merchantId): JsonResponse
    {
        $validated = $request->validate([
            'address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'city' => 'nullable|string',
            'city_id' => 'nullable|integer',
            'country_id' => 'nullable|integer',
        ]);

        $this->sessionManager->saveLocationDraft($merchantId, $validated);

        return response()->json([
            'success' => true,
            'message' => __('Location saved'),
        ]);
    }

    /**
     * POST /merchant/{merchantId}/checkout/preview-totals
     * Calculate totals preview without saving to session (AJAX)
     */
    public function previewTotals(Request $request, int $merchantId): JsonResponse
    {
        $priceCalculator = $this->checkoutService->getPriceCalculator();
        $cartService = $this->checkoutService->getCartService();

        // Get cart and session data
        $cartSummary = $cartService->getMerchantCartSummary($merchantId);
        $addressData = $this->sessionManager->getAddressData($merchantId);
        $discountData = $this->sessionManager->getDiscountData($merchantId);

        // Get delivery costs from request
        $deliveryType = $request->input('delivery_type', 'shipping');
        $shippingCost = (float) $request->input('shipping_cost', 0);
        $courierFee = (float) $request->input('courier_fee', 0);
        $packingCost = (float) $request->input('packing_cost', 0);

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
     * POST /merchant/{merchantId}/checkout/calculate-tax
     * Calculate tax for location (AJAX)
     */
    public function calculateTax(Request $request, int $merchantId): JsonResponse
    {
        $countryId = (int) $request->input('country_id', 0);
        $stateId = (int) $request->input('state_id', 0);

        $priceCalculator = $this->checkoutService->getPriceCalculator();
        $cartService = $this->checkoutService->getCartService();

        // Get tax rate for location
        $taxInfo = $priceCalculator->getTaxRateForLocation($countryId, $stateId);

        // Get cart total
        $cartSummary = $cartService->getMerchantCartSummary($merchantId);
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
