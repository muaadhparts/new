<?php

namespace App\Http\Controllers\Merchant\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * SSL Commerz Payment Controller (Bangladesh)
 */
class SslCommerzPaymentController extends BaseMerchantPaymentController
{
    protected string $paymentKeyword = 'sslcommerz';
    protected string $paymentMethod = 'SSL Commerz';

    /**
     * POST /merchant/{merchantId}/checkout/payment/sslcommerz
     */
    public function processPayment(Request $request, int $merchantId): JsonResponse|RedirectResponse
    {
        $validation = $this->validateCheckoutReady($merchantId);
        if (!$validation['valid']) {
            return $request->wantsJson() ? response()->json($validation, 400) : redirect($validation['redirect']);
        }

        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return $this->handlePaymentError($merchantId, __('SSL Commerz is not available for this merchant'));
        }

        $checkoutData = $this->getCheckoutData($merchantId);
        $currency = $this->priceCalculator->getMonetaryUnit();
        $credentials = $config['credentials'];

        // Generate unique transaction ID
        $txnId = 'SSLCZ_M' . $merchantId . '_' . uniqid();

        $this->storeInputForCallback($merchantId, [
            'merchant_id' => $merchantId,
            'pay_id' => $config['id'],
            'txn_id' => $txnId,
        ]);

        try {
            // Prepare POST data
            $postData = [
                'store_id' => $credentials['store_id'] ?? '',
                'store_passwd' => $credentials['store_password'] ?? '',
                'total_amount' => $checkoutData['totals']['grand_total'],
                'currency' => $currency->name,
                'tran_id' => $txnId,
                'success_url' => route('merchant.payment.sslcommerz.callback') . '?merchant_id=' . $merchantId,
                'fail_url' => $this->getFailureUrl($merchantId),
                'cancel_url' => $this->getCancelUrl($merchantId),
                'cus_name' => $checkoutData['address']['customer_name'],
                'cus_email' => $checkoutData['address']['customer_email'],
                'cus_add1' => $checkoutData['address']['customer_address'] ?? '',
                'cus_city' => $checkoutData['address']['customer_city'] ?? '',
                'cus_state' => $checkoutData['address']['customer_state'] ?? '',
                'cus_postcode' => $checkoutData['address']['customer_zip'] ?? '',
                'cus_country' => $checkoutData['address']['customer_country'] ?? '',
                'cus_phone' => $checkoutData['address']['customer_phone'] ?? '',
                'cus_fax' => $checkoutData['address']['customer_phone'] ?? '',
            ];

            // Determine API URL based on sandbox mode
            $sandboxMode = ($credentials['sandbox_check'] ?? 0) == 1;
            $apiUrl = $sandboxMode
                ? 'https://sandbox.sslcommerz.com/gwprocess/v3/api.php'
                : 'https://securepay.sslcommerz.com/gwprocess/v3/api.php';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $content = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode != 200 || !$content) {
                return $this->handlePaymentError($merchantId, __('Could not connect to payment gateway'));
            }

            $sslResponse = json_decode($content, true);

            if (empty($sslResponse['GatewayPageURL'])) {
                $message = $sslResponse['failedreason'] ?? __('Failed to create payment');
                return $this->handlePaymentError($merchantId, $message);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'redirect' => $sslResponse['GatewayPageURL'],
                ]);
            }

            return redirect($sslResponse['GatewayPageURL']);

        } catch (\Exception $e) {
            return $this->handlePaymentError($merchantId, $e->getMessage());
        }
    }

    /**
     * POST /merchant/payment/sslcommerz/callback
     */
    public function handleCallback(Request $request): RedirectResponse
    {
        $merchantId = (int)$request->query('merchant_id');
        $tranId = $request->input('tran_id');
        $status = $request->input('status');

        if (!$merchantId) {
            return redirect(route('merchant-cart.index'))->with('unsuccess', __('Invalid payment response'));
        }

        $storedInput = $this->getStoredInput($merchantId);
        if (!$storedInput || ($storedInput['txn_id'] ?? '') !== $tranId) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment session expired'));
        }

        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment configuration not found'));
        }

        if ($status !== 'VALID') {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment was not completed'));
        }

        try {
            $result = $this->purchaseCreator->createPurchase($merchantId, [
                'method' => $this->paymentMethod,
                'pay_id' => $config['id'],
                'txnid' => $tranId,
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
     * POST /checkout/payment/ssl-notify
     * Legacy notify endpoint - redirects to handleCallback
     */
    public function notify(Request $request): RedirectResponse
    {
        return $this->handleCallback($request);
    }
}
