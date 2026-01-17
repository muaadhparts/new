<?php

namespace App\Http\Controllers\Merchant\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Flutterwave Payment Controller (Africa)
 */
class FlutterwavePaymentController extends BaseMerchantPaymentController
{
    protected string $paymentKeyword = 'flutterwave';
    protected string $paymentMethod = 'Flutterwave';

    /**
     * POST /merchant/{merchantId}/checkout/payment/flutterwave
     */
    public function processPayment(Request $request, int $merchantId): JsonResponse|RedirectResponse
    {
        $validation = $this->validateCheckoutReady($merchantId);
        if (!$validation['valid']) {
            return $request->wantsJson() ? response()->json($validation, 400) : redirect($validation['redirect']);
        }

        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return $this->handlePaymentError($merchantId, __('Flutterwave is not available for this merchant'));
        }

        $checkoutData = $this->getCheckoutData($merchantId);
        $currency = $this->priceCalculator->getMonetaryUnit();

        // Generate transaction reference
        $txRef = 'M' . $merchantId . '_' . time();

        $this->storeInputForCallback($merchantId, [
            'merchant_id' => $merchantId,
            'pay_id' => $config['id'],
            'tx_ref' => $txRef,
        ]);

        try {
            $publicKey = $config['credentials']['public_key'] ?? '';

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/hosted/pay',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode([
                    'amount' => $checkoutData['totals']['grand_total'],
                    'customer_email' => $checkoutData['address']['customer_email'],
                    'currency' => $currency->name,
                    'txref' => $txRef,
                    'PBFPubKey' => $publicKey,
                    'redirect_url' => route('merchant.payment.flutterwave.callback') . '?merchant_id=' . $merchantId,
                    'payment_plan' => '',
                ]),
                CURLOPT_HTTPHEADER => [
                    'content-type: application/json',
                    'cache-control: no-cache',
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                return $this->handlePaymentError($merchantId, 'Connection error: ' . $err);
            }

            $flutterwaveResponse = json_decode($response);

            if (!isset($flutterwaveResponse->data->link)) {
                $message = $flutterwaveResponse->message ?? __('Failed to create payment');
                return $this->handlePaymentError($merchantId, $message);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'redirect' => $flutterwaveResponse->data->link,
                ]);
            }

            return redirect($flutterwaveResponse->data->link);

        } catch (\Exception $e) {
            return $this->handlePaymentError($merchantId, $e->getMessage());
        }
    }

    /**
     * GET /merchant/payment/flutterwave/callback
     */
    public function handleCallback(Request $request): RedirectResponse
    {
        $merchantId = (int)$request->query('merchant_id');
        $txRef = $request->query('txref');

        if (!$merchantId) {
            return redirect(route('merchant-cart.index'))->with('unsuccess', __('Invalid payment response'));
        }

        if ($request->query('cancelled') === 'true') {
            return redirect($this->getCancelUrl($merchantId))->with('unsuccess', __('Payment cancelled'));
        }

        $storedInput = $this->getStoredInput($merchantId);
        if (!$storedInput || ($storedInput['tx_ref'] ?? '') !== $txRef) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment session expired'));
        }

        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment configuration not found'));
        }

        try {
            // Verify transaction
            $secretKey = $config['credentials']['secret_key'] ?? '';

            $ch = curl_init('https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'SECKEY' => $secretKey,
                'txref' => $txRef,
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

            $response = curl_exec($ch);
            curl_close($ch);

            $resp = json_decode($response, true);

            if (($resp['status'] ?? '') !== 'success' || empty($resp['data'])) {
                return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment verification failed'));
            }

            $paymentStatus = $resp['data']['status'] ?? '';
            $chargeCode = $resp['data']['chargecode'] ?? '';

            if (!in_array($chargeCode, ['00', '0']) || $paymentStatus !== 'successful') {
                return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment was not completed'));
            }

            $result = $this->purchaseCreator->createPurchase($merchantId, [
                'method' => $this->paymentMethod,
                'pay_id' => $config['id'],
                'txnid' => $resp['data']['txid'] ?? $txRef,
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
     * POST /cflutter/notify
     * Legacy notify endpoint - redirects to handleCallback
     */
    public function notify(Request $request): RedirectResponse
    {
        return $this->handleCallback($request);
    }
}
