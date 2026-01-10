<?php

namespace App\Http\Controllers\Merchant\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Omnipay\Omnipay;

/**
 * PayPal Payment Controller
 */
class PaypalPaymentController extends BaseMerchantPaymentController
{
    protected string $paymentKeyword = 'paypal';
    protected string $paymentMethod = 'PayPal';

    /**
     * POST /merchant/{merchantId}/checkout/payment/paypal
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
            return $this->handlePaymentError($merchantId, __('PayPal is not available for this merchant'));
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
            // Initialize PayPal gateway
            $gateway = Omnipay::create('PayPal_Rest');
            $gateway->setClientId($config['credentials']['client_id'] ?? '');
            $gateway->setSecret($config['credentials']['client_secret'] ?? '');
            $gateway->setTestMode(($config['credentials']['sandbox'] ?? false) == 1);

            // Create purchase request
            $response = $gateway->purchase([
                'amount' => number_format($checkoutData['totals']['grand_total'], 2, '.', ''),
                'currency' => $currency->name,
                'description' => __('Purchase from :merchant', ['merchant' => $checkoutData['cart']['merchant']['name'] ?? 'Merchant']),
                'returnUrl' => route('merchant.payment.paypal.callback') . '?merchant_id=' . $merchantId,
                'cancelUrl' => $this->getCancelUrl($merchantId),
            ])->send();

            if ($response->isRedirect()) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'redirect' => $response->getRedirectUrl(),
                    ]);
                }
                return redirect($response->getRedirectUrl());
            }

            return $this->handlePaymentError($merchantId, $response->getMessage() ?? __('Failed to initiate PayPal payment'));

        } catch (\Exception $e) {
            return $this->handlePaymentError($merchantId, $e->getMessage());
        }
    }

    /**
     * GET /merchant/payment/paypal/callback
     */
    public function handleCallback(Request $request): RedirectResponse
    {
        $merchantId = (int)$request->query('merchant_id');
        $payerId = $request->query('PayerID');
        $paymentId = $request->query('paymentId');

        if (!$merchantId || !$payerId) {
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
            // Initialize PayPal gateway
            $gateway = Omnipay::create('PayPal_Rest');
            $gateway->setClientId($config['credentials']['client_id'] ?? '');
            $gateway->setSecret($config['credentials']['client_secret'] ?? '');
            $gateway->setTestMode(($config['credentials']['sandbox'] ?? false) == 1);

            // Complete the purchase
            $response = $gateway->completePurchase([
                'payer_id' => $payerId,
                'transactionReference' => $paymentId,
            ])->send();

            if (!$response->isSuccessful()) {
                return redirect($this->getFailureUrl($merchantId))
                    ->with('unsuccess', $response->getMessage() ?? __('Payment verification failed'));
            }

            // Create purchase
            $result = $this->purchaseCreator->createPurchase($merchantId, [
                'method' => $this->paymentMethod,
                'pay_id' => $config['id'],
                'txnid' => $response->getTransactionReference(),
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
     * GET /checkout/payment/paypal-notify
     * Legacy notify endpoint - redirects to handleCallback
     */
    public function notify(Request $request): RedirectResponse
    {
        return $this->handleCallback($request);
    }
}
