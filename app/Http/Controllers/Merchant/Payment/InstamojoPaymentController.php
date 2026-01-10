<?php

namespace App\Http\Controllers\Merchant\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Classes\Instamojo;

/**
 * Instamojo Payment Controller (INR Only)
 */
class InstamojoPaymentController extends BaseMerchantPaymentController
{
    protected string $paymentKeyword = 'instamojo';
    protected string $paymentMethod = 'Instamojo';

    /**
     * POST /merchant/{merchantId}/checkout/payment/instamojo
     */
    public function processPayment(Request $request, int $merchantId): JsonResponse|RedirectResponse
    {
        $validation = $this->validateCheckoutReady($merchantId);
        if (!$validation['valid']) {
            return $request->wantsJson() ? response()->json($validation, 400) : redirect($validation['redirect']);
        }

        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return $this->handlePaymentError($merchantId, __('Instamojo is not available for this merchant'));
        }

        // Instamojo requires INR
        $currency = $this->priceCalculator->getCurrency();
        if ($currency->name !== 'INR') {
            return $this->handlePaymentError($merchantId, __('Instamojo only supports INR currency'));
        }

        $checkoutData = $this->getCheckoutData($merchantId);

        $this->storeInputForCallback($merchantId, [
            'merchant_id' => $merchantId,
            'pay_id' => $config['id'],
        ]);

        try {
            $credentials = $config['credentials'];
            $sandboxMode = ($credentials['sandbox_check'] ?? 0) == 1;

            if ($sandboxMode) {
                $api = new Instamojo($credentials['key'], $credentials['token'], 'https://test.instamojo.com/api/1.1/');
            } else {
                $api = new Instamojo($credentials['key'], $credentials['token']);
            }

            $response = $api->paymentRequestCreate([
                'purpose' => __('Purchase from :merchant', ['merchant' => $checkoutData['cart']['merchant']['name'] ?? 'Merchant']),
                'amount' => round($checkoutData['totals']['grand_total'] / $currency->value, 2),
                'send_email' => true,
                'email' => $checkoutData['address']['customer_email'],
                'redirect_url' => route('merchant.payment.instamojo.callback') . '?merchant_id=' . $merchantId,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'redirect' => $response['longurl'],
                ]);
            }

            return redirect($response['longurl']);

        } catch (\Exception $e) {
            return $this->handlePaymentError($merchantId, $e->getMessage());
        }
    }

    /**
     * GET /merchant/payment/instamojo/callback
     */
    public function handleCallback(Request $request): RedirectResponse
    {
        $merchantId = (int)$request->query('merchant_id');
        $paymentId = $request->query('payment_id');
        $paymentStatus = $request->query('payment_status');
        $paymentRequestId = $request->query('payment_request_id');

        if (!$merchantId) {
            return redirect(route('front.cart'))->with('unsuccess', __('Invalid payment response'));
        }

        $storedInput = $this->getStoredInput($merchantId);
        if (!$storedInput) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment session expired'));
        }

        if ($paymentStatus === 'Failed') {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment was declined'));
        }

        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment configuration not found'));
        }

        try {
            $result = $this->purchaseCreator->createPurchase($merchantId, [
                'method' => $this->paymentMethod,
                'pay_id' => $config['id'],
                'txnid' => $paymentId,
                'payment_status' => 'Completed',
            ]);

            $this->clearStoredInput($merchantId);

            if (!$result['success']) {
                return redirect($this->getFailureUrl($merchantId))->with('unsuccess', $result['message']);
            }

            return redirect($this->getSuccessUrl($merchantId))->with('success', __('Payment successful!'));

        } catch (\Exception $e) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', $e->getMessage());
        }
    }

    /**
     * GET /checkout/payment/instamojo-notify
     * Legacy notify endpoint - redirects to handleCallback
     */
    public function notify(Request $request): RedirectResponse
    {
        return $this->handleCallback($request);
    }
}
