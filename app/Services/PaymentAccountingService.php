<?php

namespace App\Services;

use App\Models\MerchantPurchase;
use App\Models\Purchase;

/**
 * PaymentAccountingService
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 * CENTRALIZED ACCOUNTING SERVICE - ALL DEBT OPERATIONS MUST PASS THROUGH HERE
 * ═══════════════════════════════════════════════════════════════════════════════
 *
 * ARCHITECTURAL RULES:
 * 1. NO direct updates to debt columns outside this service
 * 2. collection_status is INDEPENDENT from delivery_status
 * 3. All amounts are PRE-COMPUTED and STORED, never calculated on-the-fly
 *
 * DEBT PARTIES:
 * - Platform ↔ Merchant
 * - Courier ↔ Merchant/Platform
 * - Shipping Company ↔ Merchant/Platform
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 * COD + SHIPPING COMPANY - EXPLICIT RULES (NO "varies")
 * ═══════════════════════════════════════════════════════════════════════════════
 *
 * RULE 1: WHO RECEIVES COD?
 *   → Shipping company ALWAYS collects COD from customer
 *   → money_holder = 'shipping_company' at checkout
 *
 * RULE 2: WHEN DOES AMOUNT BECOME DEBT?
 *   → IMMEDIATELY at checkout: shipping_company_owes_X = price
 *   → Debt is recorded BEFORE delivery happens
 *
 * RULE 3: DEBT DESTINATION (based on payment_owner_id):
 *   → payment_owner_id = 0 (Platform gateway):
 *       shipping_company_owes_platform = price
 *       platform_owes_merchant = net_amount
 *   → payment_owner_id > 0 (Merchant gateway):
 *       shipping_company_owes_merchant = price
 *       merchant_owes_platform = commission + tax + platform_services
 *
 * RULE 4: COLLECTION STATUS TRANSITIONS:
 *   → pending     → Waiting for shipping company to deliver and collect COD
 *   → collected   → Shipping company confirmed delivery + COD receipt
 *   → failed      → Delivery failed, no COD collected
 *
 * RULE 5: SETTLEMENT (debt → paid):
 *   → settlement_status = 'pending' → Debt exists, not yet paid
 *   → settlement_status = 'settled' → Debt paid, cleared
 *   → settled_at = timestamp of payment
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 */
class PaymentAccountingService
{
    /**
     * Calculate all accounting fields for a MerchantPurchase at checkout
     *
     * @param array $data Contains: payment_method, payment_owner_id, delivery_method,
     *                    delivery_provider, price, commission, tax, shipping_cost, courier_fee
     * @return array All accounting fields to be saved
     */
    public function calculateDebtLedger(array $data): array
    {
        $paymentMethod = $data['payment_method'] ?? 'online'; // 'online' | 'cod'
        $paymentOwnerId = $data['payment_owner_id'] ?? 0; // 0 = platform, >0 = merchant
        $deliveryMethod = $data['delivery_method'] ?? null;
        $deliveryProvider = $data['delivery_provider'] ?? null;

        // Financial amounts
        $price = (float) ($data['price'] ?? 0);
        $commission = (float) ($data['commission_amount'] ?? 0);
        $tax = (float) ($data['tax_amount'] ?? 0);
        $shippingCost = (float) ($data['shipping_cost'] ?? 0);
        $courierFee = (float) ($data['courier_fee'] ?? 0);
        $platformShippingFee = (float) ($data['platform_shipping_fee'] ?? 0);
        $platformPackingFee = (float) ($data['platform_packing_fee'] ?? 0);

        // Net amount = price - commission - tax
        $netAmount = $price - $commission - $tax;

        // Determine delivery fee (courier_fee or shipping_cost)
        $deliveryFee = $deliveryMethod === MerchantPurchase::DELIVERY_LOCAL_COURIER
            ? $courierFee
            : $shippingCost;

        // ✅ COD amount for merchant_purchases (accounting purposes)
        // Uses centralized calculation - see calculateMerchantPurchaseCodAmount()
        $codAmount = $this->calculateMerchantPurchaseCodAmount($paymentMethod, $price, $deliveryFee);

        // Platform services total
        $platformServices = $platformShippingFee + $platformPackingFee;

        // Initialize debt ledger
        $ledger = [
            'platform_owes_merchant' => 0,
            'merchant_owes_platform' => 0,
            'courier_owes_merchant' => 0,
            'courier_owes_platform' => 0,
            'shipping_company_owes_merchant' => 0,
            'shipping_company_owes_platform' => 0,
            'cod_amount' => $codAmount,
            'money_holder' => MerchantPurchase::MONEY_HOLDER_PENDING,
            'delivery_method' => $deliveryMethod,
            'delivery_provider' => $deliveryProvider,
            'collection_status' => MerchantPurchase::COLLECTION_NOT_APPLICABLE,
        ];

        // Determine debts based on payment scenario
        if ($paymentMethod === 'online') {
            // === ONLINE PAYMENT ===
            $ledger = $this->calculateOnlinePaymentDebts(
                $ledger,
                $paymentOwnerId,
                $netAmount,
                $commission,
                $tax,
                $platformServices
            );
        } else {
            // === COD PAYMENT ===
            $ledger = $this->calculateCodPaymentDebts(
                $ledger,
                $paymentOwnerId,
                $deliveryMethod,
                $price,
                $netAmount,
                $commission,
                $tax,
                $platformServices,
                $codAmount
            );
        }

        return $ledger;
    }

