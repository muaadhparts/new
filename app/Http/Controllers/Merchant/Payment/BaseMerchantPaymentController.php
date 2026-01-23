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
use App\Models\MerchantBranch;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Session;

/**
 * Base Merchant Payment Controller
 *
 * Common functionality for all payment gateways
 *
 * NOTE: Routes now use branchId, but payment/shipping methods are merchant-scoped.
 * We extract merchantId from branch->user_id for payment configuration.
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
     *
     * OPERATOR PATTERN:
     * - user_id = $merchantId → Merchant's own payment method
     * - user_id = 0 AND operator = $merchantId → Platform-provided for this merchant
     */
    protected function getPaymentConfig(int $merchantId): ?array
    {
        // Check if payment method is enabled for this merchant
        // Priority: 1. Merchant's own method, 2. Platform-provided method
        $payment = MerchantPayment::where('keyword', $this->paymentKeyword)
            ->where('checkout', 1)
            ->where(function ($query) use ($merchantId) {
                // Merchant's own payment method
                $query->where('user_id', $merchantId)
                    // OR Platform-provided method for this merchant
                    ->orWhere(function ($q) use ($merchantId) {
                        $q->where('user_id', 0)
                          ->where('operator', $merchantId);
                    });
            })
            // Prefer merchant's own method over platform-provided
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$merchantId])
            ->first();

        if (!$payment) {
            return null;
        }

        // Determine whose credentials to use
        $isPlatformProvided = (int)$payment->user_id === 0;
        $credentialOwnerId = $isPlatformProvided ? 0 : $merchantId;

        // Get credentials from merchant_credentials table (secure storage)
        $credentials = $this->getMerchantCredentials($credentialOwnerId, $this->paymentKeyword);

        if (empty($credentials) || empty($credentials['api_key'])) {
            // Fallback: Check legacy information field for platform-provided methods
            if ($isPlatformProvided && !empty($payment->information)) {
                $credentials = $this->extractLegacyCredentials($payment->information);
            }

            if (empty($credentials) || empty($credentials['api_key'])) {
                return null; // No valid credentials configured
            }
        }

        return [
            'id' => $payment->id,
            'keyword' => $payment->keyword,
            'name' => $payment->name ?? $this->paymentMethod,
            'credentials' => $credentials,
            'is_platform_provided' => $isPlatformProvided,
            'payment_owner_id' => $credentialOwnerId,
        ];
    }

    /**
     * Extract credentials from legacy information JSON field
     */
    protected function extractLegacyCredentials(?string $information): array
    {
        if (empty($information)) {
            return [];
        }

        $data = json_decode($information, true);
        if (!is_array($data)) {
            return [];
        }

        // Skip placeholder values
        if (isset($data['api_key']) && in_array($data['api_key'], ['<YOUR_API_KEY>', 'YOUR_API_KEY', ''])) {
            $data['api_key'] = null;
        }

        return $data;
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
     * Get merchant ID from branch
     * Payment methods are merchant-scoped, so we need the merchant from the branch
     */
    protected function getMerchantIdFromBranch(int $branchId): ?int
    {
        $branch = MerchantBranch::find($branchId);
        return $branch?->user_id;
    }

    /**
     * Validate checkout is ready for payment (branch-scoped)
     */
    protected function validateCheckoutReady(int $branchId): array
    {
        // Session data is branch-scoped
        $addressData = $this->sessionManager->getAddressData($branchId);
        $shippingData = $this->sessionManager->getShippingData($branchId);

        if (!$addressData) {
            return [
                'valid' => false,
                'error' => 'address_required',
                'message' => __('Please complete address step first'),
                'redirect' => route('branch.checkout.address', $branchId),
            ];
        }

        if (!$shippingData) {
            return [
                'valid' => false,
                'error' => 'shipping_required',
                'message' => __('Please select shipping method first'),
                'redirect' => route('branch.checkout.shipping', $branchId),
            ];
        }

        // Cart is branch-scoped
        if (!$this->cartService->hasBranchItems($branchId)) {
            return [
                'valid' => false,
                'error' => 'empty_cart',
                'message' => __('No items in cart for this branch'),
                'redirect' => route('merchant-cart.index'),
            ];
        }

        return ['valid' => true];
    }

    /**
     * Get checkout data for payment processing (branch-scoped)
     */
    protected function getCheckoutData(int $branchId): array
    {
        // Session data is branch-scoped
        $addressData = $this->sessionManager->getAddressData($branchId);
        $shippingData = $this->sessionManager->getShippingData($branchId);
        $discountData = $this->sessionManager->getDiscountData($branchId);

        // Cart is branch-scoped
        $cartSummary = $this->cartService->getBranchCartSummary($branchId);

        // Get merchantId from branch for reference
        $merchantId = $this->getMerchantIdFromBranch($branchId);

        $totals = $this->priceCalculator->calculateTotals($cartSummary['items'], [
            'discount_amount' => $discountData['amount'] ?? 0,
            'tax_rate' => $addressData['tax_rate'] ?? 0,
            'shipping_cost' => $shippingData['shipping_cost'] ?? 0,
            'courier_fee' => $shippingData['courier_fee'] ?? 0,
        ]);

        return [
            'branch_id' => $branchId,
            'merchant_id' => $merchantId,
            'address' => $addressData,
            'shipping' => $shippingData,
            'discount' => $discountData,
            'cart' => $cartSummary,
            'totals' => $totals,
        ];
    }

    /**
     * Store input data in session for callback (branch-scoped)
     */
    protected function storeInputForCallback(int $branchId, array $data): void
    {
        Session::put('branch_payment_input_' . $branchId, $data);
        Session::save();
    }

    /**
     * Get stored input data from callback (branch-scoped)
     */
    protected function getStoredInput(int $branchId): ?array
    {
        return Session::get('branch_payment_input_' . $branchId);
    }

    /**
     * Clear stored input data (branch-scoped)
     */
    protected function clearStoredInput(int $branchId): void
    {
        Session::forget('branch_payment_input_' . $branchId);
        Session::save();
    }

    /**
     * Create purchase after successful payment (branch-scoped)
     */
    protected function createSuccessfulPurchase(int $branchId, array $paymentData): array
    {
        return $this->purchaseCreator->createPurchase($branchId, array_merge($paymentData, [
            'method' => $this->paymentMethod,
            'payment_status' => 'Completed',
        ]));
    }

    /**
     * Get success redirect URL (branch-scoped)
     */
    protected function getSuccessUrl(int $branchId): string
    {
        return route('branch.checkout.return', ['branchId' => $branchId, 'status' => 'success']);
    }

    /**
     * Get cancel redirect URL (branch-scoped)
     */
    protected function getCancelUrl(int $branchId): string
    {
        return route('branch.checkout.return', ['branchId' => $branchId, 'status' => 'cancelled']);
    }

    /**
     * Get failure redirect URL (branch-scoped)
     */
    protected function getFailureUrl(int $branchId): string
    {
        return route('branch.checkout.return', ['branchId' => $branchId, 'status' => 'failed']);
    }

    /**
     * Handle payment error response (branch-scoped)
     */
    protected function handlePaymentError(int $branchId, string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'payment_failed',
            'message' => $message,
            'redirect' => $this->getFailureUrl($branchId),
        ], 400);
    }

    /**
     * Handle successful payment response (branch-scoped)
     */
    protected function handlePaymentSuccess(int $branchId, Purchase $purchase): JsonResponse
    {
        return response()->json([
            'success' => true,
            'purchase_number' => $purchase->purchase_number,
            'redirect' => $this->getSuccessUrl($branchId),
        ]);
    }

    /**
     * Abstract method - process payment (must be implemented by each gateway)
     * NOTE: Routes pass branchId, extract merchantId using getMerchantIdFromBranch()
     */
    abstract public function processPayment(Request $request, int $branchId);

    /**
     * Abstract method - handle payment callback/notify (must be implemented by each gateway)
     */
    abstract public function handleCallback(Request $request);
}
