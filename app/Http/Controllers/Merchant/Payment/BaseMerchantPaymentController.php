<?php

namespace App\Http\Controllers\Merchant\Payment;

use App\Http\Controllers\Controller;
use App\Services\MerchantCheckout\MerchantCheckoutService;
use App\Services\MerchantCheckout\MerchantPurchaseCreator;
use App\Services\MerchantCheckout\MerchantSessionManager;
use App\Services\Cart\MerchantCartManager;
use App\Services\MerchantCheckout\MerchantPriceCalculator;
use App\Models\MerchantPayment;
use App\Models\MerchantCredential;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Session;

/**
 * Base Merchant Payment Controller
 *
 * Common functionality for all payment gateways
 */
abstract class BaseMerchantPaymentController extends Controller
{
    protected MerchantCheckoutService $checkoutService;
    protected MerchantPurchaseCreator $purchaseCreator;
    protected MerchantSessionManager $sessionManager;
    protected MerchantCartManager $cartService;
    protected MerchantPriceCalculator $priceCalculator;

    protected string $paymentKeyword = '';
    protected string $paymentMethod = '';

    public function __construct(
        MerchantCheckoutService $checkoutService,
        MerchantPurchaseCreator $purchaseCreator,
        MerchantSessionManager $sessionManager,
        MerchantCartManager $cartService,
        MerchantPriceCalculator $priceCalculator
    ) {
        $this->checkoutService = $checkoutService;
        $this->purchaseCreator = $purchaseCreator;
        $this->sessionManager = $sessionManager;
        $this->cartService = $cartService;
        $this->priceCalculator = $priceCalculator;

        $this->middleware('auth');
    }

    /**
     * Get payment gateway configuration
     * Uses merchant_credentials table for API keys (secure storage)
     * Uses merchant_payments table only to check if method is enabled
     */
    protected function getPaymentConfig(int $merchantId): ?array
    {
        // Check if payment method is enabled for this merchant
        $payment = MerchantPayment::where('keyword', $this->paymentKeyword)
            ->where('user_id', $merchantId)
            ->where('checkout', 1)
            ->first();

        if (!$payment) {
            return null;
        }

        // Get credentials from merchant_credentials table (secure storage)
        $credentials = $this->getMerchantCredentials($merchantId, $this->paymentKeyword);

        if (empty($credentials) || empty($credentials['api_key'])) {
            return null; // No valid credentials configured
        }

        return [
            'id' => $payment->id,
            'keyword' => $payment->keyword,
            'name' => $payment->name ?? $payment->name,
            'credentials' => $credentials,
        ];
    }

    /**
     * Get merchant credentials from merchant_credentials table
     * All API keys/secrets are stored encrypted in this table
     */
    protected function getMerchantCredentials(int $merchantId, string $serviceName): array
    {
        $credentials = MerchantCredential::where('user_id', $merchantId)
            ->where('service_name', $serviceName)
            ->where('is_active', true)
            ->get();

        if ($credentials->isEmpty()) {
            return [];
        }

        $result = [];
        $environment = 'live';

        foreach ($credentials as $cred) {
            $result[$cred->key_name] = $cred->decrypted_value;
            $environment = $cred->environment;
        }

        $result['sandbox'] = $environment === 'sandbox' ? 1 : 0;
        $result['environment'] = $environment;

        return $result;
    }

    /**
     * Validate checkout is ready for payment
     */
    protected function validateCheckoutReady(int $merchantId): array
    {
        $addressData = $this->sessionManager->getAddressData($merchantId);
        $shippingData = $this->sessionManager->getShippingData($merchantId);

        if (!$addressData) {
            return [
                'valid' => false,
                'error' => 'address_required',
                'message' => __('Please complete address step first'),
                'redirect' => route('merchant.checkout.address', $merchantId),
            ];
        }

        if (!$shippingData) {
            return [
                'valid' => false,
                'error' => 'shipping_required',
                'message' => __('Please select shipping method first'),
                'redirect' => route('merchant.checkout.shipping', $merchantId),
            ];
        }

        if (!$this->cartService->hasMerchantItems($merchantId)) {
            return [
                'valid' => false,
                'error' => 'empty_cart',
                'message' => __('No items in cart for this merchant'),
                'redirect' => route('front.cart'),
            ];
        }

        return ['valid' => true];
    }

