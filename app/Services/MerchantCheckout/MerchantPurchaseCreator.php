<?php

namespace App\Services\MerchantCheckout;

use App\Models\Purchase;
use App\Models\MerchantPurchase;
use App\Models\MerchantBranch;
use App\Models\User;
use App\Models\MonetaryUnit;
use App\Models\DeliveryCourier;
use App\Classes\MuaadhMailer;
use App\Traits\SavesCustomerShippingChoice;
use App\Services\PaymentAccountingService;
use App\Services\AccountLedgerService;
use App\Services\AccountingEntryService;
use App\Services\Cart\MerchantCartManager;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Purchase creation for Branch Checkout
 *
 * Handles final purchase record creation and notifications
 * NOTE: Checkout is now branch-scoped, but payment/shipping ownership
 *       is still determined by merchant (branch->user)
 */
class MerchantPurchaseCreator
{
    use SavesCustomerShippingChoice;
    protected MerchantCartManager $cartService;
    protected MerchantSessionManager $sessionManager;
    protected MerchantPriceCalculator $priceCalculator;
    protected PaymentAccountingService $accountingService;
    protected AccountLedgerService $ledgerService;
    protected AccountingEntryService $entryService;

    public function __construct(
        MerchantCartManager $cartService,
        MerchantSessionManager $sessionManager,
        MerchantPriceCalculator $priceCalculator,
        PaymentAccountingService $accountingService,
        ?AccountLedgerService $ledgerService = null,
        ?AccountingEntryService $entryService = null
    ) {
        $this->cartService = $cartService;
        $this->sessionManager = $sessionManager;
        $this->priceCalculator = $priceCalculator;
        $this->accountingService = $accountingService;
        $this->ledgerService = $ledgerService ?? app(AccountLedgerService::class);
        $this->entryService = $entryService ?? app(AccountingEntryService::class);
    }

