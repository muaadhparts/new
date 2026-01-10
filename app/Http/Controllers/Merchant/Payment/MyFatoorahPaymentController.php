<?php

namespace App\Http\Controllers\Merchant\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;

/**
 * MyFatoorah Payment Controller (Middle East)
 */
class MyFatoorahPaymentController extends BaseMerchantPaymentController
{
    protected string $paymentKeyword = 'myfatoorah';
    protected string $paymentMethod = 'MyFatoorah';

    /**
     * POST /merchant/{merchantId}/checkout/payment/myfatoorah
     */
    public function processPayment(Request $request, int $merchantId): JsonResponse|RedirectResponse
    {
        $validation = $this->validateCheckoutReady($merchantId);
        if (!$validation['valid']) {
            return $request->wantsJson() ? response()->json($validation, 400) : redirect($validation['redirect']);
        }

        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return $this->handlePaymentError($merchantId, __('MyFatoorah is not available for this merchant'));
        }

        $checkoutData = $this->getCheckoutData($merchantId);
        $currency = $this->priceCalculator->getCurrency();

        $this->storeInputForCallback($merchantId, [
            'merchant_id' => $merchantId,
            'pay_id' => $config['id'],
        ]);

        try {
            $apiKey = $config['credentials']['api_key'] ?? '';
            $testMode = ($config['credentials']['sandbox'] ?? 0) == 1;
            $baseUrl = $testMode
                ? 'https://apitest.myfatoorah.com'
                : 'https://api.myfatoorah.com';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '/v2/SendPayment', [
                'CustomerName' => $checkoutData['address']['customer_name'],
                'NotificationOption' => 'LNK',
                'InvoiceValue' => $checkoutData['totals']['grand_total'],
                'DisplayCurrencyIso' => $currency->name,
                'CustomerEmail' => $checkoutData['address']['customer_email'],
                'CallBackUrl' => route('merchant.payment.myfatoorah.callback') . '?merchant_id=' . $merchantId,
                'ErrorUrl' => $this->getFailureUrl($merchantId),
                'Language' => app()->getLocale() === 'ar' ? 'ar' : 'en',
            ]);

            $result = $response->json();

            if (!$response->successful() || !isset($result['Data']['InvoiceURL'])) {
                return $this->handlePaymentError($merchantId, $result['Message'] ?? __('Failed to create payment'));
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'redirect' => $result['Data']['InvoiceURL'],
                ]);
            }

            return redirect($result['Data']['InvoiceURL']);

        } catch (\Exception $e) {
            return $this->handlePaymentError($merchantId, $e->getMessage());
        }
    }

    /**
     * GET /merchant/payment/myfatoorah/callback
     */
    public function handleCallback(Request $request): RedirectResponse
    {
        $merchantId = (int)$request->query('merchant_id');
        $paymentId = $request->query('paymentId');

        if (!$merchantId) {
            return redirect(route('front.cart'))->with('unsuccess', __('Invalid payment response'));
        }

        $storedInput = $this->getStoredInput($merchantId);
        if (!$storedInput) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment session expired'));
        }

        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment configuration not found'));
        }

        try {
            $apiKey = $config['credentials']['api_key'] ?? '';
            $testMode = ($config['credentials']['sandbox'] ?? 0) == 1;
            $baseUrl = $testMode ? 'https://apitest.myfatoorah.com' : 'https://api.myfatoorah.com';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->post($baseUrl . '/v2/GetPaymentStatus', [
                'Key' => $paymentId,
                'KeyType' => 'PaymentId',
            ]);

            $result = $response->json();
            $status = $result['Data']['InvoiceStatus'] ?? '';

            if ($status !== 'Paid') {
                return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment was not completed'));
            }

            $purchaseResult = $this->purchaseCreator->createPurchase($merchantId, [
                'method' => $this->paymentMethod,
                'pay_id' => $config['id'],
                'txnid' => $paymentId,
                'payment_status' => 'Completed',
            ]);

            $this->clearStoredInput($merchantId);

            if (!$purchaseResult['success']) {
                return redirect($this->getFailureUrl($merchantId))->with('unsuccess', $purchaseResult['message']);
            }

            return redirect($this->getSuccessUrl($merchantId))->with('success', __('Payment successful!'));

        } catch (\Exception $e) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', $e->getMessage());
        }
    }

    /**
     * GET /checkout/payment/myfatoorah/notify
     * Legacy notify endpoint - redirects to handleCallback
     */
    public function notify(Request $request): RedirectResponse
    {
        return $this->handleCallback($request);
    }
}
