<?php

namespace App\Http\Controllers\Merchant\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Manual/Bank Transfer Payment Controller
 *
 * NOTE: Routes use branchId, but payment methods are merchant-scoped.
 */
class ManualPaymentController extends BaseMerchantPaymentController
{
    protected string $paymentKeyword = 'manual';
    protected string $paymentMethod = 'Manual Payment';

    /**
     * Override: Manual payment doesn't need API credentials
     *
     * OPERATOR PATTERN:
     * - user_id = $merchantId → Merchant's own manual payment
     * - user_id = 0 AND operator = $merchantId → Platform-provided for this merchant
     */
    protected function getPaymentConfig(int $merchantId): ?array
    {
        $payment = \App\Models\MerchantPayment::where('keyword', $this->paymentKeyword)
            ->where('checkout', 1)
            ->where(function ($query) use ($merchantId) {
                $query->where('user_id', $merchantId)
                    ->orWhere(function ($q) use ($merchantId) {
                        $q->where('user_id', 0)
                          ->where('operator', $merchantId);
                    });
            })
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$merchantId])
            ->first();

        if (!$payment) {
            return null;
        }

        $isPlatformProvided = (int)$payment->user_id === 0;

        return [
            'id' => $payment->id,
            'keyword' => $payment->keyword,
            'name' => $payment->name ?? 'Manual Payment',
            'is_platform_provided' => $isPlatformProvided,
            'payment_owner_id' => $isPlatformProvided ? 0 : $merchantId,
        ];
    }

    /**
     * POST /branch/{branchId}/checkout/payment/manual
     */
    public function processPayment(Request $request, int $branchId): JsonResponse
    {
        $validated = $request->validate([
            'txn_img' => 'required|image|max:2048',
        ]);

        // Validate checkout is ready (branch-scoped)
        $validation = $this->validateCheckoutReady($branchId);
        if (!$validation['valid']) {
            return response()->json($validation, 400);
        }

        // Get merchantId from branch (payment methods are merchant-scoped)
        $merchantId = $this->getMerchantIdFromBranch($branchId);
        if (!$merchantId) {
            return $this->handlePaymentError($branchId, __('Invalid branch'));
        }

        // Get payment config (merchant-scoped)
        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return $this->handlePaymentError($branchId, __('Manual payment is not available for this merchant'));
        }

        // Upload transaction image
        $txnImage = null;
        if ($request->hasFile('txn_img')) {
            $file = $request->file('txn_img');
            $txnImage = $file->store('payments/manual', 'public');
        }

        // Create purchase with pending verification status (branch-scoped)
        $result = $this->purchaseCreator->createPurchase($branchId, [
            'method' => $this->paymentMethod,
            'pay_id' => $config['id'],
            'txnid' => $txnImage,
            'payment_status' => 'pending',
        ]);

        if (!$result['success']) {
            return $this->handlePaymentError($branchId, $result['message'] ?? __('Failed to create order'));
        }

        return response()->json([
            'success' => true,
            'message' => __('Your order has been placed. Please wait for payment verification.'),
            'purchase_number' => $result['purchase']->purchase_number,
            'redirect' => $this->getSuccessUrl($branchId),
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
