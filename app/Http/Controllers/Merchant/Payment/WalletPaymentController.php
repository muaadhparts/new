<?php

namespace App\Http\Controllers\Merchant\Payment;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Wallet Payment Controller
 */
class WalletPaymentController extends BaseMerchantPaymentController
{
    protected string $paymentKeyword = 'wallet';
    protected string $paymentMethod = 'Wallet';

    /**
     * POST /merchant/{merchantId}/checkout/payment/wallet
     */
    public function processPayment(Request $request, int $merchantId): JsonResponse
    {
        // Validate checkout is ready
        $validation = $this->validateCheckoutReady($merchantId);
        if (!$validation['valid']) {
            return response()->json($validation, 400);
        }

        // Get checkout data
        $checkoutData = $this->getCheckoutData($merchantId);
        $grandTotal = $checkoutData['totals']['grand_total'];

        // Get user wallet balance
        $user = Auth::user();
        $walletBalance = (float)($user->affilate_income ?? 0);

        if ($walletBalance < $grandTotal) {
            return $this->handlePaymentError($merchantId, __('Insufficient wallet balance. You have :balance but need :total', [
                'balance' => $this->priceCalculator->formatPrice($walletBalance),
                'total' => $this->priceCalculator->formatPrice($grandTotal),
            ]));
        }

        // Deduct from wallet
        $user->affilate_income = $walletBalance - $grandTotal;
        $user->save();

        // Create purchase
        $result = $this->purchaseCreator->createPurchase($merchantId, [
            'method' => $this->paymentMethod,
            'payment_status' => 'Completed',
            'wallet_price' => $grandTotal,
        ]);

        if (!$result['success']) {
            // Refund wallet if purchase creation fails
            $user->affilate_income = $walletBalance;
            $user->save();

            return $this->handlePaymentError($merchantId, $result['message'] ?? __('Failed to create order'));
        }

        return $this->handlePaymentSuccess($merchantId, $result['purchase']);
    }

    /**
     * GET /merchant/{merchantId}/checkout/wallet/balance
     */
    public function getBalance(int $merchantId): JsonResponse
    {
        $user = Auth::user();
        $walletBalance = (float)($user->affilate_income ?? 0);
        $checkoutData = $this->getCheckoutData($merchantId);
        $grandTotal = $checkoutData['totals']['grand_total'];

        return response()->json([
            'success' => true,
            'balance' => $walletBalance,
            'balance_formatted' => $this->priceCalculator->formatPrice($walletBalance),
            'required' => $grandTotal,
            'required_formatted' => $this->priceCalculator->formatPrice($grandTotal),
            'sufficient' => $walletBalance >= $grandTotal,
        ]);
    }

    /**
     * Wallet doesn't have callbacks
     */
    public function handleCallback(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not applicable for Wallet'], 400);
    }
}
