<?php

namespace App\Services\MerchantCheckout;

use App\Models\Purchase;
use App\Models\MerchantPurchase;
use App\Models\User;
use App\Models\Currency;
use App\Models\DeliveryCourier;
use App\Classes\MuaadhMailer;
use App\Traits\SavesCustomerShippingChoice;
use App\Services\PaymentAccountingService;
use App\Services\AccountLedgerService;
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
    use SavesCustomerShippingChoice;
    protected MerchantCartService $cartService;
    protected MerchantSessionManager $sessionManager;
    protected MerchantPriceCalculator $priceCalculator;
    protected PaymentAccountingService $accountingService;
    protected AccountLedgerService $ledgerService;

    public function __construct(
        MerchantCartService $cartService,
        MerchantSessionManager $sessionManager,
        MerchantPriceCalculator $priceCalculator,
        PaymentAccountingService $accountingService,
        ?AccountLedgerService $ledgerService = null
    ) {
        $this->cartService = $cartService;
        $this->sessionManager = $sessionManager;
        $this->priceCalculator = $priceCalculator;
        $this->accountingService = $accountingService;
        $this->ledgerService = $ledgerService ?? app(AccountLedgerService::class);
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
                'customer_latitude' => $addressData['latitude'] ?? null,
                'customer_longitude' => $addressData['longitude'] ?? null,

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

                // ✅ اختيار العميل لشركة الشحن
                'customer_shipping_choice' => $this->buildCustomerShippingChoice($merchantId, $shippingData),
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
     *
     * Integrates with PaymentAccountingService for debt tracking
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

        // Determine payment method (cod vs online)
        $paymentMethod = strtolower($paymentData['method'] ?? '');
        $isCod = in_array($paymentMethod, ['cod', 'cash on delivery']);

        // Determine delivery method
        $deliveryType = $shippingData['delivery_type'] ?? null;
        $deliveryMethod = match ($deliveryType) {
            'local_courier' => MerchantPurchase::DELIVERY_LOCAL_COURIER,
            'shipping_company', 'tryoto' => MerchantPurchase::DELIVERY_SHIPPING_COMPANY,
            'pickup' => MerchantPurchase::DELIVERY_PICKUP,
            default => MerchantPurchase::DELIVERY_NONE,
        };

        // Determine delivery provider
        $deliveryProvider = match ($deliveryType) {
            'local_courier' => 'courier_' . ($shippingData['courier_id'] ?? 0),
            default => $shippingData['shipping_provider'] ?? null,
        };

        // === Calculate Debt Ledger via Accounting Service ===
        $accountingData = $this->accountingService->calculateDebtLedger([
            'payment_method' => $isCod ? 'cod' : 'online',
            'payment_owner_id' => $merchantId, // Merchant receives payment directly in this checkout
            'delivery_method' => $deliveryMethod,
            'delivery_provider' => $deliveryProvider,
            'price' => $grossPrice,
            'commission_amount' => $commissionAmount,
            'tax_amount' => $totals['tax_amount'],
            'shipping_cost' => $shippingData['shipping_cost'] ?? 0,
            'courier_fee' => $shippingData['courier_fee'] ?? 0,
            'platform_shipping_fee' => $shippingData['platform_shipping_fee'] ?? 0,
            'platform_packing_fee' => $shippingData['platform_packing_fee'] ?? 0,
        ]);

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

            // Payment type (who owns the payment gateway: merchant or platform)
            // For COD, platform handles collection through courier
            'payment_type' => $isCod ? 'platform' : ($this->determinePaymentType($merchantId, $paymentData)),
            'payment_gateway_id' => $paymentData['pay_id'] ?? null,

            // Shipping type
            'shipping_type' => $deliveryType === 'local_courier' ? 'courier' : 'merchant',
            'shipping_id' => $shippingData['shipping_id'] ?? null,
            'courier_id' => $shippingData['courier_id'] ?? null,

            // === Accounting Fields from Service ===
            'money_holder' => $accountingData['money_holder'],
            'delivery_method' => $accountingData['delivery_method'],
            'delivery_provider' => $accountingData['delivery_provider'],
            'cod_amount' => $accountingData['cod_amount'],
            'collection_status' => $accountingData['collection_status'],

            // === Debt Ledger ===
            'platform_owes_merchant' => $accountingData['platform_owes_merchant'],
            'merchant_owes_platform' => $accountingData['merchant_owes_platform'],
            'courier_owes_merchant' => $accountingData['courier_owes_merchant'],
            'courier_owes_platform' => $accountingData['courier_owes_platform'],
            'shipping_company_owes_merchant' => $accountingData['shipping_company_owes_merchant'],
            'shipping_company_owes_platform' => $accountingData['shipping_company_owes_platform'],

            // Settlement
            'settlement_status' => 'pending',
        ]);
        $merchantPurchase->save();

        // === Record Ledger Entries ===
        // تسجيل الديون في سجل الحركات المالية
        try {
            $this->ledgerService->recordDebtsForMerchantPurchase($merchantPurchase);
        } catch (\Exception $e) {
            // Log but don't fail - accounting entries can be reconciled later
            \Log::warning('Failed to record ledger entries for purchase #' . $merchantPurchase->purchase_number . ': ' . $e->getMessage());
        }

        return $merchantPurchase;
    }

    /**
     * Create courier delivery record
     *
     * ⚠️ WARNING: cod_amount is calculated via PaymentAccountingService!
     *    DO NOT calculate it manually here. Use accountingService->prepareDeliveryCourierData()
     */
    protected function createCourierDelivery(
        Purchase $purchase,
        int $merchantId,
        array $shippingData,
        array $addressData,
        array $paymentData
    ): void {
        $paymentMethod = $paymentData['method'] ?? 'online';

        // ✅ Use centralized service for cod_amount calculation
        // @see PaymentAccountingService::prepareDeliveryCourierData()
        $courierData = $this->accountingService->prepareDeliveryCourierData(
            $purchase,
            $merchantId,
            $shippingData,
            $paymentMethod
        );

        DeliveryCourier::create($courierData);
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

    /**
     * ✅ بناء اختيار العميل لشركة الشحن من بيانات الشحن المحفوظة
     *
     * @param int $merchantId
     * @param array $shippingData
     * @return array|null
     */
    protected function buildCustomerShippingChoice(int $merchantId, array $shippingData): ?array
    {
        $deliveryType = $shippingData['delivery_type'] ?? null;
        $shippingProvider = $shippingData['shipping_provider'] ?? null;

        // ✅ حالة 1: توصيل محلي (كوريير)
        if ($deliveryType === 'local_courier') {
            return [
                $merchantId => [
                    'provider' => 'local_courier',
                    'courier_id' => $shippingData['courier_id'] ?? null,
                    'courier_name' => $shippingData['courier_name'] ?? 'Courier',
                    'price' => (float)($shippingData['courier_fee'] ?? 0),
                    'merchant_location_id' => $shippingData['merchant_location_id'] ?? null,
                    'service_area_id' => $shippingData['service_area_id'] ?? null,
                    'selected_at' => now()->toIso8601String(),
                ]
            ];
        }

        // ✅ حالة 2: Tryoto (API provider)
        // shipping_provider = 'tryoto' AND shipping_id = "deliveryOptionId#companyName#price"
        if ($shippingProvider === 'tryoto') {
            $shippingIdValue = $shippingData['shipping_id'] ?? '';

            // استخراج البيانات من القيمة المركبة "123456#Omni Llama#15"
            $deliveryOptionId = $shippingIdValue;
            $companyName = $shippingData['shipping_name'] ?? '';
            $price = (float)($shippingData['shipping_cost'] ?? 0);

            if (is_string($shippingIdValue) && strpos($shippingIdValue, '#') !== false) {
                $parts = explode('#', $shippingIdValue);
                $deliveryOptionId = $parts[0] ?? $shippingIdValue;
                $companyName = $parts[1] ?? $companyName;
                $price = isset($parts[2]) ? (float)$parts[2] : $price;
            }

            // ✅ الحصول على المدينة المدعومة من الجلسة (تم تحديدها عند جلب خيارات الشحن)
            $addressData = $this->sessionManager->getAddressData($merchantId);
            $shippingCity = $addressData['shipping_city'] ?? $addressData['customer_city'] ?? null;

            return [
                $merchantId => [
                    'provider' => 'tryoto',
                    'delivery_option_id' => $deliveryOptionId,
                    'company_name' => $companyName,
                    'price' => $price,
                    'original_price' => (float)($shippingData['original_shipping_cost'] ?? $price),
                    'is_free' => $shippingData['is_free_shipping'] ?? false,
                    'shipping_city' => $shippingCity, // ✅ المدينة المدعومة للشحن
                    'selected_at' => now()->toIso8601String(),
                ]
            ];
        }

        // ✅ حالة 3: شحن عادي (من جدول shippings)
        if (!empty($shippingData['shipping_id']) && is_numeric($shippingData['shipping_id'])) {
            // جلب بيانات الشحن من الجدول
            $shipping = \DB::table('shippings')->find($shippingData['shipping_id']);

            return [
                $merchantId => [
                    'provider' => $shipping->provider ?? $shippingProvider, // من الجدول مباشرة
                    'shipping_id' => (int)$shippingData['shipping_id'],
                    'title' => $shippingData['shipping_name'] ?? $shipping->title ?? null,
                    'price' => (float)($shippingData['shipping_cost'] ?? $shipping->price ?? 0),
                    'selected_at' => now()->toIso8601String(),
                ]
            ];
        }

        return null;
    }

    /**
     * Determine payment type (who owns the payment gateway)
     *
     * @param int $merchantId
     * @param array $paymentData
     * @return string 'merchant' or 'platform'
     */
    protected function determinePaymentType(int $merchantId, array $paymentData): string
    {
        // Check if merchant has their own payment credentials
        $merchantHasGateway = \App\Models\MerchantCredential::where('user_id', $merchantId)
            ->where('is_active', true)
            ->exists();

        // If merchant has active gateway credentials, use 'merchant'
        // Otherwise, platform handles payment
        return $merchantHasGateway ? 'merchant' : 'platform';
    }
}
