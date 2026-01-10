<?php

namespace App\Http\Controllers\Merchant\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

/**
 * Paystack Payment Controller (Africa - NGN, GHS, ZAR, KES)
 *
 * Uses inline payment - frontend handles payment popup
 */
class PaystackPaymentController extends BaseMerchantPaymentController
{
    protected string $paymentKeyword = 'paystack';
    protected string $paymentMethod = 'Paystack';

    /**
     * POST /merchant/{merchantId}/checkout/payment/paystack
     *
     * Paystack uses inline (popup) payment on frontend
     * This endpoint creates the purchase after successful payment
     */
    public function processPayment(Request $request, int $merchantId): JsonResponse
    {
        $validated = $request->validate([
            'reference' => 'required|string',
        ]);

        $validation = $this->validateCheckoutReady($merchantId);
        if (!$validation['valid']) {
            return response()->json($validation, 400);
        }

        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return $this->handlePaymentError($merchantId, __('Paystack is not available for this merchant'));
        }

        try {
            // Verify payment with Paystack API
            $secretKey = $config['credentials']['secret'] ?? '';
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
            ])->get('https://api.paystack.co/transaction/verify/' . $validated['reference']);

            $result = $response->json();

            if (!$response->successful() || ($result['data']['status'] ?? '') !== 'success') {
                return $this->handlePaymentError($merchantId, __('Payment verification failed'));
            }

            // Create purchase
            $purchaseResult = $this->purchaseCreator->createPurchase($merchantId, [
                'method' => $this->paymentMethod,
                'pay_id' => $config['id'],
                'txnid' => $validated['reference'],
                'payment_status' => 'Completed',
            ]);

            if (!$purchaseResult['success']) {
                return $this->handlePaymentError($merchantId, $purchaseResult['message'] ?? __('Failed to create order'));
            }

            return response()->json([
                'success' => true,
                'message' => __('Payment successful!'),
                'purchase_number' => $purchaseResult['purchase']->purchase_number,
                'redirect' => $this->getSuccessUrl($merchantId),
            ]);

        } catch (\Exception $e) {
            return $this->handlePaymentError($merchantId, $e->getMessage());
        }
    }

    /**
     * GET /merchant/{merchantId}/checkout/payment/paystack/config
     *
     * Returns Paystack configuration for frontend inline payment
     */
    public function getConfig(Request $request, int $merchantId): JsonResponse
    {
        $validation = $this->validateCheckoutReady($merchantId);
        if (!$validation['valid']) {
            return response()->json($validation, 400);
        }

        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return $this->handlePaymentError($merchantId, __('Paystack is not available for this merchant'));
        }

        $checkoutData = $this->getCheckoutData($merchantId);
        $currency = $this->priceCalculator->getCurrency();

        // Generate unique reference
        $reference = 'M' . $merchantId . '_' . time() . '_' . uniqid();

        return response()->json([
            'success' => true,
            'paystack' => [
                'key' => $config['credentials']['public'] ?? $config['credentials']['key'] ?? '',
                'email' => $checkoutData['address']['customer_email'],
                'amount' => (int)($checkoutData['totals']['grand_total'] * 100), // Amount in kobo
                'currency' => $currency->name,
                'ref' => $reference,
                'callback_url' => route('merchant.payment.paystack.process', $merchantId),
            ],
        ]);
    }

    /**
     * Paystack doesn't use traditional callbacks - payment is inline
     */
    public function handleCallback(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Use inline payment instead'], 400);
    }
}
