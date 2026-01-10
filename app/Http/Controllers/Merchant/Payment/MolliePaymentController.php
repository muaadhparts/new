<?php

namespace App\Http\Controllers\Merchant\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Mollie\Laravel\Facades\Mollie;
use PurchaseHelper;

/**
 * Mollie Payment Controller (Europe)
 */
class MolliePaymentController extends BaseMerchantPaymentController
{
    protected string $paymentKeyword = 'mollie';
    protected string $paymentMethod = 'Mollie';

    /**
     * POST /merchant/{merchantId}/checkout/payment/mollie
     */
    public function processPayment(Request $request, int $merchantId): JsonResponse|RedirectResponse
    {
        $validation = $this->validateCheckoutReady($merchantId);
        if (!$validation['valid']) {
            return $request->wantsJson() ? response()->json($validation, 400) : redirect($validation['redirect']);
        }

        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return $this->handlePaymentError($merchantId, __('Mollie is not available for this merchant'));
        }

        // Validate currency
        $currency = $this->priceCalculator->getCurrency();
        $availableCurrencies = PurchaseHelper::mollie_currencies();
        if (!in_array($currency->name, $availableCurrencies)) {
            return $this->handlePaymentError($merchantId, __('Invalid currency for Mollie payment'));
        }

        $checkoutData = $this->getCheckoutData($merchantId);

        $this->storeInputForCallback($merchantId, [
            'merchant_id' => $merchantId,
            'pay_id' => $config['id'],
        ]);

        try {
            $payment = Mollie::api()->payments()->create([
                'amount' => [
                    'currency' => $currency->name,
                    'value' => sprintf('%0.2f', $checkoutData['totals']['grand_total']),
                ],
                'description' => __('Purchase from :merchant', ['merchant' => $checkoutData['cart']['merchant']['name'] ?? 'Merchant']),
                'redirectUrl' => route('merchant.payment.mollie.callback') . '?merchant_id=' . $merchantId,
                'metadata' => [
                    'merchant_id' => $merchantId,
                ],
            ]);

            // Store payment ID for verification
            session(['merchant_mollie_payment_id_' . $merchantId => $payment->id]);

            $paymentData = Mollie::api()->payments()->get($payment->id);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'redirect' => $paymentData->getCheckoutUrl(),
                ]);
            }

            return redirect($paymentData->getCheckoutUrl(), 303);

        } catch (\Exception $e) {
            return $this->handlePaymentError($merchantId, $e->getMessage());
        }
    }

    /**
     * GET /merchant/payment/mollie/callback
     */
    public function handleCallback(Request $request): RedirectResponse
    {
        $merchantId = (int)$request->query('merchant_id');

        if (!$merchantId) {
            return redirect(route('front.cart'))->with('unsuccess', __('Invalid payment response'));
        }

        $storedInput = $this->getStoredInput($merchantId);
        if (!$storedInput) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment session expired'));
        }

        $paymentId = session('merchant_mollie_payment_id_' . $merchantId);
        if (!$paymentId) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment verification failed'));
        }

        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment configuration not found'));
        }

        try {
            $payment = Mollie::api()->payments()->get($paymentId);

            if ($payment->status !== 'paid') {
                return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment was not completed'));
            }

            $result = $this->purchaseCreator->createPurchase($merchantId, [
                'method' => $this->paymentMethod,
                'pay_id' => $config['id'],
                'txnid' => $paymentId,
                'payment_status' => 'Completed',
            ]);

            $this->clearStoredInput($merchantId);
            session()->forget('merchant_mollie_payment_id_' . $merchantId);

            if (!$result['success']) {
                return redirect($this->getFailureUrl($merchantId))->with('unsuccess', $result['message']);
            }

            return redirect($this->getSuccessUrl($merchantId))->with('success', __('Payment successful!'));

        } catch (\Exception $e) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', $e->getMessage());
        }
    }

    /**
     * GET /checkout/payment/molly-notify
     * Legacy notify endpoint - redirects to handleCallback
     */
    public function notify(Request $request): RedirectResponse
    {
        return $this->handleCallback($request);
    }
}
