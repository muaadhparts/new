<?php

namespace App\Services;

use App\Models\AccountParty;
use App\Models\AccountingLedger;
use App\Models\AccountBalance;
use App\Models\MerchantPurchase;
use App\Models\Purchase;
use App\Models\SettlementBatch;
use App\Services\MonetaryUnitService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * AccountingEntryService - خدمة القيود المحاسبية
 *
 * تسجيل القيود المحاسبية الكاملة لكل حدث مالي:
 * - إنشاء الطلب
 * - التحصيل (COD/أونلاين)
 * - التسوية
 * - الإلغاء / الإرجاع
 *
 * القاعدة: كل رقم في التقارير يأتي من Ledger فقط
 */
class AccountingEntryService
{
    // ═══════════════════════════════════════════════════════════════
    // PARTY MANAGEMENT
    // ═══════════════════════════════════════════════════════════════

    /**
     * الحصول على طرف المنصة
     */
    public function getPlatformParty(): AccountParty
    {
        return AccountParty::where('party_type', 'platform')
            ->where('code', 'platform')
            ->firstOrFail();
    }

    /**
     * الحصول على أو إنشاء طرف التاجر
     */
    public function getOrCreateMerchantParty(int $merchantId): AccountParty
    {
        return AccountParty::firstOrCreate(
            [
                'party_type' => 'merchant',
                'reference_type' => 'User',
                'reference_id' => $merchantId,
            ],
            [
                'name' => optional(\App\Models\User::find($merchantId))->shop_name ?? "Merchant #{$merchantId}",
                'code' => "merchant_{$merchantId}",
                'is_active' => true,
            ]
        );
    }

    /**
     * الحصول على أو إنشاء طرف المندوب
     */
    public function getOrCreateCourierParty(int $courierId): AccountParty
    {
        return AccountParty::firstOrCreate(
            [
                'party_type' => 'courier',
                'reference_type' => 'Courier',
                'reference_id' => $courierId,
            ],
            [
                'name' => optional(\App\Models\Courier::find($courierId))->name ?? "Courier #{$courierId}",
                'code' => "courier_{$courierId}",
                'is_active' => true,
            ]
        );
    }

    /**
     * الحصول على أو إنشاء طرف شركة الشحن
     */
    public function getOrCreateShippingParty(string $providerName): AccountParty
    {
        $code = 'shipping_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $providerName));

        return AccountParty::firstOrCreate(
            [
                'party_type' => 'shipping_provider',
                'code' => $code,
            ],
            [
                'name' => $providerName,
                'is_active' => true,
            ]
        );
    }

