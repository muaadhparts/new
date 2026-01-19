<?php

namespace App\Http\Controllers\Merchant\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

/**
 * Stripe Payment Controller
 *
 * NOTE: Routes use branchId, but payment methods are merchant-scoped.
 */
class StripePaymentController extends BaseMerchantPaymentController
{
    protected string $paymentKeyword = 'stripe';
    protected string $paymentMethod = 'Stripe';

    /**
     * POST /branch/{branchId}/checkout/payment/stripe
     */
    public function processPayment(Request $request, int $branchId): JsonResponse|RedirectResponse
    {
        // Validate checkout is ready (branch-scoped)
        $validation = $this->validateCheckoutReady($branchId);
        if (!$validation['valid']) {
            if ($request->wantsJson()) {
                return response()->json($validation, 400);
            }
            return redirect($validation['redirect'])->with('unsuccess', $validation['message']);
        }

        // Get merchantId from branch (payment methods are merchant-scoped)
        $merchantId = $this->getMerchantIdFromBranch($branchId);
        if (!$merchantId) {
            return $this->handlePaymentError($branchId, __('Invalid branch'));
        }

        // Get payment config (merchant-scoped)
        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return $this->handlePaymentError($branchId, __('Stripe is not available for this merchant'));
        }

        // Get checkout data (branch-scoped)
        $checkoutData = $this->getCheckoutData($branchId);
        $currency = $this->priceCalculator->getMonetaryUnit();

        // Store checkout data for callback (branch-scoped)
        $this->storeInputForCallback($branchId, [
            'branch_id' => $branchId,
            'merchant_id' => $merchantId,
            'pay_id' => $config['id'],
        ]);

        try {
            // Initialize Stripe
            Stripe::setApiKey($config['credentials']['secret'] ?? '');

            // Create line items for Stripe
            $lineItems = [];
            foreach ($checkoutData['cart']['items'] as $item) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => strtolower($currency->name),
                        'product_data' => [
                            'name' => $item['item']['name'] ?? 'Product',
                        ],
                        'unit_amount' => (int)(($item['item_price'] ?? $item['price'] / $item['qty']) * 100),
                    ],
                    'quantity' => $item['qty'],
                ];
            }

            // Add shipping if applicable
            if ($checkoutData['totals']['shipping_cost'] > 0) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => strtolower($currency->name),
                        'product_data' => [
                            'name' => __('Shipping'),
                        ],
                        'unit_amount' => (int)($checkoutData['totals']['shipping_cost'] * 100),
                    ],
                    'quantity' => 1,
                ];
            }

            // Add tax if applicable
            if ($checkoutData['totals']['tax_amount'] > 0) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => strtolower($currency->name),
                        'product_data' => [
                            'name' => __('Tax'),
                        ],
                        'unit_amount' => (int)($checkoutData['totals']['tax_amount'] * 100),
                    ],
                    'quantity' => 1,
                ];
            }

            // Create Stripe checkout session
            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => route('branch.payment.stripe.callback') . '?session_id={CHECKOUT_SESSION_ID}&branch_id=' . $branchId,
                'cancel_url' => $this->getCancelUrl($branchId),
                'metadata' => [
                    'branch_id' => $branchId,
                    'merchant_id' => $merchantId,
                ],
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'redirect' => $session->url,
                    'session_id' => $session->id,
                ]);
            }

            return redirect($session->url);

        } catch (\Exception $e) {
            return $this->handlePaymentError($branchId, $e->getMessage());
        }
    }

    /**
     * GET /branch/payment/stripe/callback
     */
    public function handleCallback(Request $request): RedirectResponse
    {
        $sessionId = $request->query('session_id');
        $branchId = (int)$request->query('branch_id');

        if (!$sessionId || !$branchId) {
            return redirect(route('merchant-cart.index'))
                ->with('unsuccess', __('Invalid payment response'));
        }

        // Get stored input (branch-scoped)
        $storedInput = $this->getStoredInput($branchId);
        if (!$storedInput) {
            return redirect($this->getFailureUrl($branchId))
                ->with('unsuccess', __('Payment session expired'));
        }

        // Get merchantId from stored session
        $merchantId = $storedInput['merchant_id'] ?? $this->getMerchantIdFromBranch($branchId);
        if (!$merchantId) {
            return redirect($this->getFailureUrl($branchId))
                ->with('unsuccess', __('Invalid branch'));
        }

        // Get payment config (merchant-scoped)
        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return redirect($this->getFailureUrl($branchId))
                ->with('unsuccess', __('Payment configuration not found'));
        }

        try {
            Stripe::setApiKey($config['credentials']['secret'] ?? '');

            // Retrieve session to verify payment
            $session = StripeSession::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return redirect($this->getFailureUrl($branchId))
                    ->with('unsuccess', __('Payment was not completed'));
            }

            // Create purchase (branch-scoped)
            $result = $this->purchaseCreator->createPurchase($branchId, [
                'method' => $this->paymentMethod,
                'pay_id' => $config['id'],
                'txnid' => $session->payment_intent,
                'charge_id' => $session->id,
                'payment_status' => 'Completed',
            ]);

            // Clear stored input
            $this->clearStoredInput($branchId);

            if (!$result['success']) {
                return redirect($this->getFailureUrl($branchId))
                    ->with('unsuccess', $result['message'] ?? __('Failed to create order'));
            }

            return redirect($this->getSuccessUrl($branchId))
                ->with('success', __('Payment successful!'));

        } catch (\Exception $e) {
            return redirect($this->getFailureUrl($branchId))
                ->with('unsuccess', $e->getMessage());
        }
    }

    /**
     * GET /payment/stripe/notify
     * Legacy notify endpoint - redirects to handleCallback
     */
    public function notify(Request $request): RedirectResponse
    {
        return $this->handleCallback($request);
    }
}
