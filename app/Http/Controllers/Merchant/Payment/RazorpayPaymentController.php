<?php

namespace App\Http\Controllers\Merchant\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Razorpay\Api\Api;

/**
 * Razorpay Payment Controller (INR Only)
 */
class RazorpayPaymentController extends BaseMerchantPaymentController
{
    protected string $paymentKeyword = 'razorpay';
    protected string $paymentMethod = 'Razorpay';

    /**
     * POST /merchant/{merchantId}/checkout/payment/razorpay
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
            return $this->handlePaymentError($merchantId, __('Razorpay is not available for this merchant'));
        }

        // Check currency (Razorpay requires INR)
        $currency = $this->priceCalculator->getMonetaryUnit();
        if ($currency->name !== 'INR') {
            return $this->handlePaymentError($merchantId, __('Razorpay only supports INR currency'));
        }

        // Get checkout data
        $checkoutData = $this->getCheckoutData($merchantId);

        try {
            $api = new Api($config['credentials']['key'] ?? '', $config['credentials']['secret'] ?? '');

            // Create Razorpay order
            $orderData = [
                'receipt' => 'M' . $merchantId . '_' . time(),
                'amount' => round($checkoutData['totals']['grand_total'] * 100), // Amount in paise
                'currency' => 'INR',
                'payment_capture' => 1,
            ];

            $razorpayOrder = $api->order->create($orderData);

            // Store for callback
            $this->storeInputForCallback($merchantId, [
                'merchant_id' => $merchantId,
                'pay_id' => $config['id'],
                'razorpay_order_id' => $razorpayOrder['id'],
            ]);

            return response()->json([
                'success' => true,
                'razorpay' => [
                    'key' => $config['credentials']['key'],
                    'order_id' => $razorpayOrder['id'],
                    'amount' => $razorpayOrder['amount'],
                    'currency' => 'INR',
                    'name' => $checkoutData['cart']['merchant']['name'] ?? 'Merchant',
                    'prefill' => [
                        'name' => $checkoutData['address']['customer_name'],
                        'email' => $checkoutData['address']['customer_email'],
                        'contact' => $checkoutData['address']['customer_phone'],
                    ],
                ],
                'callback_url' => route('merchant.payment.razorpay.callback'),
            ]);

        } catch (\Exception $e) {
            return $this->handlePaymentError($merchantId, $e->getMessage());
        }
    }

    /**
     * POST /merchant/payment/razorpay/callback
     */
    public function handleCallback(Request $request): RedirectResponse
    {
        $razorpayPaymentId = $request->input('razorpay_payment_id');
        $razorpayOrderId = $request->input('razorpay_order_id');
        $razorpaySignature = $request->input('razorpay_signature');

        // Find merchant ID from stored input by order ID
        $merchantId = $this->findMerchantByRazorpayOrder($razorpayOrderId);

        if (!$merchantId) {
            return redirect(route('front.cart'))
                ->with('unsuccess', __('Invalid payment response'));
        }

        // Get stored input
        $storedInput = $this->getStoredInput($merchantId);
        if (!$storedInput) {
            return redirect($this->getFailureUrl($merchantId))
                ->with('unsuccess', __('Payment session expired'));
        }

        // Get payment config
        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return redirect($this->getFailureUrl($merchantId))
                ->with('unsuccess', __('Payment configuration not found'));
        }

        try {
            $api = new Api($config['credentials']['key'] ?? '', $config['credentials']['secret'] ?? '');

            // Verify signature
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature' => $razorpaySignature,
            ]);

            // Create purchase
            $result = $this->purchaseCreator->createPurchase($merchantId, [
                'method' => $this->paymentMethod,
                'pay_id' => $config['id'],
                'txnid' => $razorpayPaymentId,
                'charge_id' => $razorpayOrderId,
                'payment_status' => 'Completed',
            ]);

            // Clear stored input
            $this->clearStoredInput($merchantId);

            if (!$result['success']) {
                return redirect($this->getFailureUrl($merchantId))
                    ->with('unsuccess', $result['message'] ?? __('Failed to create order'));
            }

            return redirect($this->getSuccessUrl($merchantId))
                ->with('success', __('Payment successful!'));

        } catch (\Exception $e) {
            return redirect($this->getFailureUrl($merchantId))
                ->with('unsuccess', $e->getMessage());
        }
    }

    /**
     * Find merchant ID by Razorpay order ID
     */
    protected function findMerchantByRazorpayOrder(string $orderId): ?int
    {
        // Search all merchant payment sessions
        // This is a simplified approach - in production you'd use a database table
        for ($i = 1; $i <= 1000; $i++) {
            $stored = $this->getStoredInput($i);
            if ($stored && ($stored['razorpay_order_id'] ?? '') === $orderId) {
                return $i;
            }
        }
        return null;
    }

    /**
     * POST /checkout/payment/razorpay-notify
     * Legacy notify endpoint - redirects to handleCallback
     */
    public function notify(Request $request): RedirectResponse
    {
        return $this->handleCallback($request);
    }
}
