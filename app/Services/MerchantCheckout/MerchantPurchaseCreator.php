<?php

namespace App\Services\MerchantCheckout;

use App\Models\Purchase;
use App\Models\MerchantPurchase;
use App\Models\User;
use App\Models\Currency;
use App\Models\DeliveryCourier;
use App\Classes\MuaadhMailer;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Purchase creation for Merchant Checkout
 *
 * Handles final purchase record creation and notifications
 */
class MerchantPurchaseCreator
{
    protected MerchantCartService $cartService;
    protected MerchantSessionManager $sessionManager;
    protected MerchantPriceCalculator $priceCalculator;

    public function __construct(
        MerchantCartService $cartService,
        MerchantSessionManager $sessionManager,
        MerchantPriceCalculator $priceCalculator
    ) {
        $this->cartService = $cartService;
        $this->sessionManager = $sessionManager;
        $this->priceCalculator = $priceCalculator;
    }

    /**
     * Create purchase from checkout data
     */
    public function createPurchase(int $merchantId, array $paymentData): array
    {
        $addressData = $this->sessionManager->getAddressData($merchantId);
        $shippingData = $this->sessionManager->getShippingData($merchantId);
        $discountData = $this->sessionManager->getDiscountData($merchantId);
        $cartPayload = $this->cartService->buildCartPayload($merchantId);

        if (!$addressData || !$shippingData) {
            return [
                'success' => false,
                'error' => 'incomplete_checkout',
                'message' => __('Checkout data is incomplete'),
            ];
        }

        $user = Auth::user();
        $merchant = User::find($merchantId);
        $currency = $this->priceCalculator->getCurrency();

        // Calculate final totals
        $totals = $this->priceCalculator->calculateTotals($cartPayload['items'], [
            'discount_amount' => $discountData['amount'] ?? 0,
            'tax_rate' => $addressData['tax_rate'] ?? 0,
            'shipping_cost' => $shippingData['shipping_cost'] ?? 0,
            'packing_cost' => $shippingData['packing_cost'] ?? 0,
            'courier_fee' => $shippingData['courier_fee'] ?? 0,
        ]);

        try {
            DB::beginTransaction();

            // Create main purchase
            $purchase = new Purchase();
            $purchase->fill([
                'user_id' => $user->id,
                'purchase_number' => $this->generatePurchaseNumber(),
                'cart' => $cartPayload,
                'totalQty' => $cartPayload['totalQty'],

                // Customer info
                'customer_name' => $addressData['customer_name'],
                'customer_email' => $addressData['customer_email'],
                'customer_phone' => $addressData['customer_phone'],
                'customer_address' => $addressData['customer_address'],
                'customer_city' => $addressData['customer_city'],
                'customer_state' => $addressData['customer_state'] ?? '',
                'customer_zip' => $addressData['customer_zip'] ?? '',
                'customer_country' => $addressData['customer_country'] ?? '',

                // Pricing
                'pay_amount' => $this->priceCalculator->convertToBase($totals['grand_total']),
                'shipping_cost' => $shippingData['shipping_cost'],
                'packing_cost' => $shippingData['packing_cost'],
                'tax' => $totals['tax_amount'],
                'tax_location' => $addressData['tax_location'] ?? '',
                'discount_amount' => $discountData['amount'] ?? 0,
                'discount_code' => $discountData['code'] ?? '',
                'discount_code_id' => $discountData['code_id'] ?? null,
                'wallet_price' => $paymentData['wallet_price'] ?? 0,

                // Currency
                'currency_name' => $currency->name,
                'currency_sign' => $currency->sign,
                'currency_value' => $currency->value,

                // Payment
                'method' => $paymentData['method'] ?? null,
                'pay_id' => $paymentData['pay_id'] ?? null,
                'txnid' => $paymentData['txnid'] ?? null,
                'charge_id' => $paymentData['charge_id'] ?? null,
                'payment_status' => $paymentData['payment_status'] ?? 'pending',

                // Status
                'status' => 'pending',
                'shipping' => 'shipto',
                'shipping_title' => $shippingData['shipping_name'] ?? $shippingData['courier_name'] ?? '',

                // Multi-merchant
                'merchant_ids' => json_encode([$merchantId]),
                'merchant_shipping_id' => $shippingData['shipping_id'] ?? 0,
                'merchant_packing_id' => $shippingData['packing_id'] ?? 0,

                // Courier
                'couriers' => $shippingData['delivery_type'] === 'local_courier' ? json_encode([
                    'courier_id' => $shippingData['courier_id'],
                    'courier_fee' => $shippingData['courier_fee'],
                    'service_area_id' => $shippingData['service_area_id'] ?? null,
                ]) : null,
            ]);
            $purchase->save();

            // Create merchant purchase record
            $merchantPurchase = $this->createMerchantPurchase(
                $purchase,
                $merchantId,
                $cartPayload,
                $shippingData,
                $totals,
                $paymentData
            );

            // Create courier delivery if needed
            if ($shippingData['delivery_type'] === 'local_courier' && $shippingData['courier_id']) {
                $this->createCourierDelivery($purchase, $merchantId, $shippingData, $addressData, $paymentData);
            }

            // Create tracking record
            $purchase->tracks()->create([
                'title' => 'Pending',
                'text' => __('Your order has been placed and is awaiting confirmation.'),
            ]);

            DB::commit();

            // Send notifications
            $this->sendNotifications($purchase, $merchant);

            // Store temp data for success page
            $this->sessionManager->storeTempPurchase($purchase);
            $this->sessionManager->storeTempCart($cartPayload);

            // Clean up cart and session
            $this->cartService->removeMerchantItems($merchantId);
            $this->sessionManager->clearAllCheckoutData($merchantId);

            return [
                'success' => true,
                'purchase' => $purchase,
                'merchant_purchase' => $merchantPurchase,
                'purchase_number' => $purchase->purchase_number,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => 'creation_failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create merchant purchase record
     */
    protected function createMerchantPurchase(
        Purchase $purchase,
        int $merchantId,
        array $cartPayload,
        array $shippingData,
        array $totals,
        array $paymentData
    ): MerchantPurchase {
        $merchant = User::find($merchantId);
        $commissionRate = $merchant->operator_commission ?? 0;
        $grossPrice = $totals['items_total'];
        $commissionAmount = ($grossPrice * $commissionRate) / 100;
        $netAmount = $grossPrice - $commissionAmount - $totals['tax_amount'];

        $merchantPurchase = new MerchantPurchase();
        $merchantPurchase->fill([
            'purchase_id' => $purchase->id,
            'user_id' => $merchantId,
            'purchase_number' => $purchase->purchase_number,
            'cart' => $cartPayload,
            'qty' => $cartPayload['totalQty'],
            'price' => $grossPrice,
            'commission_amount' => $commissionAmount,
            'tax_amount' => $totals['tax_amount'],
            'net_amount' => $netAmount,
            'shipping_cost' => $shippingData['shipping_cost'],
            'packing_cost' => $shippingData['packing_cost'],
            'courier_fee' => $shippingData['courier_fee'] ?? 0,

            // Ownership tracking
            'payment_owner_id' => $merchantId,
            'shipping_owner_id' => $merchantId,
            'packing_owner_id' => $merchantId,

            // Payment type
            'payment_type' => 'merchant',
            'money_received_by' => $shippingData['delivery_type'] === 'local_courier' ? 'courier' : 'merchant',
            'payment_gateway_id' => $paymentData['pay_id'] ?? null,

            // Shipping type
            'shipping_type' => $shippingData['delivery_type'] === 'local_courier' ? 'courier' : 'merchant',
            'shipping_id' => $shippingData['shipping_id'] ?? null,
            'courier_id' => $shippingData['courier_id'] ?? null,

            // Settlement
            'settlement_status' => 'pending',
        ]);
        $merchantPurchase->save();

        return $merchantPurchase;
    }

    /**
     * Create courier delivery record
     */
    protected function createCourierDelivery(
        Purchase $purchase,
        int $merchantId,
        array $shippingData,
        array $addressData,
        array $paymentData
    ): void {
        // Determine payment method for courier (cod or online)
        $paymentMethod = strtolower($paymentData['method'] ?? '');
        $isCod = in_array($paymentMethod, ['cod', 'cash on delivery']);

        DeliveryCourier::create([
            'purchase_id' => $purchase->id,
            'merchant_id' => $merchantId,
            'courier_id' => $shippingData['courier_id'],
            'service_area_id' => $shippingData['service_area_id'] ?? null,
            'merchant_location_id' => $shippingData['merchant_location_id'] ?? null,
            'delivery_fee' => $shippingData['courier_fee'],
            'purchase_amount' => $purchase->pay_amount,
            'cod_amount' => $isCod ? $purchase->pay_amount : 0,
            'payment_method' => $isCod ? DeliveryCourier::PAYMENT_COD : DeliveryCourier::PAYMENT_ONLINE,
            'status' => DeliveryCourier::STATUS_PENDING_APPROVAL,
            'fee_status' => DeliveryCourier::FEE_PENDING,
            'settlement_status' => DeliveryCourier::SETTLEMENT_PENDING,
        ]);
    }

    /**
     * Send notifications
     */
    protected function sendNotifications(Purchase $purchase, User $merchant): void
    {
        try {
            $mailer = new MuaadhMailer();

            // Send to customer
            $mailer->sendAutoPurchaseMail([
                'order_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'customer_name' => $purchase->customer_name,
                'customer_email' => $purchase->customer_email,
                'total' => $purchase->pay_amount,
            ], $purchase->customer_email);

            // Send to merchant
            if ($merchant->email) {
                $mailer->sendMerchantPurchaseMail([
                    'order_id' => $purchase->id,
                    'purchase_number' => $purchase->purchase_number,
                    'merchant_name' => $merchant->shop_name ?? $merchant->name,
                ], $merchant->email);
            }
        } catch (\Exception $e) {
            // Log but don't fail the purchase
            \Log::error('Failed to send purchase notification: ' . $e->getMessage());
        }
    }

    /**
     * Generate unique purchase number
     */
    protected function generatePurchaseNumber(): string
    {
        return strtoupper(Str::random(4)) . time();
    }

    /**
     * Update purchase payment status
     */
    public function updatePaymentStatus(Purchase $purchase, string $status, array $paymentData = []): bool
    {
        try {
            $purchase->update([
                'payment_status' => $status,
                'txnid' => $paymentData['txnid'] ?? $purchase->txnid,
                'charge_id' => $paymentData['charge_id'] ?? $purchase->charge_id,
            ]);

            // Update merchant purchase too
            MerchantPurchase::where('purchase_id', $purchase->id)
                ->update(['payment_status' => $status]);

            // Add tracking
            if ($status === 'Completed') {
                $purchase->tracks()->create([
                    'title' => 'Payment Confirmed',
                    'text' => __('Payment has been confirmed.'),
                ]);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to update payment status: ' . $e->getMessage());
            return false;
        }
    }
}
