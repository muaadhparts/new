<?php

namespace App\Http\Controllers\Merchant\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use MyFatoorah\Library\API\Payment\MyFatoorahPayment;
use MyFatoorah\Library\API\Payment\MyFatoorahPaymentStatus;

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
        $currency = $this->priceCalculator->getMonetaryUnit();

        // Validate amount
        $totalAmount = $checkoutData['totals']['grand_total'];
        if ($totalAmount <= 0) {
            return $this->handlePaymentError($merchantId, __('Invalid payment amount'));
        }

        $this->storeInputForCallback($merchantId, [
            'merchant_id' => $merchantId,
            'pay_id' => $config['id'],
        ]);

        try {
            $mfConfig = [
                'apiKey'      => $config['credentials']['api_key'] ?? '',
                'isTest'      => ($config['credentials']['sandbox'] ?? 0) == 1,
                'countryCode' => 'SAU',
            ];

            // Calculate final amount (convert from display currency to base currency)
            $finalAmount = $totalAmount / ($currency->value ?? 1);

            // Sanitize customer data
            $customerName = $this->sanitizeString($checkoutData['address']['customer_name'] ?? 'Guest', 50);
            $customerEmail = filter_var($checkoutData['address']['customer_email'] ?? '', FILTER_VALIDATE_EMAIL)
                ? $checkoutData['address']['customer_email']
                : 'guest@example.com';
            $customerPhone = preg_replace('/[^0-9]/', '', $checkoutData['address']['customer_phone'] ?? '0000000000');

            $curlData = [
                'NotificationOption' => 'ALL',
                'CustomerName'       => $customerName,
                'InvoiceValue'       => round($finalAmount, 2),
                'DisplayCurrencyIso' => $currency->name ?? 'SAR',
                'CustomerEmail'      => $customerEmail,
                'CallBackUrl'        => route('merchant.payment.myfatoorah.callback') . '?merchant_id=' . $merchantId,
                'ErrorUrl'           => $this->getFailureUrl($merchantId),
                'MobileCountryCode'  => '+966',
                'CustomerMobile'     => substr($customerPhone, 0, 15),
                'Language'           => app()->getLocale() === 'ar' ? 'ar' : 'en',
                'CustomerReference'  => 'order_' . time() . '_' . $merchantId,
                'SourceInfo'         => 'Laravel - Merchant Checkout',
            ];

            $mfObj = new MyFatoorahPayment($mfConfig);
            $payment = $mfObj->getInvoiceURL($curlData);

            if (empty($payment['invoiceURL'])) {
                return $this->handlePaymentError($merchantId, __('Failed to create payment invoice'));
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'redirect' => $payment['invoiceURL'],
                ]);
            }

            return redirect($payment['invoiceURL']);

        } catch (\Exception $e) {
            report($e);
            return $this->handlePaymentError($merchantId, __('Payment processing failed. Please try again.'));
        }
    }

    /**
     * GET /merchant/payment/myfatoorah/callback
     */
    public function handleCallback(Request $request): RedirectResponse
    {
        $merchantId = (int)$request->query('merchant_id');
        $paymentId = $request->query('paymentId');

        // Validate required parameters
        if (!$merchantId || !$paymentId) {
            return redirect(route('front.cart'))->with('unsuccess', __('Invalid payment response'));
        }

        // Validate paymentId format (alphanumeric with dashes)
        if (!preg_match('/^[a-zA-Z0-9\-]+$/', $paymentId)) {
            return redirect(route('front.cart'))->with('unsuccess', __('Invalid payment response'));
        }

        $storedInput = $this->getStoredInput($merchantId);
        if (!$storedInput) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment session expired'));
        }

        // Verify merchant_id matches stored session
        if (($storedInput['merchant_id'] ?? null) !== $merchantId) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Invalid payment session'));
        }

        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment configuration not found'));
        }

        try {
            $mfConfig = [
                'apiKey'      => $config['credentials']['api_key'] ?? '',
                'isTest'      => ($config['credentials']['sandbox'] ?? 0) == 1,
                'countryCode' => 'SAU',
            ];

            $mfObj = new MyFatoorahPaymentStatus($mfConfig);
            $data = $mfObj->getPaymentStatus($paymentId, 'PaymentId');

            $status = $data->InvoiceStatus ?? '';

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
            report($e);
            return redirect($this->getFailureUrl($merchantId))->with('unsuccess', __('Payment verification failed. Please contact support.'));
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

    /**
     * Sanitize string for API submission
     */
    private function sanitizeString(?string $value, int $maxLength = 100): string
    {
        if (empty($value)) {
            return '';
        }

        // Remove potentially dangerous characters
        $value = strip_tags($value);
        $value = preg_replace('/[<>"\']/', '', $value);
        $value = trim($value);

        return mb_substr($value, 0, $maxLength);
    }
}