    /**
     * الحصول على أو إنشاء طرف الجهة الضريبية
     */
    public function getTaxAuthorityParty(): AccountParty
    {
        return AccountParty::firstOrCreate(
            [
                'party_type' => 'tax_authority',
                'code' => 'tax_authority',
            ],
            [
                'name' => 'Tax Authority',
                'is_active' => true,
            ]
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // ORDER CREATION ENTRIES - قيود إنشاء الطلب
    // ═══════════════════════════════════════════════════════════════

    /**
     * تسجيل كل القيود عند إنشاء الطلب
     *
     * @param MerchantPurchase $mp
     * @return Collection القيود المنشأة
     */
    public function createOrderEntries(MerchantPurchase $mp): Collection
    {
        return DB::transaction(function () use ($mp) {
            $entries = collect();

            $platform = $this->getPlatformParty();
            $merchant = $this->getOrCreateMerchantParty($mp->user_id);

            // 1. قيد إيراد المبيعات (للتاجر)
            $entries->push($this->createSaleRevenueEntry($mp, $merchant, $platform));

            // 2. قيد العمولة (للمنصة)
            if (($mp->commission_amount ?? 0) > 0) {
                $entries->push($this->createCommissionEntry($mp, $merchant, $platform));
            }

            // 3. قيد الضريبة
            if (($mp->tax_amount ?? 0) > 0) {
                $entries->push($this->createTaxEntry($mp, $merchant));
            }

            // 4. قيود الشحن
            $shippingEntries = $this->createShippingEntries($mp, $merchant, $platform);
            $entries = $entries->merge($shippingEntries);

            // 5. قيد رسوم المندوب
            if (($mp->courier_fee ?? 0) > 0 && $mp->courier_id) {
                $entries->push($this->createCourierFeeEntry($mp));
            }

            // 6. قيد COD (إذا كان الدفع عند الاستلام)
            if ($mp->payment_method === 'cod' && ($mp->cod_amount ?? 0) > 0) {
                $entries->push($this->createCodPendingEntry($mp, $merchant, $platform));
            }

            // تحديث الأرصدة المحسوبة
            $this->updateBalances($entries);

            return $entries;
        });
    }

    /**
     * قيد إيراد المبيعات
     */
    protected function createSaleRevenueEntry(
        MerchantPurchase $mp,
        AccountParty $merchant,
        AccountParty $platform
    ): AccountingLedger {
        // تحديد حالة الدين بناءً على طريقة الدفع
        $debtStatus = $mp->payment_method === 'cod'
            ? AccountingLedger::DEBT_PENDING
            : AccountingLedger::DEBT_SETTLED;

        return AccountingLedger::create([
            'purchase_id' => $mp->purchase_id,
            'merchant_purchase_id' => $mp->id,
            'from_party_id' => $platform->id, // ضمنياً العميل عبر المنصة
            'to_party_id' => $merchant->id,
            'amount' => $mp->price,
            'monetary_unit_code' => MonetaryUnitService::BASE_MONETARY_UNIT,
            'transaction_type' => AccountingLedger::TYPE_DEBT,
            'entry_type' => AccountingLedger::ENTRY_SALE_REVENUE,
            'direction' => AccountingLedger::DIRECTION_CREDIT,
            'debt_status' => $debtStatus,
            'description' => "Sales revenue for order #{$mp->purchase_number}",
            'description_ar' => "إيراد مبيعات - طلب #{$mp->purchase_number}",
            'metadata' => [
                'merchant_id' => $mp->user_id,
                'items_count' => count($mp->getCartItems()),
                'payment_method' => $mp->payment_method,
                'payment_owner_id' => $mp->payment_owner_id,
            ],
            'status' => AccountingLedger::STATUS_COMPLETED,
        ]);
    }

    /**
     * قيد العمولة المستحقة
     */
    protected function createCommissionEntry(
        MerchantPurchase $mp,
        AccountParty $merchant,
        AccountParty $platform
    ): AccountingLedger {
        return AccountingLedger::create([
            'purchase_id' => $mp->purchase_id,
            'merchant_purchase_id' => $mp->id,
            'from_party_id' => $merchant->id,
            'to_party_id' => $platform->id,
            'amount' => $mp->commission_amount,
            'monetary_unit_code' => MonetaryUnitService::BASE_MONETARY_UNIT,
            'transaction_type' => AccountingLedger::TYPE_FEE,
            'entry_type' => AccountingLedger::ENTRY_COMMISSION_EARNED,
            'direction' => AccountingLedger::DIRECTION_CREDIT,
            'debt_status' => AccountingLedger::DEBT_PENDING,
            'description' => "Platform commission for order #{$mp->purchase_number}",
            'description_ar' => "عمولة المنصة - طلب #{$mp->purchase_number}",
            'metadata' => [
                'commission_rate' => $mp->commission_rate ?? 0,
                'base_amount' => $mp->price,
            ],
            'status' => AccountingLedger::STATUS_COMPLETED,
        ]);
    }

    /**
     * قيد الضريبة المحصلة
     */
    protected function createTaxEntry(
        MerchantPurchase $mp,
        AccountParty $merchant
    ): AccountingLedger {
        $taxAuthority = $this->getTaxAuthorityParty();

        return AccountingLedger::create([
            'purchase_id' => $mp->purchase_id,
            'merchant_purchase_id' => $mp->id,
            'from_party_id' => $merchant->id, // الضريبة محصلة من العميل عبر التاجر
            'to_party_id' => $taxAuthority->id,
            'amount' => $mp->tax_amount,
            'monetary_unit_code' => MonetaryUnitService::BASE_MONETARY_UNIT,
            'transaction_type' => AccountingLedger::TYPE_FEE,
            'entry_type' => AccountingLedger::ENTRY_TAX_COLLECTED,
            'direction' => AccountingLedger::DIRECTION_CREDIT,
            'debt_status' => AccountingLedger::DEBT_PENDING, // معلق حتى توريده للجهة الضريبية
            'description' => "Tax collected for order #{$mp->purchase_number}",
            'description_ar' => "ضريبة محصلة - طلب #{$mp->purchase_number}",
            'metadata' => [
                'tax_rate' => $mp->purchase->tax ?? 0,
                'tax_location' => $mp->purchase->tax_location ?? null,
                'taxable_amount' => $mp->price,
            ],
            'status' => AccountingLedger::STATUS_COMPLETED,
        ]);
    }

    /**
     * قيود الشحن والتغليف
     *
     * OWNER_ID PATTERN:
     * - shipping_owner_id = 0 → Platform-provided shipping
     * - shipping_owner_id > 0 → Merchant-provided shipping
     * - packing_owner_id = 0  → Platform-provided packaging
     * - packing_owner_id > 0  → Merchant-provided packaging
     */
    protected function createShippingEntries(
        MerchantPurchase $mp,
        AccountParty $merchant,
        AccountParty $platform
    ): Collection {
        $entries = collect();

        // ═══ SHIPPING ENTRIES ═══

        // شحن المنصة (platform_shipping_fee when shipping_owner_id = 0)
        if (($mp->platform_shipping_fee ?? 0) > 0) {
            $shippingProvider = $this->getOrCreateShippingParty($mp->delivery_provider ?? 'Unknown');

            $entries->push(AccountingLedger::create([
                'purchase_id' => $mp->purchase_id,
                'merchant_purchase_id' => $mp->id,
                'from_party_id' => $platform->id,
                'to_party_id' => $shippingProvider->id,
                'amount' => $mp->platform_shipping_fee,
                'monetary_unit_code' => MonetaryUnitService::BASE_MONETARY_UNIT,
                'transaction_type' => AccountingLedger::TYPE_FEE,
                'entry_type' => AccountingLedger::ENTRY_SHIPPING_FEE_PLATFORM,
                'direction' => AccountingLedger::DIRECTION_CREDIT,
                'debt_status' => AccountingLedger::DEBT_PENDING,
                'description' => "Platform shipping fee for order #{$mp->purchase_number}",
                'description_ar' => "رسم شحن (منصة) - طلب #{$mp->purchase_number}",
                'metadata' => [
                    'shipping_provider' => $mp->delivery_provider,
                    'shipping_owner_id' => $mp->shipping_owner_id ?? 0,
                    'is_platform_provided' => true,
                ],
                'status' => AccountingLedger::STATUS_COMPLETED,
            ]));
        }

        // شحن التاجر (shipping_cost when shipping_owner_id > 0)
        $shippingOwnerId = $mp->shipping_owner_id ?? 0;
        if (($mp->shipping_cost ?? 0) > 0 && $shippingOwnerId > 0) {
            $entries->push(AccountingLedger::create([
                'purchase_id' => $mp->purchase_id,
                'merchant_purchase_id' => $mp->id,
                'from_party_id' => $platform->id, // العميل عبر المنصة
                'to_party_id' => $merchant->id,
                'amount' => $mp->shipping_cost,
                'monetary_unit_code' => MonetaryUnitService::BASE_MONETARY_UNIT,
                'transaction_type' => AccountingLedger::TYPE_FEE,
                'entry_type' => AccountingLedger::ENTRY_SHIPPING_FEE_MERCHANT,
                'direction' => AccountingLedger::DIRECTION_CREDIT,
                'debt_status' => AccountingLedger::DEBT_PENDING,
                'description' => "Merchant shipping fee for order #{$mp->purchase_number}",
                'description_ar' => "رسم شحن (تاجر) - طلب #{$mp->purchase_number}",
                'metadata' => [
                    'shipping_owner_id' => $shippingOwnerId,
                    'is_platform_provided' => false,
                ],
                'status' => AccountingLedger::STATUS_COMPLETED,
            ]));
        }

        // ═══ PACKING ENTRIES ═══

        // تغليف المنصة (platform_packing_fee when packing_owner_id = 0)
        if (($mp->platform_packing_fee ?? 0) > 0) {
            $entries->push(AccountingLedger::create([
                'purchase_id' => $mp->purchase_id,
                'merchant_purchase_id' => $mp->id,
                'from_party_id' => $merchant->id, // خصم من التاجر
                'to_party_id' => $platform->id,   // إيراد للمنصة
                'amount' => $mp->platform_packing_fee,
                'monetary_unit_code' => MonetaryUnitService::BASE_MONETARY_UNIT,
                'transaction_type' => AccountingLedger::TYPE_FEE,
                'entry_type' => AccountingLedger::ENTRY_PACKING_FEE_PLATFORM,
                'direction' => AccountingLedger::DIRECTION_CREDIT,
                'debt_status' => AccountingLedger::DEBT_PENDING,
                'description' => "Platform packing fee for order #{$mp->purchase_number}",
                'description_ar' => "رسم تغليف (منصة) - طلب #{$mp->purchase_number}",
                'metadata' => [
                    'packing_owner_id' => $mp->packing_owner_id ?? 0,
                    'is_platform_provided' => true,
                ],
                'status' => AccountingLedger::STATUS_COMPLETED,
            ]));
        }

        // تغليف التاجر (packing_cost when packing_owner_id > 0)
        $packingOwnerId = $mp->packing_owner_id ?? 0;
        if (($mp->packing_cost ?? 0) > 0 && $packingOwnerId > 0) {
            $entries->push(AccountingLedger::create([
                'purchase_id' => $mp->purchase_id,
                'merchant_purchase_id' => $mp->id,
                'from_party_id' => $platform->id, // العميل عبر المنصة
                'to_party_id' => $merchant->id,   // إيراد للتاجر
                'amount' => $mp->packing_cost,
                'monetary_unit_code' => MonetaryUnitService::BASE_MONETARY_UNIT,
                'transaction_type' => AccountingLedger::TYPE_FEE,
                'entry_type' => AccountingLedger::ENTRY_PACKING_FEE_MERCHANT,
                'direction' => AccountingLedger::DIRECTION_CREDIT,
                'debt_status' => AccountingLedger::DEBT_PENDING,
                'description' => "Merchant packing fee for order #{$mp->purchase_number}",
                'description_ar' => "رسم تغليف (تاجر) - طلب #{$mp->purchase_number}",
                'metadata' => [
                    'packing_owner_id' => $packingOwnerId,
                    'is_platform_provided' => false,
                ],
                'status' => AccountingLedger::STATUS_COMPLETED,
            ]));
        }

        return $entries;
    }

    /**
     * قيد رسوم المندوب
     */
    protected function createCourierFeeEntry(MerchantPurchase $mp): AccountingLedger
    {
        $courier = $this->getOrCreateCourierParty($mp->courier_id);
        $platform = $this->getPlatformParty();

        return AccountingLedger::create([
            'purchase_id' => $mp->purchase_id,
            'merchant_purchase_id' => $mp->id,
            'from_party_id' => $platform->id, // العميل عبر المنصة
            'to_party_id' => $courier->id,
            'amount' => $mp->courier_fee,
            'monetary_unit_code' => MonetaryUnitService::BASE_MONETARY_UNIT,
            'transaction_type' => AccountingLedger::TYPE_FEE,
            'entry_type' => AccountingLedger::ENTRY_COURIER_FEE,
            'direction' => AccountingLedger::DIRECTION_CREDIT,
            'debt_status' => AccountingLedger::DEBT_PENDING,
            'description' => "Courier fee for order #{$mp->purchase_number}",
            'description_ar' => "رسم توصيل - طلب #{$mp->purchase_number}",
            'metadata' => [
                'courier_id' => $mp->courier_id,
            ],
            'status' => AccountingLedger::STATUS_COMPLETED,
        ]);
    }

    /**
     * قيد COD معلق
     */
    protected function createCodPendingEntry(
        MerchantPurchase $mp,
        AccountParty $merchant,
        AccountParty $platform
    ): AccountingLedger {
        // تحديد من سيستلم COD
        $collectorParty = $this->determineCollectorParty($mp);

        // تحديد من سيدفع له COD
        $recipientParty = ($mp->payment_owner_id == 0) ? $platform : $merchant;

        return AccountingLedger::create([
            'purchase_id' => $mp->purchase_id,
            'merchant_purchase_id' => $mp->id,
            'from_party_id' => $collectorParty->id, // المندوب أو شركة الشحن
            'to_party_id' => $recipientParty->id, // المنصة أو التاجر
            'amount' => $mp->cod_amount,
            'monetary_unit_code' => MonetaryUnitService::BASE_MONETARY_UNIT,
            'transaction_type' => AccountingLedger::TYPE_COLLECTION,
            'entry_type' => AccountingLedger::ENTRY_COD_PENDING,
            'direction' => AccountingLedger::DIRECTION_CREDIT,
            'debt_status' => AccountingLedger::DEBT_PENDING,
            'description' => "COD pending for order #{$mp->purchase_number}",
            'description_ar' => "دفع عند الاستلام (معلق) - طلب #{$mp->purchase_number}",
            'metadata' => [
                'collector_type' => $mp->delivery_method,
                'collector_id' => $mp->courier_id ?? $mp->shipping_id ?? null,
                'payment_owner_id' => $mp->payment_owner_id,
            ],
            'status' => AccountingLedger::STATUS_PENDING,
        ]);
    }

    /**
     * تحديد من سيجمع COD
     */
    protected function determineCollectorParty(MerchantPurchase $mp): AccountParty
    {
        if ($mp->delivery_method === 'local_courier' && $mp->courier_id) {
            return $this->getOrCreateCourierParty($mp->courier_id);
        }

        if ($mp->delivery_provider) {
            return $this->getOrCreateShippingParty($mp->delivery_provider);
        }

        // افتراضي: المنصة
        return $this->getPlatformParty();
    }

    // ═══════════════════════════════════════════════════════════════
    // COLLECTION ENTRIES - قيود التحصيل
    // ═══════════════════════════════════════════════════════════════

    /**
     * تسجيل تحصيل COD
     */
    public function recordCodCollection(
        MerchantPurchase $mp,
        string $collectedBy = 'system',
        ?int $userId = null
    ): AccountingLedger {
        return DB::transaction(function () use ($mp, $collectedBy, $userId) {
            // تحديث قيد COD المعلق
            $pendingEntry = AccountingLedger::where('merchant_purchase_id', $mp->id)
                ->where('entry_type', AccountingLedger::ENTRY_COD_PENDING)
                ->where('debt_status', AccountingLedger::DEBT_PENDING)
                ->first();

            if ($pendingEntry) {
                $pendingEntry->update([
                    'debt_status' => AccountingLedger::DEBT_SETTLED,
                    'settled_at' => now(),
                    'settled_by' => $userId,
                ]);
            }

            // إنشاء قيد تحصيل جديد
            $platform = $this->getPlatformParty();
            $merchant = $this->getOrCreateMerchantParty($mp->user_id);

            $fromParty = $this->determineCollectorParty($mp);
            $toParty = ($mp->payment_owner_id == 0) ? $platform : $merchant;

            $entry = AccountingLedger::create([
                'purchase_id' => $mp->purchase_id,
                'merchant_purchase_id' => $mp->id,
                'from_party_id' => $fromParty->id,
                'to_party_id' => $toParty->id,
                'amount' => $mp->cod_amount,
                'monetary_unit_code' => MonetaryUnitService::BASE_MONETARY_UNIT,
                'transaction_type' => AccountingLedger::TYPE_COLLECTION,
                'entry_type' => AccountingLedger::ENTRY_COD_COLLECTED,
                'direction' => AccountingLedger::DIRECTION_CREDIT,
                'debt_status' => AccountingLedger::DEBT_SETTLED,
                'description' => "COD collected for order #{$mp->purchase_number}",
                'description_ar' => "تم تحصيل الدفع عند الاستلام - طلب #{$mp->purchase_number}",
                'metadata' => [
                    'collected_by' => $collectedBy,
                    'collected_at' => now()->toIso8601String(),
                ],
                'status' => AccountingLedger::STATUS_COMPLETED,
                'settled_at' => now(),
                'settled_by' => $userId,
            ]);

            // تحديث SALE_REVENUE إلى SETTLED
            AccountingLedger::where('merchant_purchase_id', $mp->id)
                ->where('entry_type', AccountingLedger::ENTRY_SALE_REVENUE)
                ->update(['debt_status' => AccountingLedger::DEBT_SETTLED]);

            $this->updateBalances(collect([$entry]));

            return $entry;
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // SETTLEMENT ENTRIES - قيود التسوية
    // ═══════════════════════════════════════════════════════════════

    /**
     * تسجيل تسوية من المنصة للتاجر
     */
    public function recordSettlementToMerchant(
        array $merchantPurchaseIds,
        float $amount,
        string $paymentMethod,
        ?string $paymentReference = null,
        ?int $userId = null
    ): SettlementBatch {
        return DB::transaction(function () use ($merchantPurchaseIds, $amount, $paymentMethod, $paymentReference, $userId) {
            $platform = $this->getPlatformParty();

            // الحصول على أول طلب لتحديد التاجر
            $firstMp = MerchantPurchase::findOrFail($merchantPurchaseIds[0]);
            $merchant = $this->getOrCreateMerchantParty($firstMp->user_id);

            // إنشاء دفعة التسوية
            $batch = SettlementBatch::create([
                'batch_ref' => 'SET-' . date('Ymd') . '-' . strtoupper(\Str::random(6)),
                'from_party_id' => $platform->id,
                'to_party_id' => $merchant->id,
                'total_amount' => $amount,
                'monetary_unit_code' => MonetaryUnitService::BASE_MONETARY_UNIT,
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentReference,
                'status' => 'completed',
                'settlement_date' => now()->toDateString(),
                'created_by' => $userId,
                'approved_by' => $userId,
            ]);

            // إنشاء قيد التسوية
            $entry = AccountingLedger::create([
                'purchase_id' => null,
                'merchant_purchase_id' => null,
                'from_party_id' => $platform->id,
                'to_party_id' => $merchant->id,
                'amount' => $amount,
                'monetary_unit_code' => MonetaryUnitService::BASE_MONETARY_UNIT,
                'transaction_type' => AccountingLedger::TYPE_SETTLEMENT,
                'entry_type' => AccountingLedger::ENTRY_SETTLEMENT_PAYMENT,
                'direction' => AccountingLedger::DIRECTION_DEBIT,
                'debt_status' => AccountingLedger::DEBT_SETTLED,
                'description' => "Settlement payment to merchant - Batch #{$batch->batch_ref}",
                'description_ar' => "دفعة تسوية للتاجر - الدفعة #{$batch->batch_ref}",
                'metadata' => [
                    'merchant_id' => $firstMp->user_id,
                    'purchase_ids' => $merchantPurchaseIds,
                    'payment_method' => $paymentMethod,
                    'payment_reference' => $paymentReference,
                ],
                'status' => AccountingLedger::STATUS_COMPLETED,
                'settlement_batch_id' => $batch->id,
                'settled_at' => now(),
                'settled_by' => $userId,
            ]);

            // تحديث حالة الطلبات
            MerchantPurchase::whereIn('id', $merchantPurchaseIds)->update([
                'settlement_status' => 'settled',
                'settled_at' => now(),
                'merchant_settlement_id' => $batch->id,
            ]);

            // تحديث قيود العمولة إلى مسددة
            AccountingLedger::whereIn('merchant_purchase_id', $merchantPurchaseIds)
                ->where('entry_type', AccountingLedger::ENTRY_COMMISSION_EARNED)
                ->update(['debt_status' => AccountingLedger::DEBT_SETTLED]);

            $this->updateBalances(collect([$entry]));

            return $batch;
        });
    }

    /**
     * تسجيل تسوية من المندوب للمنصة
     */
    public function recordCourierSettlement(
        int $courierId,
        array $merchantPurchaseIds,
        float $amount,
        string $paymentMethod,
        ?int $userId = null
    ): SettlementBatch {
        return DB::transaction(function () use ($courierId, $merchantPurchaseIds, $amount, $paymentMethod, $userId) {
            $courier = $this->getOrCreateCourierParty($courierId);
            $platform = $this->getPlatformParty();

            $batch = SettlementBatch::create([
                'batch_ref' => 'CSET-' . date('Ymd') . '-' . strtoupper(\Str::random(6)),
                'from_party_id' => $courier->id,
                'to_party_id' => $platform->id,
                'total_amount' => $amount,
                'monetary_unit_code' => MonetaryUnitService::BASE_MONETARY_UNIT,
                'payment_method' => $paymentMethod,
                'status' => 'completed',
                'settlement_date' => now()->toDateString(),
                'created_by' => $userId,
            ]);

            $entry = AccountingLedger::create([
                'from_party_id' => $courier->id,
                'to_party_id' => $platform->id,
                'amount' => $amount,
                'monetary_unit_code' => MonetaryUnitService::BASE_MONETARY_UNIT,
                'transaction_type' => AccountingLedger::TYPE_SETTLEMENT,
                'entry_type' => AccountingLedger::ENTRY_SETTLEMENT_PAYMENT,
                'direction' => AccountingLedger::DIRECTION_DEBIT,
                'debt_status' => AccountingLedger::DEBT_SETTLED,
                'description' => "Courier settlement to platform - Batch #{$batch->batch_ref}",
                'description_ar' => "تسوية المندوب للمنصة - الدفعة #{$batch->batch_ref}",
                'metadata' => [
                    'courier_id' => $courierId,
                    'purchase_ids' => $merchantPurchaseIds,
                ],
                'status' => AccountingLedger::STATUS_COMPLETED,
                'settlement_batch_id' => $batch->id,
                'settled_at' => now(),
                'settled_by' => $userId,
            ]);

            // تحديث قيود المندوب
            MerchantPurchase::whereIn('id', $merchantPurchaseIds)->update([
                'courier_owes_platform' => 0,
            ]);

            $this->updateBalances(collect([$entry]));

            return $batch;
        });
    }

    /**
     * تسجيل تسوية من شركة الشحن للمنصة
     *
     * عندما تسلم شركة الشحن مبالغ COD المحصلة للمنصة
     */
    public function recordShippingCompanySettlement(
        string $providerCode,
        array $merchantPurchaseIds,
        float $amount,
        string $paymentMethod,
        ?string $paymentReference = null,
        ?int $userId = null
    ): SettlementBatch {
        return DB::transaction(function () use ($providerCode, $merchantPurchaseIds, $amount, $paymentMethod, $paymentReference, $userId) {
            $shippingCompany = $this->getOrCreateShippingParty($providerCode);
            $platform = $this->getPlatformParty();

            $batch = SettlementBatch::create([
                'batch_ref' => 'SSET-' . date('Ymd') . '-' . strtoupper(\Str::random(6)),
                'from_party_id' => $shippingCompany->id,
                'to_party_id' => $platform->id,
                'total_amount' => $amount,
                'monetary_unit_code' => MonetaryUnitService::BASE_MONETARY_UNIT,
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentReference,
                'status' => 'completed',
                'settlement_date' => now()->toDateString(),
                'created_by' => $userId,
            ]);

            $entry = AccountingLedger::create([
                'from_party_id' => $shippingCompany->id,
                'to_party_id' => $platform->id,
                'amount' => $amount,
                'monetary_unit_code' => MonetaryUnitService::BASE_MONETARY_UNIT,
                'transaction_type' => AccountingLedger::TYPE_SETTLEMENT,
                'entry_type' => AccountingLedger::ENTRY_SETTLEMENT_PAYMENT,
                'direction' => AccountingLedger::DIRECTION_DEBIT,
                'debt_status' => AccountingLedger::DEBT_SETTLED,
                'description' => "Shipping company settlement to platform - Batch #{$batch->batch_ref}",
                'description_ar' => "تسوية شركة الشحن للمنصة - الدفعة #{$batch->batch_ref}",
                'metadata' => [
                    'shipping_provider' => $providerCode,
                    'purchase_ids' => $merchantPurchaseIds,
                    'payment_reference' => $paymentReference,
                ],
                'status' => AccountingLedger::STATUS_COMPLETED,
                'settlement_batch_id' => $batch->id,
                'settled_at' => now(),
                'settled_by' => $userId,
            ]);

            // تحديث حالة الطلبات - شركة الشحن سددت
            MerchantPurchase::whereIn('id', $merchantPurchaseIds)->update([
                'shipping_company_owes_platform' => 0,
            ]);

            // تحديث قيود COD المعلقة إلى مسددة
            AccountingLedger::whereIn('merchant_purchase_id', $merchantPurchaseIds)
                ->where('entry_type', AccountingLedger::ENTRY_COD_PENDING)
                ->update([
                    'debt_status' => AccountingLedger::DEBT_SETTLED,
                    'settled_at' => now(),
                    'settled_by' => $userId,
                ]);

            $this->updateBalances(collect([$entry]));

            return $batch;
        });
    }

    /**
     * تسجيل تسوية من شركة الشحن للتاجر مباشرة
     *
     * عندما يكون التاجر هو مالك بوابة الدفع وشركة الشحن تسلم له مباشرة
     */
    public function recordShippingCompanySettlementToMerchant(
        string $providerCode,
        int $merchantId,
        array $merchantPurchaseIds,
        float $amount,
        string $paymentMethod,
        ?string $paymentReference = null,
        ?int $userId = null
    ): SettlementBatch {
        return DB::transaction(function () use ($providerCode, $merchantId, $merchantPurchaseIds, $amount, $paymentMethod, $paymentReference, $userId) {
            $shippingCompany = $this->getOrCreateShippingParty($providerCode);
            $merchant = $this->getOrCreateMerchantParty($merchantId);

            $batch = SettlementBatch::create([
                'batch_ref' => 'SMSET-' . date('Ymd') . '-' . strtoupper(\Str::random(6)),
                'from_party_id' => $shippingCompany->id,
                'to_party_id' => $merchant->id,
                'total_amount' => $amount,
                'monetary_unit_code' => MonetaryUnitService::BASE_MONETARY_UNIT,
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentReference,
                'status' => 'completed',
                'settlement_date' => now()->toDateString(),
                'created_by' => $userId,
            ]);

            $entry = AccountingLedger::create([
                'from_party_id' => $shippingCompany->id,
                'to_party_id' => $merchant->id,
                'amount' => $amount,
                'monetary_unit_code' => MonetaryUnitService::BASE_MONETARY_UNIT,
                'transaction_type' => AccountingLedger::TYPE_SETTLEMENT,
                'entry_type' => AccountingLedger::ENTRY_SETTLEMENT_PAYMENT,
                'direction' => AccountingLedger::DIRECTION_DEBIT,
                'debt_status' => AccountingLedger::DEBT_SETTLED,
                'description' => "Shipping company settlement to merchant - Batch #{$batch->batch_ref}",
                'description_ar' => "تسوية شركة الشحن للتاجر - الدفعة #{$batch->batch_ref}",
                'metadata' => [
                    'shipping_provider' => $providerCode,
                    'merchant_id' => $merchantId,
                    'purchase_ids' => $merchantPurchaseIds,
                    'payment_reference' => $paymentReference,
                ],
                'status' => AccountingLedger::STATUS_COMPLETED,
                'settlement_batch_id' => $batch->id,
                'settled_at' => now(),
                'settled_by' => $userId,
            ]);

            // تحديث حالة الطلبات - شركة الشحن سددت للتاجر
            MerchantPurchase::whereIn('id', $merchantPurchaseIds)->update([
                'shipping_company_owes_merchant' => 0,
            ]);

            $this->updateBalances(collect([$entry]));

            return $batch;
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // CANCELLATION ENTRIES - قيود الإلغاء
    // ═══════════════════════════════════════════════════════════════

    /**
     * عكس كل قيود الطلب (إلغاء)
     */
    public function reversePurchaseEntries(
        MerchantPurchase $mp,
        string $reason = 'Order cancelled',
        ?int $userId = null
    ): Collection {
        return DB::transaction(function () use ($mp, $reason, $userId) {
            $entries = collect();

            // جلب كل القيود الأصلية للطلب
            $originalEntries = AccountingLedger::where('merchant_purchase_id', $mp->id)
                ->where('debt_status', '!=', AccountingLedger::DEBT_REVERSED)
                ->get();

            foreach ($originalEntries as $original) {
                // إنشاء قيد عكسي
                $reversal = AccountingLedger::create([
                    'purchase_id' => $original->purchase_id,
                    'merchant_purchase_id' => $original->merchant_purchase_id,
                    'from_party_id' => $original->to_party_id, // عكس الاتجاه
                    'to_party_id' => $original->from_party_id,
                    'amount' => $original->amount,
                    'currency' => $original->currency,
                    'transaction_type' => AccountingLedger::TYPE_REVERSAL,
                    'entry_type' => AccountingLedger::ENTRY_CANCELLATION_REVERSAL,
                    'direction' => $original->direction === AccountingLedger::DIRECTION_CREDIT
                        ? AccountingLedger::DIRECTION_DEBIT
                        : AccountingLedger::DIRECTION_CREDIT,
                    'debt_status' => AccountingLedger::DEBT_SETTLED,
                    'original_entry_id' => $original->id,
                    'description' => "Reversal: {$reason}",
                    'description_ar' => "عكس: {$reason}",
                    'metadata' => [
                        'original_entry_id' => $original->id,
                        'original_entry_type' => $original->entry_type,
                        'reversal_reason' => $reason,
                    ],
                    'status' => AccountingLedger::STATUS_COMPLETED,
                    'created_by' => $userId,
                ]);

                // تحديث القيد الأصلي
                $original->reverseDebt();

                $entries->push($reversal);
            }

            // تحديث الطلب
            $mp->update([
                'settlement_status' => 'cancelled',
                'platform_owes_merchant' => 0,
                'merchant_owes_platform' => 0,
                'courier_owes_platform' => 0,
                'shipping_company_owes_merchant' => 0,
                'shipping_company_owes_platform' => 0,
            ]);

            $this->updateBalances($entries);

            return $entries;
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // BALANCE UPDATES - تحديث الأرصدة
    // ═══════════════════════════════════════════════════════════════

    /**
     * تحديث الأرصدة المحسوبة
     */
    protected function updateBalances(Collection $entries): void
    {
        $partyPairs = $entries->map(function ($entry) {
            return [
                'from' => $entry->from_party_id,
                'to' => $entry->to_party_id,
            ];
        })->unique();

        foreach ($partyPairs as $pair) {
            $this->recalculateBalance($pair['from'], $pair['to']);
            $this->recalculateBalance($pair['to'], $pair['from']);
        }
    }

    /**
     * إعادة حساب رصيد طرف
     *
     * يحسب الأرصدة المعلقة ويحدث جدول الأرصدة
     * إذا أصبح الرصيد صفر يتم تصفيره (لا حذف السجل)
     */
    protected function recalculateBalance(int $partyId, int $counterpartyId): void
    {
        // حساب المستحق لنا (receivable)
        $receivable = AccountingLedger::where('to_party_id', $partyId)
            ->where('from_party_id', $counterpartyId)
            ->where('debt_status', AccountingLedger::DEBT_PENDING)
            ->sum('amount');

        // حساب المستحق علينا (payable)
        $payable = AccountingLedger::where('from_party_id', $partyId)
            ->where('to_party_id', $counterpartyId)
            ->where('debt_status', AccountingLedger::DEBT_PENDING)
            ->sum('amount');

        // تحديث رصيد المستحقات (receivable)
        AccountBalance::updateOrCreate(
            [
                'party_id' => $partyId,
                'counterparty_id' => $counterpartyId,
                'balance_type' => AccountBalance::TYPE_RECEIVABLE,
            ],
            [
                'pending_amount' => $receivable,
                'last_transaction_at' => now(),
                'last_calculated_at' => now(),
            ]
        );

        // تحديث رصيد المديونيات (payable)
        AccountBalance::updateOrCreate(
            [
                'party_id' => $partyId,
                'counterparty_id' => $counterpartyId,
                'balance_type' => AccountBalance::TYPE_PAYABLE,
            ],
            [
                'pending_amount' => $payable,
                'last_transaction_at' => now(),
                'last_calculated_at' => now(),
            ]
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // REPORTING QUERIES - استعلامات التقارير
    // ═══════════════════════════════════════════════════════════════

    /**
     * الحصول على ملخص المبيعات من Ledger فقط
     */
    public function getSalesSummaryFromLedger(
        ?int $merchantId = null,
        ?\Carbon\Carbon $startDate = null,
        ?\Carbon\Carbon $endDate = null
    ): array {
        $query = AccountingLedger::query();

        if ($merchantId) {
            $merchant = $this->getOrCreateMerchantParty($merchantId);
            $query->where('to_party_id', $merchant->id);
        }

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        return [
            'total_sales' => (clone $query)
                ->where('entry_type', AccountingLedger::ENTRY_SALE_REVENUE)
                ->sum('amount'),

            'total_commission' => (clone $query)
                ->where('entry_type', AccountingLedger::ENTRY_COMMISSION_EARNED)
                ->sum('amount'),

            'total_tax' => (clone $query)
                ->where('entry_type', AccountingLedger::ENTRY_TAX_COLLECTED)
                ->sum('amount'),

            'total_shipping' => (clone $query)
                ->whereIn('entry_type', [
                    AccountingLedger::ENTRY_SHIPPING_FEE_PLATFORM,
                    AccountingLedger::ENTRY_SHIPPING_FEE_MERCHANT,
                ])
                ->sum('amount'),

            'pending_amount' => (clone $query)
                ->where('debt_status', AccountingLedger::DEBT_PENDING)
                ->sum('amount'),

            'settled_amount' => (clone $query)
                ->where('debt_status', AccountingLedger::DEBT_SETTLED)
                ->sum('amount'),
        ];
    }

    /**
     * الحصول على كشف حساب التاجر
     */
    public function getMerchantStatement(
        int $merchantId,
        ?\Carbon\Carbon $startDate = null,
        ?\Carbon\Carbon $endDate = null
    ): array {
        $merchant = $this->getOrCreateMerchantParty($merchantId);
        $platform = $this->getPlatformParty();

        $query = AccountingLedger::where(function ($q) use ($merchant) {
            $q->where('from_party_id', $merchant->id)
                ->orWhere('to_party_id', $merchant->id);
        });

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        $entries = $query->orderBy('transaction_date')->get();

        // حساب الملخص من القيود فقط
        $summary = [
            'total_sales' => $entries
                ->where('entry_type', AccountingLedger::ENTRY_SALE_REVENUE)
                ->where('to_party_id', $merchant->id)
                ->sum('amount'),

            'total_commission' => $entries
                ->where('entry_type', AccountingLedger::ENTRY_COMMISSION_EARNED)
                ->where('from_party_id', $merchant->id)
                ->sum('amount'),

            'total_tax' => $entries
                ->where('entry_type', AccountingLedger::ENTRY_TAX_COLLECTED)
                ->where('from_party_id', $merchant->id)
                ->sum('amount'),

            'total_shipping_earned' => $entries
                ->where('entry_type', AccountingLedger::ENTRY_SHIPPING_FEE_MERCHANT)
                ->where('to_party_id', $merchant->id)
                ->sum('amount'),

            'total_settlements_received' => $entries
                ->where('entry_type', AccountingLedger::ENTRY_SETTLEMENT_PAYMENT)
                ->where('to_party_id', $merchant->id)
                ->sum('amount'),
        ];

        // الصافي المستحق
        $summary['net_receivable'] = $summary['total_sales']
            + $summary['total_shipping_earned']
            - $summary['total_commission']
            - $summary['total_tax'];

        // المتبقي
        $summary['balance_due'] = $summary['net_receivable'] - $summary['total_settlements_received'];

        return [
            'merchant' => $merchant,
            'summary' => $summary,
            'entries' => $entries,
            'period' => [
                'start' => $startDate?->format('Y-m-d'),
                'end' => $endDate?->format('Y-m-d'),
            ],
        ];
    }

    /**
     * تقرير الضرائب من Ledger فقط
     */
    public function getTaxReport(
        ?\Carbon\Carbon $startDate = null,
        ?\Carbon\Carbon $endDate = null
    ): array {
        $query = AccountingLedger::where('entry_type', AccountingLedger::ENTRY_TAX_COLLECTED);

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        $entries = $query->orderBy('transaction_date', 'desc')->get();

        // تجميع حسب الموقع الضريبي
        $byLocation = $entries->groupBy(function ($entry) {
            return $entry->metadata['tax_location'] ?? __('Unknown');
        })->map(function ($locationEntries) {
            return [
                'count' => $locationEntries->count(),
                'total' => $locationEntries->sum('amount'),
                'pending' => $locationEntries->where('debt_status', AccountingLedger::DEBT_PENDING)->sum('amount'),
                'settled' => $locationEntries->where('debt_status', AccountingLedger::DEBT_SETTLED)->sum('amount'),
            ];
        });

        return [
            'entries' => $entries,
            'by_location' => $byLocation,
            'total' => [
                'collected' => $entries->sum('amount'),
                'pending' => $entries->where('debt_status', AccountingLedger::DEBT_PENDING)->sum('amount'),
                'settled' => $entries->where('debt_status', AccountingLedger::DEBT_SETTLED)->sum('amount'),
            ],
            'period' => [
                'start' => $startDate?->format('Y-m-d'),
                'end' => $endDate?->format('Y-m-d'),
            ],
        ];
    }

    /**
     * تقرير الذمم المدينة / الدائنة
     */
    public function getReceivablesPayablesReport(): array
    {
        $platform = $this->getPlatformParty();

        // الذمم المدينة (مستحق للمنصة)
        $receivables = AccountBalance::where('party_id', $platform->id)
            ->where('balance_type', 'receivable')
            ->where('pending_amount', '>', 0)
            ->with('counterparty')
            ->get()
            ->groupBy(function ($balance) {
                return $balance->counterparty->party_type;
            });

        // الذمم الدائنة (مستحق على المنصة)
        $payables = AccountBalance::where('party_id', $platform->id)
            ->where('balance_type', 'payable')
            ->where('pending_amount', '>', 0)
            ->with('counterparty')
            ->get()
            ->groupBy(function ($balance) {
                return $balance->counterparty->party_type;
            });

        return [
            'receivables' => [
                'from_merchants' => $receivables->get('merchant', collect()),
                'from_couriers' => $receivables->get('courier', collect()),
                'from_shipping' => $receivables->get('shipping_provider', collect()),
                'total' => $receivables->flatten()->sum('pending_amount'),
            ],
            'payables' => [
                'to_merchants' => $payables->get('merchant', collect()),
                'to_tax_authority' => $payables->get('tax_authority', collect()),
                'to_shipping' => $payables->get('shipping_provider', collect()),
                'total' => $payables->flatten()->sum('pending_amount'),
            ],
            'net_position' => $receivables->flatten()->sum('pending_amount')
                - $payables->flatten()->sum('pending_amount'),
        ];
    }
}