    /**
     * Create purchase from checkout data (branch-scoped)
     */
    public function createPurchase(int $branchId, array $paymentData): array
    {
        // Get branch and merchant
        $branch = MerchantBranch::with('user')->find($branchId);
        if (!$branch) {
            return [
                'success' => false,
                'error' => 'invalid_branch',
                'message' => __('Invalid branch'),
            ];
        }

        $merchant = $branch->user;
        $merchantId = $merchant->id;

        // Get checkout data (branch-scoped session)
        $addressData = $this->sessionManager->getAddressData($branchId);
        $shippingData = $this->sessionManager->getShippingData($branchId);
        $cartPayload = $this->cartService->buildBranchCartPayload($branchId);

        if (!$addressData || !$shippingData) {
            return [
                'success' => false,
                'error' => 'incomplete_checkout',
                'message' => __('Checkout data is incomplete'),
            ];
        }

        $user = Auth::user();
        $currency = $this->priceCalculator->getMonetaryUnit();

        // Calculate final totals
        $totals = $this->priceCalculator->calculateTotals($cartPayload['items'], [
            'tax_rate' => $addressData['tax_rate'] ?? 0,
            'shipping_cost' => $shippingData['shipping_cost'] ?? 0,
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
                'tax' => $totals['tax_amount'],
                'tax_location' => $addressData['tax_location'] ?? '',

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
                'shipping_name' => $shippingData['shipping_name'] ?? $shippingData['courier_name'] ?? '',

                // Multi-merchant (single merchant per branch checkout)
                'merchant_ids' => json_encode([$merchantId]),
                'merchant_shipping_id' => $shippingData['shipping_id'] ?? 0,

                // Courier
                'couriers' => $shippingData['delivery_type'] === 'local_courier' ? json_encode([
                    'courier_id' => $shippingData['courier_id'],
                    'courier_fee' => $shippingData['courier_fee'],
                    'service_area_id' => $shippingData['service_area_id'] ?? null,
                ]) : null,

                // Customer shipping choice (branch-scoped)
                'customer_shipping_choice' => $this->buildCustomerShippingChoice($branchId, $merchantId, $shippingData),
            ]);
            $purchase->save();

            // Create merchant purchase record (with branch_id)
            $merchantPurchase = $this->createMerchantPurchase(
                $purchase,
                $branchId,
                $merchantId,
                $cartPayload,
                $shippingData,
                $totals,
                $paymentData
            );

            // Create courier delivery if needed
            if ($shippingData['delivery_type'] === 'local_courier' && $shippingData['courier_id']) {
                $this->createCourierDelivery($purchase, $branchId, $merchantId, $shippingData, $addressData, $paymentData);
            }

            // Create tracking record
            $purchase->tracks()->create([
                'name' => 'Pending',
                'text' => __('Your order has been placed and is awaiting confirmation.'),
            ]);

            DB::commit();

            // Send notifications
            $this->sendNotifications($purchase, $merchant);

            // Store temp data for success page
            $this->sessionManager->storeTempPurchase($purchase);
            $this->sessionManager->storeTempCart($cartPayload);

            // Clean up cart and session (branch-scoped)
            $this->cartService->clearBranch($branchId);
            $this->sessionManager->clearAllCheckoutData($branchId);

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
     * Create merchant purchase record (with branch_id)
     *
     * Integrates with PaymentAccountingService for debt tracking
     */
    protected function createMerchantPurchase(
        Purchase $purchase,
        int $branchId,
        int $merchantId,
        array $cartPayload,
        array $shippingData,
        array $totals,
        array $paymentData
    ): MerchantPurchase {
        // === حساب العمولة من MerchantCommission (المصدر الموحد) ===
        $commission = \App\Models\MerchantCommission::getOrCreateForMerchant($merchantId);
        $grossPrice = $totals['items_total'];

        // العمولة = ثابت + (نسبة مئوية * السعر)
        $commissionAmount = $commission->is_active
            ? $commission->calculateCommission($grossPrice)
            : 0;

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

        // ═══════════════════════════════════════════════════════════════════
        // OWNERSHIP: Determine who owns each service (merchant or platform)
        // is_platform_provided=true + owner_user_id=0 → Platform owns the service
        // is_platform_provided=false + owner_user_id=merchantId → Merchant owns
        // ═══════════════════════════════════════════════════════════════════
        $shippingOwnerId = $shippingData['owner_user_id'] ?? $merchantId;
        $isPlatformShipping = $shippingData['is_platform_provided'] ?? false;
        $isCourier = ($deliveryType === 'local_courier') || ($deliveryMethod === MerchantPurchase::DELIVERY_LOCAL_COURIER);

        // ═══════════════════════════════════════════════════════════════════
        // PAYMENT OWNER: depends on payment method and shipping owner
        //
        // للدفع عند الاستلام (COD):
        // - إذا الشحن تبع التاجر → COD تبع التاجر
        // - إذا الشحن تبع المنصة → COD تبع المنصة
        // - إذا الشحن عبر المندوب → COD تبع المنصة (كل ما يخص المندوب = منصة)
        //
        // للدفع الإلكتروني:
        // - يعتمد على من يملك بوابة الدفع
        // ═══════════════════════════════════════════════════════════════════
        if ($isCod) {
            // COD ownership follows shipping ownership
            if ($isCourier) {
                // Courier delivery → Platform handles COD collection
                $paymentOwnerId = 0;
            } elseif ($isPlatformShipping || $shippingOwnerId === 0) {
                // Platform shipping → Platform handles COD
                $paymentOwnerId = 0;
            } else {
                // Merchant's own shipping → Merchant handles COD
                $paymentOwnerId = $shippingOwnerId;
            }
        } else {
            // Online payment → depends on whose gateway is used
            $paymentOwnerId = $this->determinePaymentOwnerId($merchantId, $paymentData);
        }

        // === Calculate Debt Ledger via Accounting Service ===
        $accountingData = $this->accountingService->calculateDebtLedger([
            'payment_method' => $isCod ? 'cod' : 'online',
            'payment_owner_id' => $paymentOwnerId,
            'shipping_owner_id' => $shippingOwnerId,
            'is_platform_shipping' => $isPlatformShipping,
            'delivery_method' => $deliveryMethod,
            'delivery_provider' => $deliveryProvider,
            'price' => $grossPrice,
            'commission_amount' => $commissionAmount,
            'tax_amount' => $totals['tax_amount'],
            'shipping_cost' => $shippingData['shipping_cost'] ?? 0,
            'courier_fee' => $shippingData['courier_fee'] ?? 0,
            'platform_shipping_fee' => $shippingData['platform_shipping_fee'] ?? 0,
        ]);

        $merchantPurchase = new MerchantPurchase();
        $merchantPurchase->fill([
            'purchase_id' => $purchase->id,
            'user_id' => $merchantId,
            'merchant_branch_id' => $branchId, // ✅ Branch ID stored
            'purchase_number' => $purchase->purchase_number,
            'cart' => $cartPayload,
            'qty' => $cartPayload['totalQty'],
            'price' => $grossPrice,
            'commission_amount' => $commissionAmount,
            'tax_amount' => $totals['tax_amount'],
            'net_amount' => $netAmount,
            'shipping_cost' => $shippingData['shipping_cost'],
            'courier_fee' => $shippingData['courier_fee'] ?? 0,

            // ═══════════════════════════════════════════════════════════════════
            // OWNERSHIP: Who owns each service (0=platform, >0=merchant/user)
            // This determines who receives the money and who pays fees
            // ═══════════════════════════════════════════════════════════════════
            'payment_owner_id' => $paymentOwnerId,
            'shipping_owner_id' => $shippingOwnerId,

            // Payment method and type
            'payment_method' => $isCod ? 'cod' : 'online',
            'payment_type' => $paymentOwnerId > 0 ? 'merchant' : 'platform',
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
            'courier_owes_platform' => $accountingData['courier_owes_platform'],
            'shipping_company_owes_merchant' => $accountingData['shipping_company_owes_merchant'],
            'shipping_company_owes_platform' => $accountingData['shipping_company_owes_platform'],

            // Settlement
            'settlement_status' => 'pending',
        ]);
        $merchantPurchase->save();

        // === Record Full Accounting Entries ===
        try {
            $this->entryService->createOrderEntries($merchantPurchase);
        } catch (\Exception $e) {
            \Log::warning('Failed to record accounting entries for purchase #' . $merchantPurchase->purchase_number . ': ' . $e->getMessage());

            try {
                $this->ledgerService->recordDebtsForMerchantPurchase($merchantPurchase);
            } catch (\Exception $fallbackEx) {
                \Log::error('Fallback ledger also failed: ' . $fallbackEx->getMessage());
            }
        }

        return $merchantPurchase;
    }

    /**
     * Create courier delivery record
     */
    protected function createCourierDelivery(
        Purchase $purchase,
        int $branchId,
        int $merchantId,
        array $shippingData,
        array $addressData,
        array $paymentData
    ): void {
        $paymentMethod = $paymentData['method'] ?? 'online';

        $courierData = $this->accountingService->prepareDeliveryCourierData(
            $purchase,
            $merchantId,
            $shippingData,
            $paymentMethod
        );

        // Add branch_id to courier data
        $courierData['merchant_branch_id'] = $branchId;

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
                    'name' => 'Payment Confirmed',
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
     * Build customer shipping choice (branch-scoped)
     */
    protected function buildCustomerShippingChoice(int $branchId, int $merchantId, array $shippingData): ?array
    {
        $deliveryType = $shippingData['delivery_type'] ?? null;
        $shippingProvider = $shippingData['shipping_provider'] ?? null;

        // Case 1: Local courier
        if ($deliveryType === 'local_courier') {
            return [
                $merchantId => [
                    'provider' => 'local_courier',
                    'courier_id' => $shippingData['courier_id'] ?? null,
                    'courier_name' => $shippingData['courier_name'] ?? 'Courier',
                    'price' => (float)($shippingData['courier_fee'] ?? 0),
                    'merchant_branch_id' => $branchId,
                    'service_area_id' => $shippingData['service_area_id'] ?? null,
                    'selected_at' => now()->toIso8601String(),
                ]
            ];
        }

        // Case 2: Tryoto (API provider)
        if ($shippingProvider === 'tryoto') {
            $shippingIdValue = $shippingData['shipping_id'] ?? '';

            $deliveryOptionId = $shippingIdValue;
            $companyName = $shippingData['shipping_name'] ?? '';
            $price = (float)($shippingData['shipping_cost'] ?? 0);

            if (is_string($shippingIdValue) && strpos($shippingIdValue, '#') !== false) {
                $parts = explode('#', $shippingIdValue);
                $deliveryOptionId = $parts[0] ?? $shippingIdValue;
                $companyName = $parts[1] ?? $companyName;
                $price = isset($parts[2]) ? (float)$parts[2] : $price;
            }

            $addressData = $this->sessionManager->getAddressData($branchId);
            $shippingCity = $addressData['shipping_city'] ?? $addressData['customer_city'] ?? null;

            return [
                $merchantId => [
                    'provider' => 'tryoto',
                    'delivery_option_id' => $deliveryOptionId,
                    'company_name' => $companyName,
                    'price' => $price,
                    'original_price' => (float)($shippingData['original_shipping_cost'] ?? $price),
                    'is_free' => $shippingData['is_free_shipping'] ?? false,
                    'shipping_city' => $shippingCity,
                    'merchant_branch_id' => $branchId,
                    'selected_at' => now()->toIso8601String(),
                ]
            ];
        }

        // Case 3: Regular shipping
        if (!empty($shippingData['shipping_id']) && is_numeric($shippingData['shipping_id'])) {
            $shipping = \DB::table('shippings')->find($shippingData['shipping_id']);

            return [
                $merchantId => [
                    'provider' => $shipping->provider ?? $shippingProvider,
                    'shipping_id' => (int)$shippingData['shipping_id'],
                    'name' => $shippingData['shipping_name'] ?? $shipping->name ?? null,
                    'price' => (float)($shippingData['shipping_cost'] ?? $shipping->price ?? 0),
                    'merchant_branch_id' => $branchId,
                    'selected_at' => now()->toIso8601String(),
                ]
            ];
        }

        return null;
    }

    /**
     * Determine who owns the payment gateway
     */
    protected function determinePaymentOwnerId(int $merchantId, array $paymentData): int
    {
        if (isset($paymentData['payment_owner_id'])) {
            return (int) $paymentData['payment_owner_id'];
        }

        $credentialService = app(\App\Services\MerchantCredentialService::class);

        $paymentKeyword = $paymentData['keyword'] ?? $paymentData['method'] ?? null;
        if ($paymentKeyword) {
            $normalizedKeyword = strtolower(str_replace(' ', '', $paymentKeyword));
            if (in_array($normalizedKeyword, ['myfatoorah', 'stripe', 'paypal', 'razorpay', 'tap'])) {
                if ($credentialService->hasPaymentCredentialsFor($merchantId, $normalizedKeyword)) {
                    return $merchantId;
                }
            }
        }

        if ($credentialService->hasPaymentCredentials($merchantId)) {
            return $merchantId;
        }

        return 0;
    }

    /**
     * @deprecated Use determinePaymentOwnerId() for owner ID
     */
    protected function determinePaymentType(int $merchantId, array $paymentData): string
    {
        $ownerId = $this->determinePaymentOwnerId($merchantId, $paymentData);
        return $ownerId > 0 ? 'merchant' : 'platform';
    }
}
