<?php

namespace App\Http\Controllers\Merchant\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * MercadoPago Payment Controller (Latin America)
 */
class MercadopagoPaymentController extends BaseMerchantPaymentController
{
    protected string $paymentKeyword = 'mercadopago';
    protected string $paymentMethod = 'MercadoPago';

    /**
     * POST /merchant/{merchantId}/checkout/payment/mercadopago
     *
     * MercadoPago sends token from frontend after card tokenization
     */
    public function processPayment(Request $request, int $merchantId): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        $validation = $this->validateCheckoutReady($merchantId);
        if (!$validation['valid']) {
            return response()->json($validation, 400);
        }

        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return $this->handlePaymentError($merchantId, __('MercadoPago is not available for this merchant'));
        }

        $checkoutData = $this->getCheckoutData($merchantId);

        try {
            $accessToken = $config['credentials']['token'] ?? '';

            \MercadoPago\SDK::setAccessToken($accessToken);

            $payment = new \MercadoPago\Payment();
            $payment->transaction_amount = (string)$checkoutData['totals']['grand_total'];
            $payment->token = $validated['token'];
            $payment->description = __('Purchase from :merchant', ['merchant' => $checkoutData['cart']['merchant']['name'] ?? 'Merchant']);
            $payment->installments = 1;
            $payment->payer = [
                'email' => $checkoutData['address']['customer_email'],
            ];

            $payment->save();

            if ($payment->status !== 'approved') {
                $errorMessage = $payment->status_detail ?? __('Payment was not approved');
                return $this->handlePaymentError($merchantId, $errorMessage);
            }

            $result = $this->purchaseCreator->createPurchase($merchantId, [
                'method' => $this->paymentMethod,
                'pay_id' => $config['id'],
                'txnid' => $payment->id,
                'payment_status' => 'Completed',
            ]);

            if (!$result['success']) {
                return $this->handlePaymentError($merchantId, $result['message'] ?? __('Failed to create order'));
            }

            return response()->json([
                'success' => true,
                'message' => __('Payment successful!'),
                'purchase_number' => $result['purchase']->purchase_number,
                'redirect' => $this->getSuccessUrl($merchantId),
            ]);

        } catch (\Exception $e) {
            return $this->handlePaymentError($merchantId, $e->getMessage());
        }
    }

    /**
     * GET /merchant/{merchantId}/checkout/payment/mercadopago/config
     *
     * Returns MercadoPago configuration for frontend
     */
    public function getConfig(Request $request, int $merchantId): JsonResponse
    {
        $validation = $this->validateCheckoutReady($merchantId);
        if (!$validation['valid']) {
            return response()->json($validation, 400);
        }

        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return $this->handlePaymentError($merchantId, __('MercadoPago is not available for this merchant'));
        }

        $checkoutData = $this->getCheckoutData($merchantId);

        return response()->json([
            'success' => true,
            'mercadopago' => [
                'public_key' => $config['credentials']['public_key'] ?? '',
                'amount' => $checkoutData['totals']['grand_total'],
                'email' => $checkoutData['address']['customer_email'],
            ],
        ]);
    }

    /**
     * MercadoPago doesn't use traditional callbacks - payment is processed directly
     */
    public function handleCallback(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not applicable for MercadoPago'], 400);
    }
}
