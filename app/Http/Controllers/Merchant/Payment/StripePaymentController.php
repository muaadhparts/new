<?php

namespace App\Http\Controllers\Merchant\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

/**
 * Stripe Payment Controller
 */
class StripePaymentController extends BaseMerchantPaymentController
{
    protected string $paymentKeyword = 'stripe';
    protected string $paymentMethod = 'Stripe';

    /**
     * POST /merchant/{merchantId}/checkout/payment/stripe
     */
    public function processPayment(Request $request, int $merchantId): JsonResponse|RedirectResponse
    {
        // Validate checkout is ready
        $validation = $this->validateCheckoutReady($merchantId);
        if (!$validation['valid']) {
            if ($request->wantsJson()) {
                return response()->json($validation, 400);
            }
            return redirect($validation['redirect'])->with('unsuccess', $validation['message']);
        }

        // Get payment config
        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return $this->handlePaymentError($merchantId, __('Stripe is not available for this merchant'));
        }

        // Get checkout data
        $checkoutData = $this->getCheckoutData($merchantId);
        $currency = $this->priceCalculator->getCurrency();

        // Store checkout data for callback
        $this->storeInputForCallback($merchantId, [
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
                'success_url' => route('merchant.payment.stripe.callback') . '?session_id={CHECKOUT_SESSION_ID}&merchant_id=' . $merchantId,
                'cancel_url' => $this->getCancelUrl($merchantId),
                'metadata' => [
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
            return $this->handlePaymentError($merchantId, $e->getMessage());
        }
    }

    /**
     * GET /merchant/payment/stripe/callback
     */
    public function handleCallback(Request $request): RedirectResponse
    {
        $sessionId = $request->query('session_id');
        $merchantId = (int)$request->query('merchant_id');

        if (!$sessionId || !$merchantId) {
            return redirect($this->getFailureUrl($merchantId ?: 0))
                ->with('unsuccess', __('Invalid payment response'));
        }

        // Get stored input
        $storedInput = $this->getStoredInput($merchantId);
        if (!$storedInput) {
            return redirect($this->getFailureUrl($merchantId))
                ->with('unsuccess', __('Payment session expired'));
        }

        // Get payment config
        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return redirect($this->getFailureUrl($merchantId))
                ->with('unsuccess', __('Payment configuration not found'));
        }

        try {
            Stripe::setApiKey($config['credentials']['secret'] ?? '');

            // Retrieve session to verify payment
            $session = StripeSession::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return redirect($this->getFailureUrl($merchantId))
                    ->with('unsuccess', __('Payment was not completed'));
            }

            // Create purchase
            $result = $this->purchaseCreator->createPurchase($merchantId, [
                'method' => $this->paymentMethod,
                'pay_id' => $config['id'],
                'txnid' => $session->payment_intent,
                'charge_id' => $session->id,
                'payment_status' => 'Completed',
            ]);

            // Clear stored input
            $this->clearStoredInput($merchantId);

            if (!$result['success']) {
                return redirect($this->getFailureUrl($merchantId))
                    ->with('unsuccess', $result['message'] ?? __('Failed to create order'));
            }

            return redirect($this->getSuccessUrl($merchantId))
                ->with('success', __('Payment successful!'));

        } catch (\Exception $e) {
            return redirect($this->getFailureUrl($merchantId))
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