    /**
     * Calculate debts for Online Payment scenario
     */
    protected function calculateOnlinePaymentDebts(
        array $ledger,
        int $paymentOwnerId,
        float $netAmount,
        float $commission,
        float $tax,
        float $platformServices
    ): array {
        if ($paymentOwnerId === 0) {
            // Platform received payment
            $ledger['money_holder'] = MerchantPurchase::MONEY_HOLDER_PLATFORM;
            $ledger['platform_owes_merchant'] = $netAmount;
            $ledger['merchant_owes_platform'] = 0;
        } else {
            // Merchant received payment directly
            $ledger['money_holder'] = MerchantPurchase::MONEY_HOLDER_MERCHANT;
            $ledger['platform_owes_merchant'] = 0;
            $ledger['merchant_owes_platform'] = $commission + $tax + $platformServices;
        }

        return $ledger;
    }

    /**
     * Calculate debts for COD Payment scenario
     */
    protected function calculateCodPaymentDebts(
        array $ledger,
        int $paymentOwnerId,
        ?string $deliveryMethod,
        float $price,
        float $netAmount,
        float $commission,
        float $tax,
        float $platformServices,
        float $codAmount
    ): array {
        $ledger['collection_status'] = MerchantPurchase::COLLECTION_PENDING;

        if ($deliveryMethod === MerchantPurchase::DELIVERY_LOCAL_COURIER) {
            // === COD via Local Courier ===
            $ledger['money_holder'] = MerchantPurchase::MONEY_HOLDER_COURIER;

            if ($paymentOwnerId === 0) {
                // Platform gateway - courier owes platform, platform owes merchant
                $ledger['courier_owes_platform'] = $price;
                $ledger['platform_owes_merchant'] = $netAmount;
            } else {
                // Merchant gateway - courier owes merchant directly
                $ledger['courier_owes_merchant'] = $price;
                $ledger['merchant_owes_platform'] = $commission + $tax + $platformServices;
            }

        } elseif ($deliveryMethod === MerchantPurchase::DELIVERY_SHIPPING_COMPANY) {
            // === COD via Shipping Company ===
            $ledger['money_holder'] = MerchantPurchase::MONEY_HOLDER_SHIPPING;

            if ($paymentOwnerId === 0) {
                // Platform gateway - shipping company owes platform
                $ledger['shipping_company_owes_platform'] = $price;
                $ledger['platform_owes_merchant'] = $netAmount;
            } else {
                // Merchant gateway - shipping company owes merchant
                $ledger['shipping_company_owes_merchant'] = $price;
                $ledger['merchant_owes_platform'] = $commission + $tax + $platformServices;
            }

        } else {
            // === COD with other delivery methods (shouldn't happen often) ===
            // Treat as if merchant receives COD directly
            $ledger['money_holder'] = MerchantPurchase::MONEY_HOLDER_MERCHANT;
            $ledger['merchant_owes_platform'] = $commission + $tax + $platformServices;
        }

        return $ledger;
    }

    /**
     * Mark COD as collected by courier
     */
    public function markCollectedByCourier(MerchantPurchase $mp, int $courierId): void
    {
        $mp->update([
            'collection_status' => MerchantPurchase::COLLECTION_COLLECTED,
            'collected_at' => now(),
            'collected_by' => 'courier_' . $courierId,
        ]);

        // Also update parent purchase if all merchant purchases collected
        $this->updatePurchasePaymentStatusIfFullyCollected($mp->purchase);
    }

