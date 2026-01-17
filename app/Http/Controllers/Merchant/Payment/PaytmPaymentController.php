<?php

namespace App\Http\Controllers\Merchant\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use App\Traits\Paytm;

/**
 * Paytm Payment Controller (INR Only)
 */
class PaytmPaymentController extends BaseMerchantPaymentController
{
    use Paytm;

    protected string $paymentKeyword = 'paytm';
    protected string $paymentMethod = 'Paytm';

    /**
     * POST /merchant/{merchantId}/checkout/payment/paytm
     */
    public function processPayment(Request $request, int $merchantId): JsonResponse|View
    {
        $validation = $this->validateCheckoutReady($merchantId);
        if (!$validation['valid']) {
            return response()->json($validation, 400);
        }

        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return $this->handlePaymentError($merchantId, __('Paytm is not available for this merchant'));
        }

        // Paytm requires INR
        $currency = $this->priceCalculator->getMonetaryUnit();
        if ($currency->name !== 'INR') {
            return $this->handlePaymentError($merchantId, __('Paytm only supports INR currency'));
        }

        $checkoutData = $this->getCheckoutData($merchantId);

        $this->storeInputForCallback($merchantId, [
            'merchant_id' => $merchantId,
            'pay_id' => $config['id'],
        ]);

        try {
            $itemNumber = 'M' . $merchantId . '_' . time();
            $amount = round($checkoutData['totals']['grand_total'] / $currency->value, 2);

            $dataForRequest = $this->handlePaytmRequest($itemNumber, $amount, 'merchant_checkout');
            $paytmTxnUrl = 'https://securegw-stage.paytm.in/theia/processTransaction';
            $paramList = $dataForRequest['paramList'];
            $checkSum = $dataForRequest['checkSum'];

            // Store item number for callback verification
            session(['merchant_payment_order_id_' . $merchantId => $itemNumber]);

            return view('frontend.paytm-merchant-form', compact('paytmTxnUrl', 'paramList', 'checkSum', 'merchantId'));

        } catch (\Exception $e) {
            return $this->handlePaymentError($merchantId, $e->getMessage());
        }
    }

    /**
     * POST /merchant/payment/paytm/callback
     */
    public function handleCallback(Request $request): RedirectResponse
    {
        $inputData = $request->all();
        $orderId = $inputData['ORDERID'] ?? '';

        // Extract merchant ID from order ID (format: M{merchantId}_{timestamp})
        preg_match('/^M(\d+)_/', $orderId, $matches);
        $merchantId = isset($matches[1]) ? (int)$matches[1] : 0;

        if (!$merchantId) {
            return redirect(route('merchant-cart.index'))->with('unsuccess', __('Invalid payment response'));
        }

        $storedInput = $this->getStoredInput($merchantId);
        if (!$storedInput) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment session expired'));
        }

        $storedOrderId = session('merchant_payment_order_id_' . $merchantId);
        if ($storedOrderId !== $orderId) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment verification failed'));
        }

        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment configuration not found'));
        }

        if ($inputData['STATUS'] !== 'TXN_SUCCESS') {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment was not completed'));
        }

        try {
            $result = $this->purchaseCreator->createPurchase($merchantId, [
                'method' => $this->paymentMethod,
                'pay_id' => $config['id'],
                'txnid' => $inputData['TXNID'] ?? '',
                'payment_status' => 'Completed',
            ]);

            $this->clearStoredInput($merchantId);
            session()->forget('merchant_payment_order_id_' . $merchantId);

            if (!$result['success']) {
                return redirect($this->getFailureUrl($merchantId))->with('unsuccess', $result['message']);
            }

            return redirect($this->getSuccessUrl($merchantId))->with('success', __('Payment successful!'));

        } catch (\Exception $e) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', $e->getMessage());
        }
    }

    /**
     * POST /checkout/payment/paytm-notify
     * Legacy notify endpoint - redirects to handleCallback
     */
    public function notify(Request $request): RedirectResponse
    {
        return $this->handleCallback($request);
    }
}
