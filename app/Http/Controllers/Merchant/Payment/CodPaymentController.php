<?php

namespace App\Http\Controllers\Merchant\Payment;

use App\Models\MerchantPayment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Cash on Delivery Payment Controller
 */
class CodPaymentController extends BaseMerchantPaymentController
{
    protected string $paymentKeyword = 'cod';
    protected string $paymentMethod = 'Cash On Delivery';

    /**
     * Override: COD doesn't need API credentials
     */
    protected function getPaymentConfig(int $merchantId): ?array
    {
        // Check if COD is enabled for this merchant
        $payment = MerchantPayment::where('keyword', $this->paymentKeyword)
            ->where('user_id', $merchantId)
            ->where('checkout', 1)
            ->first();

        if (!$payment) {
            return null;
        }

        return [
            'id' => $payment->id,
            'keyword' => $payment->keyword,
            'name' => $payment->name ?? $payment->name ?? 'Cash On Delivery',
        ];
    }

    /**
     * POST /merchant/{merchantId}/checkout/payment/cod
     */
    public function processPayment(Request $request, int $merchantId): JsonResponse
    {
        // Validate checkout is ready
        $validation = $this->validateCheckoutReady($merchantId);
        if (!$validation['valid']) {
            return response()->json($validation, 400);
        }

        // Get payment config
        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return $this->handlePaymentError($merchantId, __('Cash on Delivery is not available for this merchant'));
        }

        // Create purchase with pending status
        $result = $this->purchaseCreator->createPurchase($merchantId, [
            'method' => $this->paymentMethod,
            'pay_id' => $config['id'],
            'payment_status' => 'pending',
        ]);

        if (!$result['success']) {
            return $this->handlePaymentError($merchantId, $result['message'] ?? __('Failed to create order'));
        }

        return $this->handlePaymentSuccess($merchantId, $result['purchase']);
    }

    /**
     * COD doesn't have callbacks
     */
    public function handleCallback(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not applicable for COD'], 400);
    }
}