    /**
     * Get checkout data for payment processing
     */
    protected function getCheckoutData(int $merchantId): array
    {
        $addressData = $this->sessionManager->getAddressData($merchantId);
        $shippingData = $this->sessionManager->getShippingData($merchantId);
        $discountData = $this->sessionManager->getDiscountData($merchantId);
        $cartSummary = $this->cartService->getMerchantCartSummary($merchantId);

        $totals = $this->priceCalculator->calculateTotals($cartSummary['items'], [
            'discount_amount' => $discountData['amount'] ?? 0,
            'tax_rate' => $addressData['tax_rate'] ?? 0,
            'shipping_cost' => $shippingData['shipping_cost'] ?? 0,
            'packing_cost' => $shippingData['packing_cost'] ?? 0,
            'courier_fee' => $shippingData['courier_fee'] ?? 0,
        ]);

        return [
            'merchant_id' => $merchantId,
            'address' => $addressData,
            'shipping' => $shippingData,
            'discount' => $discountData,
            'cart' => $cartSummary,
            'totals' => $totals,
        ];
    }

    /**
     * Store input data in session for callback
     */
    protected function storeInputForCallback(int $merchantId, array $data): void
    {
        Session::put('merchant_payment_input_' . $merchantId, $data);
        Session::save();
    }

    /**
     * Get stored input data from callback
     */
    protected function getStoredInput(int $merchantId): ?array
    {
        return Session::get('merchant_payment_input_' . $merchantId);
    }

    /**
     * Clear stored input data
     */
    protected function clearStoredInput(int $merchantId): void
    {
        Session::forget('merchant_payment_input_' . $merchantId);
        Session::save();
    }

    /**
     * Create purchase after successful payment
     */
    protected function createSuccessfulPurchase(int $merchantId, array $paymentData): array
    {
        return $this->purchaseCreator->createPurchase($merchantId, array_merge($paymentData, [
            'method' => $this->paymentMethod,
            'payment_status' => 'Completed',
        ]));
    }

    /**
     * Get success redirect URL
     * Always shows success page - the page will display "Continue to Other Items" button if needed
     */
    protected function getSuccessUrl(int $merchantId): string
    {
        return route('merchant.checkout.return', ['merchantId' => $merchantId, 'status' => 'success']);
    }

    /**
     * Get cancel redirect URL
     */
    protected function getCancelUrl(int $merchantId): string
    {
        return route('merchant.checkout.return', ['merchantId' => $merchantId, 'status' => 'cancelled']);
    }

    /**
     * Get failure redirect URL
     */
    protected function getFailureUrl(int $merchantId): string
    {
        return route('merchant.checkout.return', ['merchantId' => $merchantId, 'status' => 'failed']);
    }

    /**
     * Handle payment error response
     */
    protected function handlePaymentError(int $merchantId, string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'payment_failed',
            'message' => $message,
            'redirect' => $this->getFailureUrl($merchantId),
        ], 400);
    }

    /**
     * Handle successful payment response
     */
    protected function handlePaymentSuccess(int $merchantId, Purchase $purchase): JsonResponse
    {
        return response()->json([
            'success' => true,
            'purchase_number' => $purchase->purchase_number,
            'redirect' => $this->getSuccessUrl($merchantId),
        ]);
    }

    /**
     * Abstract method - process payment (must be implemented by each gateway)
     */
    abstract public function processPayment(Request $request, int $merchantId);

    /**
     * Abstract method - handle payment callback/notify (must be implemented by each gateway)
     */
    abstract public function handleCallback(Request $request);
}
