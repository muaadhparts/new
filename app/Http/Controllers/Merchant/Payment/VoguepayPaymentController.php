<?php

namespace App\Http\Controllers\Merchant\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * VoguePay Payment Controller (Nigeria)
 *
 * Uses inline payment - frontend handles payment popup
 */
class VoguepayPaymentController extends BaseMerchantPaymentController
{
    protected string $paymentKeyword = 'voguepay';
    protected string $paymentMethod = 'VoguePay';

    /**
     * POST /merchant/{merchantId}/checkout/payment/voguepay
     *
     * VoguePay uses inline (popup) payment on frontend
     * This endpoint creates the purchase after successful payment
     */
    public function processPayment(Request $request, int $merchantId): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => 'required|string',
        ]);

        $validation = $this->validateCheckoutReady($merchantId);
        if (!$validation['valid']) {
            return response()->json($validation, 400);
        }

        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return $this->handlePaymentError($merchantId, __('VoguePay is not available for this merchant'));
        }

        try {
            // Create purchase
            $result = $this->purchaseCreator->createPurchase($merchantId, [
                'method' => $this->paymentMethod,
                'pay_id' => $config['id'],
                'txnid' => $validated['transaction_id'],
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
     * GET /merchant/{merchantId}/checkout/payment/voguepay/config
     *
     * Returns VoguePay configuration for frontend inline payment
     */
    public function getConfig(Request $request, int $merchantId): JsonResponse
    {
        $validation = $this->validateCheckoutReady($merchantId);
        if (!$validation['valid']) {
            return response()->json($validation, 400);
        }

        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return $this->handlePaymentError($merchantId, __('VoguePay is not available for this merchant'));
        }

        $checkoutData = $this->getCheckoutData($merchantId);
        $currency = $this->priceCalculator->getMonetaryUnit();

        return response()->json([
            'success' => true,
            'voguepay' => [
                'merchant_id' => $config['credentials']['merchant_id'] ?? '',
                'email' => $checkoutData['address']['customer_email'],
                'amount' => $checkoutData['totals']['grand_total'],
                'currency' => $currency->name,
                'merchant_ref' => 'M' . $merchantId . '_' . time(),
                'memo' => __('Purchase from :merchant', ['merchant' => $checkoutData['cart']['merchant']['name'] ?? 'Merchant']),
            ],
        ]);
    }

    /**
     * VoguePay doesn't use traditional callbacks - payment is inline
     */
    public function handleCallback(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Use inline payment instead'], 400);
    }
}
