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
     *
     * OPERATOR PATTERN:
     * - user_id = $merchantId → Merchant's own COD
     * - user_id = 0 AND operator = $merchantId → Platform-provided COD for this merchant
     */
    protected function getPaymentConfig(int $merchantId): ?array
    {
        // Check if COD is enabled for this merchant
        // Priority: 1. Merchant's own COD, 2. Platform-provided COD
        $payment = MerchantPayment::where('keyword', $this->paymentKeyword)
            ->where('checkout', 1)
            ->where(function ($query) use ($merchantId) {
                // Merchant's own COD
                $query->where('user_id', $merchantId)
                    // OR Platform-provided COD for this merchant
                    ->orWhere(function ($q) use ($merchantId) {
                        $q->where('user_id', 0)
                          ->where('operator', $merchantId);
                    });
            })
            // Prefer merchant's own method over platform-provided
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$merchantId])
            ->first();

        if (!$payment) {
            return null;
        }

        $isPlatformProvided = (int)$payment->user_id === 0;

        return [
            'id' => $payment->id,
            'keyword' => $payment->keyword,
            'name' => $payment->name ?? 'Cash On Delivery',
            'is_platform_provided' => $isPlatformProvided,
            'payment_owner_id' => $isPlatformProvided ? 0 : $merchantId,
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