    /**
     * Mark COD as collected by shipping company
     */
    public function markCollectedByShippingCompany(MerchantPurchase $mp, string $provider): void
    {
        $mp->update([
            'collection_status' => MerchantPurchase::COLLECTION_COLLECTED,
            'collected_at' => now(),
            'collected_by' => $provider,
        ]);

        // Also update parent purchase if all merchant purchases collected
        $this->updatePurchasePaymentStatusIfFullyCollected($mp->purchase);
    }

    /**
     * Update Purchase.payment_status when all COD is collected
     */
    protected function updatePurchasePaymentStatusIfFullyCollected(Purchase $purchase): void
    {
        $pendingCollections = $purchase->merchantPurchases()
            ->where('collection_status', MerchantPurchase::COLLECTION_PENDING)
            ->count();

        if ($pendingCollections === 0) {
            $purchase->payment_status = 'Completed';
            $purchase->save();
        }
    }

    /**
     * Get debt summary for a merchant
     */
    public function getMerchantDebtSummary(int $merchantId): array
    {
        $query = MerchantPurchase::where('user_id', $merchantId)
            ->where('settlement_status', '!=', 'settled');

        return [
            'platform_owes_you' => (float) $query->sum('platform_owes_merchant'),
            'you_owe_platform' => (float) $query->sum('merchant_owes_platform'),
            'couriers_owe_you' => (float) $query->sum('courier_owes_merchant'),
            'shipping_companies_owe_you' => (float) $query->sum('shipping_company_owes_merchant'),
            'net_receivable' => (float) $query->sum('platform_owes_merchant')
                + (float) $query->sum('courier_owes_merchant')
                + (float) $query->sum('shipping_company_owes_merchant')
                - (float) $query->sum('merchant_owes_platform'),
        ];
    }

    /**
     * Get debt summary for platform
     */
    public function getPlatformDebtSummary(): array
    {
        $query = MerchantPurchase::where('settlement_status', '!=', 'settled');

        return [
            'owes_to_merchants' => (float) $query->sum('platform_owes_merchant'),
            'merchants_owe' => (float) $query->sum('merchant_owes_platform'),
            'couriers_owe' => (float) $query->sum('courier_owes_platform'),
            'shipping_companies_owe' => (float) $query->sum('shipping_company_owes_platform'),
            'net_receivable' => (float) $query->sum('merchant_owes_platform')
                + (float) $query->sum('courier_owes_platform')
                + (float) $query->sum('shipping_company_owes_platform')
                - (float) $query->sum('platform_owes_merchant'),
        ];
    }

