<?php

namespace App\Http\Controllers\Merchant\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Manual/Bank Transfer Payment Controller
 */
class ManualPaymentController extends BaseMerchantPaymentController
{
    protected string $paymentKeyword = 'manual';
    protected string $paymentMethod = 'Manual Payment';

    /**
     * POST /merchant/{merchantId}/checkout/payment/manual
     */
    public function processPayment(Request $request, int $merchantId): JsonResponse
    {
        $validated = $request->validate([
            'txn_img' => 'required|image|max:2048',
        ]);

        // Validate checkout is ready
        $validation = $this->validateCheckoutReady($merchantId);
        if (!$validation['valid']) {
            return response()->json($validation, 400);
        }

        // Get payment config
        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return $this->handlePaymentError($merchantId, __('Manual payment is not available for this merchant'));
        }

        // Upload transaction image
        $txnImage = null;
        if ($request->hasFile('txn_img')) {
            $file = $request->file('txn_img');
            $txnImage = $file->store('payments/manual', 'public');
        }

        // Create purchase with pending verification status
        $result = $this->purchaseCreator->createPurchase($merchantId, [
            'method' => $this->paymentMethod,
            'pay_id' => $config['id'],
            'txnid' => $txnImage,
            'payment_status' => 'pending',
        ]);

        if (!$result['success']) {
            return $this->handlePaymentError($merchantId, $result['message'] ?? __('Failed to create order'));
        }

        return response()->json([
            'success' => true,
            'message' => __('Your order has been placed. Please wait for payment verification.'),
            'purchase_number' => $result['purchase']->purchase_number,
            'redirect' => $this->getSuccessUrl($merchantId),
        ]);
    }

    /**
     * Manual payment doesn't have callbacks
     */
    public function handleCallback(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not applicable for Manual Payment'], 400);
    }
}