    /**
     * Get debt summary for a courier
     */
    public function getCourierDebtSummary(int $courierId): array
    {
        // Get from DeliveryCourier table for accurate courier-specific data
        $query = \App\Models\DeliveryCourier::where('courier_id', $courierId)
            ->where('settlement_status', '!=', 'settled');

        $codCollected = (clone $query)->where('payment_method', 'cod')->sum('cod_amount');
        $feesEarned = (clone $query)->sum('delivery_fee');

        return [
            'cod_collected' => (float) $codCollected,
            'fees_earned' => (float) $feesEarned,
            'owes_to_platform' => (float) ($codCollected - $feesEarned),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // SETTLEMENT METHODS - تسوية الذمم
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Settle shipping company debt to platform
     *
     * Called when: Shipping company transfers COD to platform
     * Zeroes: shipping_company_owes_platform
     */
    public function settleShippingCompanyToPlatform(MerchantPurchase $mp, array $settlementData = []): void
    {
        if ($mp->shipping_company_owes_platform <= 0) {
            throw new \InvalidArgumentException('No shipping company debt to platform exists');
        }

        $mp->update([
            'shipping_company_owes_platform' => 0,
            'settlement_status' => 'settled',
            'settled_at' => now(),
        ]);

        // Log settlement for audit trail
        \Log::info('Settlement: Shipping Company → Platform', [
            'merchant_purchase_id' => $mp->id,
            'amount' => $mp->getOriginal('shipping_company_owes_platform'),
            'settlement_data' => $settlementData,
        ]);
    }

    /**
     * Settle shipping company debt to merchant
     *
     * Called when: Shipping company transfers COD to merchant
     * Zeroes: shipping_company_owes_merchant
     */
    public function settleShippingCompanyToMerchant(MerchantPurchase $mp, array $settlementData = []): void
    {
        if ($mp->shipping_company_owes_merchant <= 0) {
            throw new \InvalidArgumentException('No shipping company debt to merchant exists');
        }

        $mp->update([
            'shipping_company_owes_merchant' => 0,
            'settlement_status' => 'settled',
            'settled_at' => now(),
        ]);

        \Log::info('Settlement: Shipping Company → Merchant', [
            'merchant_purchase_id' => $mp->id,
            'amount' => $mp->getOriginal('shipping_company_owes_merchant'),
            'settlement_data' => $settlementData,
        ]);
    }

    /**
     * Settle courier debt to platform
     *
     * Called when: Courier transfers COD to platform
     * Zeroes: courier_owes_platform
     */
    public function settleCourierToPlatform(MerchantPurchase $mp, array $settlementData = []): void
    {
        if ($mp->courier_owes_platform <= 0) {
            throw new \InvalidArgumentException('No courier debt to platform exists');
        }

        $mp->update([
            'courier_owes_platform' => 0,
            'settlement_status' => 'settled',
            'settled_at' => now(),
        ]);

        \Log::info('Settlement: Courier → Platform', [
            'merchant_purchase_id' => $mp->id,
            'amount' => $mp->getOriginal('courier_owes_platform'),
            'settlement_data' => $settlementData,
        ]);
    }

    /**
     * Settle courier debt to merchant
     *
     * Called when: Courier transfers COD to merchant
     * Zeroes: courier_owes_merchant
     */
    public function settleCourierToMerchant(MerchantPurchase $mp, array $settlementData = []): void
    {
        if ($mp->courier_owes_merchant <= 0) {
            throw new \InvalidArgumentException('No courier debt to merchant exists');
        }

        $mp->update([
            'courier_owes_merchant' => 0,
            'settlement_status' => 'settled',
            'settled_at' => now(),
        ]);

        \Log::info('Settlement: Courier → Merchant', [
            'merchant_purchase_id' => $mp->id,
            'amount' => $mp->getOriginal('courier_owes_merchant'),
            'settlement_data' => $settlementData,
        ]);
    }

    /**
     * Settle platform debt to merchant
     *
     * Called when: Platform pays merchant their net amount
     * Zeroes: platform_owes_merchant
     */
    public function settlePlatformToMerchant(MerchantPurchase $mp, array $settlementData = []): void
    {
        if ($mp->platform_owes_merchant <= 0) {
            throw new \InvalidArgumentException('No platform debt to merchant exists');
        }

        $mp->update([
            'platform_owes_merchant' => 0,
            'settlement_status' => 'settled',
            'settled_at' => now(),
        ]);

        \Log::info('Settlement: Platform → Merchant', [
            'merchant_purchase_id' => $mp->id,
            'amount' => $mp->getOriginal('platform_owes_merchant'),
            'settlement_data' => $settlementData,
        ]);
    }

    /**
     * Settle merchant debt to platform
     *
     * Called when: Merchant pays platform commission/fees
     * Zeroes: merchant_owes_platform
     */
    public function settleMerchantToPlatform(MerchantPurchase $mp, array $settlementData = []): void
    {
        if ($mp->merchant_owes_platform <= 0) {
            throw new \InvalidArgumentException('No merchant debt to platform exists');
        }

        $mp->update([
            'merchant_owes_platform' => 0,
            'settlement_status' => 'settled',
            'settled_at' => now(),
        ]);

        \Log::info('Settlement: Merchant → Platform', [
            'merchant_purchase_id' => $mp->id,
            'amount' => $mp->getOriginal('merchant_owes_platform'),
            'settlement_data' => $settlementData,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // COD FAILURE HANDLING
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Mark COD collection as failed
     *
     * Called when: Delivery failed, customer refused, etc.
     * Effect: collection_status = failed, debt remains until order resolved
     */
    public function markCollectionFailed(MerchantPurchase $mp, string $reason = ''): void
    {
        $mp->update([
            'collection_status' => MerchantPurchase::COLLECTION_FAILED,
            'collected_at' => now(),
            'collected_by' => 'FAILED: ' . $reason,
        ]);

        \Log::warning('COD Collection Failed', [
            'merchant_purchase_id' => $mp->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Reverse debts on order cancellation/refund
     *
     * IMPORTANT: This zeroes all debts - use only for full cancellation
     */
    public function reverseDebtsOnCancellation(MerchantPurchase $mp, string $reason = ''): void
    {
        $mp->update([
            'platform_owes_merchant' => 0,
            'merchant_owes_platform' => 0,
            'courier_owes_merchant' => 0,
            'courier_owes_platform' => 0,
            'shipping_company_owes_merchant' => 0,
            'shipping_company_owes_platform' => 0,
            'settlement_status' => 'cancelled',
            'settled_at' => now(),
        ]);

        \Log::info('Debts Reversed (Cancellation)', [
            'merchant_purchase_id' => $mp->id,
            'reason' => $reason,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // COD AMOUNT CALCULATION - SINGLE SOURCE OF TRUTH
    // ═══════════════════════════════════════════════════════════════════════════════
    //
    // ⚠️  WARNING: DO NOT CALCULATE cod_amount ANYWHERE ELSE IN THE CODEBASE!
    // ⚠️  ALL cod_amount CALCULATIONS MUST USE THESE METHODS!
    //
    // The cod_amount has TWO different meanings depending on context:
    //
    // 1. merchant_purchases.cod_amount = Accounting bucket (items + delivery)
    //    - Used for debt ledger calculations
    //    - Does NOT include tax (tax tracked separately in debts)
    //
    // 2. delivery_couriers.cod_amount = Physical cash collected by courier
    //    - The ACTUAL amount the courier collects from customer
    //    - EQUALS pay_amount (includes everything: items + tax + shipping + packing)
    //
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Calculate cod_amount for merchant_purchases table (accounting purposes)
     *
     * Formula: items_price + delivery_fee (without tax - tax tracked separately)
     *
     * @param string $paymentMethod 'cod' or 'online'
     * @param float $itemsPrice Total price of items (gross)
     * @param float $deliveryFee Shipping cost OR courier fee (whichever applies)
     * @return float
     */
    public function calculateMerchantPurchaseCodAmount(
        string $paymentMethod,
        float $itemsPrice,
        float $deliveryFee
    ): float {
        if (strtolower($paymentMethod) !== 'cod') {
            return 0;
        }

        return round($itemsPrice + $deliveryFee, 2);
    }

    /**
     * Calculate cod_amount for delivery_couriers table (actual cash collection)
     *
     * This is the TOTAL amount the courier physically collects from customer.
     * It equals pay_amount which already includes: items + tax + shipping/courier + packing
     *
     * @param string $paymentMethod 'cod' or 'online'
     * @param float $payAmount The total order amount (from purchases.pay_amount)
     * @return float
     */
    public function calculateCourierCodAmount(
        string $paymentMethod,
        float $payAmount
    ): float {
        if (!in_array(strtolower($paymentMethod), ['cod', 'cash on delivery'])) {
            return 0;
        }

        // pay_amount already includes EVERYTHING (items + tax + courier_fee + packing)
        // DO NOT add delivery_fee again - it's already in pay_amount!
        return round($payAmount, 2);
    }

    /**
     * Prepare all data for delivery_couriers record
     *
     * ⚠️ USE THIS METHOD when creating DeliveryCourier records!
     *
     * @param Purchase $purchase The purchase record
     * @param int $merchantId Merchant ID
     * @param array $shippingData Shipping/courier data from session
     * @param string $paymentMethod Payment method (cod/online)
     * @return array Data ready for DeliveryCourier::create()
     */
    public function prepareDeliveryCourierData(
        Purchase $purchase,
        int $merchantId,
        array $shippingData,
        string $paymentMethod
    ): array {
        $isCod = in_array(strtolower($paymentMethod), ['cod', 'cash on delivery']);
        $deliveryFee = (float) ($shippingData['courier_fee'] ?? 0);

        return [
            'purchase_id' => $purchase->id,
            'merchant_id' => $merchantId,
            'courier_id' => $shippingData['courier_id'] ?? null,
            'service_area_id' => $shippingData['service_area_id'] ?? null,
            'merchant_location_id' => $shippingData['merchant_location_id'] ?? null,
            'delivery_fee' => $deliveryFee,
            'purchase_amount' => $purchase->pay_amount,
            'cod_amount' => $this->calculateCourierCodAmount($paymentMethod, $purchase->pay_amount),
            'payment_method' => $isCod ? 'cod' : 'online',
            'status' => 'pending_approval',
            'fee_status' => 'pending',
            'settlement_status' => 'pending',
        ];
    }
}
