<?php

namespace App\Services;

use App\Models\AccountParty;
use App\Models\AccountingLedger;
use App\Models\AccountBalance;
use App\Models\SettlementBatch;
use App\Models\MerchantPurchase;
use App\Models\Purchase;
use App\Models\User;
use App\Models\Courier;
use Illuminate\Support\Facades\DB;

/**
 * AccountLedgerService - الخدمة المركزية للنظام المحاسبي
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 * كل العمليات المحاسبية يجب أن تمر عبر هذه الخدمة
 * ═══════════════════════════════════════════════════════════════════════════════
 */
class AccountLedgerService
{
    // ═══════════════════════════════════════════════════════════════
    // PARTY MANAGEMENT - إدارة الأطراف
    // ═══════════════════════════════════════════════════════════════

    /**
     * الحصول على طرف المنصة
     */
    public function getPlatformParty(): AccountParty
    {
        return AccountParty::platform();
    }

    /**
     * الحصول على/إنشاء طرف من المرجع
     */
    public function getPartyFromReference(string $type, $reference): AccountParty
    {
        return match ($type) {
            'merchant' => AccountParty::forMerchant($reference),
            'courier' => AccountParty::forCourier($reference),
            'shipping' => AccountParty::forShippingProvider($reference),
            'payment' => AccountParty::forPaymentProvider($reference),
            default => throw new \InvalidArgumentException("Unknown party type: {$type}"),
        };
    }

    /**
     * مزامنة جميع التجار كأطراف
     */
    public function syncMerchantParties(): int
    {
        $count = 0;
        User::where('is_vendor', 1)->chunk(100, function ($merchants) use (&$count) {
            foreach ($merchants as $merchant) {
                AccountParty::forMerchant($merchant);
                $count++;
            }
        });
        return $count;
    }

    /**
     * مزامنة جميع المناديب كأطراف
     */
    public function syncCourierParties(): int
    {
        $count = 0;
        Courier::chunk(100, function ($couriers) use (&$count) {
            foreach ($couriers as $courier) {
                AccountParty::forCourier($courier);
                $count++;
            }
        });
        return $count;
    }

    /**
     * مزامنة شركات الشحن من الجداول
     */
    public function syncShippingProviders(): int
    {
        $providers = [
            'tryoto' => 'Tryoto',
            'aramex' => 'Aramex',
            'dhl' => 'DHL',
            'smsa' => 'SMSA',
            'fetchr' => 'Fetchr',
        ];

        // إضافة من جدول shippings إذا وجد providers مختلفة
        $dbProviders = DB::table('shippings')
            ->whereNotNull('provider')
            ->distinct()
            ->pluck('name', 'provider');

        foreach ($dbProviders as $code => $name) {
            $providers[$code] = $name;
        }

        $count = 0;
        foreach ($providers as $code => $name) {
            AccountParty::forShippingProvider($code, $name);
            $count++;
        }

        return $count;
    }

    /**
     * مزامنة شركات الدفع
     */
    public function syncPaymentProviders(): int
    {
        $providers = [
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'myfatoorah' => 'MyFatoorah',
            'tap' => 'Tap Payments',
            'moyasar' => 'Moyasar',
            'cod' => 'Cash on Delivery',
        ];

        $count = 0;
        foreach ($providers as $code => $name) {
            AccountParty::forPaymentProvider($code, $name);
            $count++;
        }

        return $count;
    }

    // ═══════════════════════════════════════════════════════════════
    // DEBT RECORDING - تسجيل الديون
    // ═══════════════════════════════════════════════════════════════

    /**
     * تسجيل دين جديد عند الشيك-آوت
     *
     * @param MerchantPurchase $mp
     * @param array $debtData Contains: from_party, to_party, amount, description
     * @param string|null $entryType Optional entry type (SALE_REVENUE, COD_PENDING, etc.)
     * @return AccountingLedger
     */
    public function recordDebt(
        MerchantPurchase $mp,
        AccountParty $fromParty,
        AccountParty $toParty,
        float $amount,
        string $description = '',
        ?string $descriptionAr = null,
        array $metadata = [],
        ?string $entryType = null
    ): AccountingLedger {
        return DB::transaction(function () use ($mp, $fromParty, $toParty, $amount, $description, $descriptionAr, $metadata, $entryType) {
            // Determine entry_type from metadata if not provided
            $resolvedEntryType = $entryType ?? $this->resolveEntryType($metadata);

            // إنشاء سجل في الـ Ledger
            $ledger = AccountingLedger::create([
                'purchase_id' => $mp->purchase_id,
                'merchant_purchase_id' => $mp->id,
                'from_party_id' => $fromParty->id,
                'to_party_id' => $toParty->id,
                'amount' => $amount,
                'transaction_type' => AccountingLedger::TYPE_DEBT,
                'entry_type' => $resolvedEntryType,
                'direction' => AccountingLedger::DIRECTION_CREDIT,
                'debt_status' => AccountingLedger::DEBT_PENDING,
                'description' => $description,
                'description_ar' => $descriptionAr ?? $description,
                'metadata' => $metadata,
                'status' => AccountingLedger::STATUS_PENDING,
            ]);

            // تحديث رصيد الطرف المستحق له
            $this->updateBalance($toParty, $fromParty, AccountBalance::TYPE_RECEIVABLE, $amount);

            // تحديث رصيد الطرف المدين
            $this->updateBalance($fromParty, $toParty, AccountBalance::TYPE_PAYABLE, $amount);

            return $ledger;
        });
    }

    /**
     * تسجيل رسوم (ضريبة، عمولة، إلخ)
     *
     * الرسوم تُسجل للمنصة كإيرادات/التزامات
     *
     * @param MerchantPurchase $mp
     * @param AccountParty $platform
     * @param float $amount
     * @param string $feeType (tax_collected, commission, platform_fee)
     * @param string $description
     * @param string|null $descriptionAr
     * @param array $metadata
     * @return AccountingLedger
     */
    public function recordFee(
        MerchantPurchase $mp,
        AccountParty $platform,
        float $amount,
        string $feeType,
        string $description = '',
        ?string $descriptionAr = null,
        array $metadata = []
    ): AccountingLedger {
        $metadata['fee_type'] = $feeType;

        // Map fee_type to entry_type
        $entryType = match ($feeType) {
            'tax_collected' => AccountingLedger::ENTRY_TAX_COLLECTED,
            'commission' => AccountingLedger::ENTRY_COMMISSION_EARNED,
            'platform_fee' => AccountingLedger::ENTRY_PLATFORM_FEE,
            'packing_fee' => AccountingLedger::ENTRY_PACKING_FEE_PLATFORM,
            'shipping_fee' => AccountingLedger::ENTRY_SHIPPING_FEE_PLATFORM,
            default => AccountingLedger::ENTRY_PLATFORM_FEE,
        };

        return AccountingLedger::create([
            'purchase_id' => $mp->purchase_id,
            'merchant_purchase_id' => $mp->id,
            'from_party_id' => $platform->id, // Platform receives
            'to_party_id' => $platform->id,   // Internal record
            'amount' => $amount,
            'transaction_type' => AccountingLedger::TYPE_FEE,
            'entry_type' => $entryType,
            'direction' => AccountingLedger::DIRECTION_CREDIT,
            'debt_status' => AccountingLedger::DEBT_PENDING,
            'description' => $description,
            'description_ar' => $descriptionAr ?? $description,
            'metadata' => $metadata,
            'status' => AccountingLedger::STATUS_COMPLETED, // Fees are completed immediately
        ]);
    }

    /**
     * Resolve entry_type from metadata
     */
    protected function resolveEntryType(array $metadata): ?string
    {
        $type = $metadata['type'] ?? null;

        return match ($type) {
            'platform_to_merchant' => AccountingLedger::ENTRY_SALE_REVENUE,
            'merchant_to_platform' => AccountingLedger::ENTRY_COMMISSION_EARNED,
            'courier_to_platform', 'courier_to_merchant' => AccountingLedger::ENTRY_COD_PENDING,
            'shipping_to_platform', 'shipping_to_merchant' => AccountingLedger::ENTRY_COD_PENDING,
            default => null,
        };
    }

    /**
     * تسجيل جميع ديون MerchantPurchase
     */
    public function recordDebtsForMerchantPurchase(MerchantPurchase $mp): array
    {
        $ledgerEntries = [];
        $platform = $this->getPlatformParty();
        $merchant = AccountParty::forMerchant($mp->merchant);

        // === 1. Platform ↔ Merchant ===
        if ($mp->platform_owes_merchant > 0) {
            $ledgerEntries[] = $this->recordDebt(
                $mp,
                $platform,
                $merchant,
                $mp->platform_owes_merchant,
                'Platform owes merchant for purchase #' . $mp->purchase_number,
                'المنصة مدينة للتاجر - طلب #' . $mp->purchase_number,
                ['type' => 'platform_to_merchant', 'net_amount' => $mp->net_amount]
            );
        }

        if ($mp->merchant_owes_platform > 0) {
            // تفصيل: العمولة منفصلة عن الضريبة
            $commissionOnly = $mp->commission_amount ?? 0;
            $taxAmount = $mp->tax_amount ?? 0;
            $platformFees = ($mp->platform_shipping_fee ?? 0) + ($mp->platform_packing_fee ?? 0);

            $ledgerEntries[] = $this->recordDebt(
                $mp,
                $merchant,
                $platform,
                $mp->merchant_owes_platform,
                'Merchant owes platform (commission + tax + fees) for purchase #' . $mp->purchase_number,
                'التاجر مدين للمنصة (عمولة + ضريبة + رسوم) - طلب #' . $mp->purchase_number,
                [
                    'type' => 'merchant_to_platform',
                    'commission' => $commissionOnly,
                    'tax' => $taxAmount,
                    'platform_fees' => $platformFees,
                    'breakdown' => [
                        'commission' => $commissionOnly,
                        'tax' => $taxAmount,
                        'platform_shipping_fee' => $mp->platform_shipping_fee ?? 0,
                        'platform_packing_fee' => $mp->platform_packing_fee ?? 0,
                    ]
                ]
            );
        }

        // === 2. Tax Collection (تحصيل الضريبة) ===
        // تسجيل الضريبة المحصلة كرسوم منفصلة للمتابعة
        if (($mp->tax_amount ?? 0) > 0) {
            $ledgerEntries[] = $this->recordFee(
                $mp,
                $platform,
                $mp->tax_amount,
                'tax_collected',
                'Tax collected for purchase #' . $mp->purchase_number,
                'ضريبة محصلة - طلب #' . $mp->purchase_number,
                [
                    'tax_rate' => $mp->purchase->tax ?? 0,
                    'tax_location' => $mp->purchase->tax_location ?? null,
                ]
            );
        }

        // === 3. Courier ↔ Platform/Merchant ===
        if ($mp->courier_owes_platform > 0 && $mp->courier_id) {
            $courier = Courier::find($mp->courier_id);
            if ($courier) {
                $courierParty = AccountParty::forCourier($courier);
                $ledgerEntries[] = $this->recordDebt(
                    $mp,
                    $courierParty,
                    $platform,
                    $mp->courier_owes_platform,
                    'Courier owes platform (COD collected) for purchase #' . $mp->purchase_number,
                    'المندوب مدين للمنصة (COD محصّل) - طلب #' . $mp->purchase_number,
                    ['type' => 'courier_to_platform', 'cod_amount' => $mp->cod_amount]
                );
            }
        }

        if ($mp->courier_owes_merchant > 0 && $mp->courier_id) {
            $courier = Courier::find($mp->courier_id);
            if ($courier) {
                $courierParty = AccountParty::forCourier($courier);
                $ledgerEntries[] = $this->recordDebt(
                    $mp,
                    $courierParty,
                    $merchant,
                    $mp->courier_owes_merchant,
                    'Courier owes merchant (COD collected) for purchase #' . $mp->purchase_number,
                    'المندوب مدين للتاجر (COD محصّل) - طلب #' . $mp->purchase_number,
                    ['type' => 'courier_to_merchant', 'cod_amount' => $mp->cod_amount]
                );
            }
        }

        // === 3. Shipping Company ↔ Platform/Merchant ===
        if ($mp->shipping_company_owes_platform > 0 && $mp->delivery_provider) {
            $shippingParty = AccountParty::forShippingProvider($mp->delivery_provider);
            $ledgerEntries[] = $this->recordDebt(
                $mp,
                $shippingParty,
                $platform,
                $mp->shipping_company_owes_platform,
                'Shipping company owes platform (COD collected) for purchase #' . $mp->purchase_number,
                'شركة الشحن مدينة للمنصة (COD محصّل) - طلب #' . $mp->purchase_number,
                ['type' => 'shipping_to_platform', 'provider' => $mp->delivery_provider]
            );
        }

        if ($mp->shipping_company_owes_merchant > 0 && $mp->delivery_provider) {
            $shippingParty = AccountParty::forShippingProvider($mp->delivery_provider);
            $ledgerEntries[] = $this->recordDebt(
                $mp,
                $shippingParty,
                $merchant,
                $mp->shipping_company_owes_merchant,
                'Shipping company owes merchant (COD collected) for purchase #' . $mp->purchase_number,
                'شركة الشحن مدينة للتاجر (COD محصّل) - طلب #' . $mp->purchase_number,
                ['type' => 'shipping_to_merchant', 'provider' => $mp->delivery_provider]
            );
        }

        return $ledgerEntries;
    }

    // ═══════════════════════════════════════════════════════════════
    // SETTLEMENT - التسوية
    // ═══════════════════════════════════════════════════════════════

    /**
     * تسجيل تسوية دين
     */
    public function recordSettlement(
        AccountParty $fromParty,
        AccountParty $toParty,
        float $amount,
        string $paymentMethod,
        ?string $paymentReference = null,
        ?int $createdBy = null
    ): AccountingLedger {
        return DB::transaction(function () use ($fromParty, $toParty, $amount, $paymentMethod, $paymentReference, $createdBy) {
            $ledger = AccountingLedger::create([
                'from_party_id' => $fromParty->id,
                'to_party_id' => $toParty->id,
                'amount' => $amount,
                'transaction_type' => AccountingLedger::TYPE_SETTLEMENT,
                'description' => "Settlement payment via {$paymentMethod}",
                'description_ar' => "تسوية عبر {$paymentMethod}",
                'metadata' => [
                    'payment_method' => $paymentMethod,
                    'payment_reference' => $paymentReference,
                ],
                'status' => AccountingLedger::STATUS_COMPLETED,
                'settled_at' => now(),
                'created_by' => $createdBy,
                'settled_by' => $createdBy,
            ]);

            // تحديث الأرصدة
            $receivableBalance = AccountBalance::getOrCreate(
                $toParty->id,
                $fromParty->id,
                AccountBalance::TYPE_RECEIVABLE
            );
            $receivableBalance->recordTransaction($amount, true);

            $payableBalance = AccountBalance::getOrCreate(
                $fromParty->id,
                $toParty->id,
                AccountBalance::TYPE_PAYABLE
            );
            $payableBalance->recordTransaction($amount, true);

            // تحديث الـ pending ledger entries
            $this->markRelatedDebtsAsSettled($fromParty, $toParty, $amount);

            return $ledger;
        });
    }

    /**
     * إنشاء دفعة تسوية
     */
    public function createSettlementBatch(
        AccountParty $fromParty,
        AccountParty $toParty,
        float $amount,
        string $paymentMethod,
        ?int $createdBy = null
    ): SettlementBatch {
        return SettlementBatch::create([
            'from_party_id' => $fromParty->id,
            'to_party_id' => $toParty->id,
            'total_amount' => $amount,
            'payment_method' => $paymentMethod,
            'status' => SettlementBatch::STATUS_DRAFT,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * تحديث الديون المعلقة كمسواة
     */
    protected function markRelatedDebtsAsSettled(AccountParty $fromParty, AccountParty $toParty, float $amount): void
    {
        $remaining = $amount;

        $pendingDebts = AccountingLedger::where('from_party_id', $fromParty->id)
            ->where('to_party_id', $toParty->id)
            ->where('transaction_type', AccountingLedger::TYPE_DEBT)
            ->where('status', AccountingLedger::STATUS_PENDING)
            ->orderBy('created_at')
            ->get();

        foreach ($pendingDebts as $debt) {
            if ($remaining <= 0) break;

            if ($debt->amount <= $remaining) {
                $debt->markAsCompleted();
                $remaining -= $debt->amount;
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // BALANCE UPDATES - تحديث الأرصدة
    // ═══════════════════════════════════════════════════════════════

    /**
     * تحديث رصيد
     */
    protected function updateBalance(AccountParty $party, AccountParty $counterparty, string $type, float $amount): void
    {
        $balance = AccountBalance::getOrCreate($party->id, $counterparty->id, $type);
        $balance->recordTransaction($amount);
    }

    // ═══════════════════════════════════════════════════════════════
    // REPORTS - التقارير
    // ═══════════════════════════════════════════════════════════════

    /**
     * الحصول على ملخص حسابات طرف
     */
    public function getPartySummary(AccountParty $party): array
    {
        // مستحق للطرف (آخرون مدينون له)
        $receivables = AccountBalance::where('party_id', $party->id)
            ->where('balance_type', AccountBalance::TYPE_RECEIVABLE)
            ->where('pending_amount', '>', 0)
            ->with('counterparty')
            ->get();

        // مستحق على الطرف (هو مدين لآخرين)
        $payables = AccountBalance::where('party_id', $party->id)
            ->where('balance_type', AccountBalance::TYPE_PAYABLE)
            ->where('pending_amount', '>', 0)
            ->with('counterparty')
            ->get();

        $totalReceivable = $receivables->sum('pending_amount');
        $totalPayable = $payables->sum('pending_amount');

        return [
            'party' => $party,
            'receivables' => $receivables,
            'payables' => $payables,
            'total_receivable' => $totalReceivable,
            'total_payable' => $totalPayable,
            'net_balance' => $totalReceivable - $totalPayable,
            'is_net_positive' => ($totalReceivable - $totalPayable) >= 0,
        ];
    }

    /**
     * الحصول على كشف حساب تفصيلي
     */
    public function getAccountStatement(
        AccountParty $party,
        ?AccountParty $counterparty = null,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null
    ): array {
        $query = AccountingLedger::forParty($party->id)
            ->with(['fromParty', 'toParty', 'purchase', 'merchantPurchase'])
            ->orderBy('transaction_date', 'desc');

        if ($counterparty) {
            $query->betweenParties($party->id, $counterparty->id);
        }

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        $transactions = $query->get();

        // حساب الأرصدة المتحركة
        $runningBalance = 0;
        $statement = [];

        foreach ($transactions->reverse() as $txn) {
            $isCredit = $txn->to_party_id === $party->id;
            $isDebit = $txn->from_party_id === $party->id;

            if ($isCredit && $txn->transaction_type === AccountingLedger::TYPE_DEBT) {
                $runningBalance += $txn->amount;
            } elseif ($isDebit && $txn->transaction_type === AccountingLedger::TYPE_SETTLEMENT) {
                $runningBalance -= $txn->amount;
            }

            $statement[] = [
                'transaction' => $txn,
                'is_credit' => $isCredit,
                'is_debit' => $isDebit,
                'running_balance' => $runningBalance,
            ];
        }

        return [
            'party' => $party,
            'counterparty' => $counterparty,
            'statement' => array_reverse($statement),
            'opening_balance' => 0,
            'closing_balance' => $runningBalance,
            'total_credits' => $transactions->where('to_party_id', $party->id)->sum('amount'),
            'total_debits' => $transactions->where('from_party_id', $party->id)->sum('amount'),
        ];
    }

    /**
     * الحصول على ملخص لجميع الأطراف حسب النوع
     */
    public function getSummaryByPartyType(string $partyType): array
    {
        $parties = AccountParty::where('party_type', $partyType)
            ->where('is_active', true)
            ->get();

        $summaries = [];
        foreach ($parties as $party) {
            $summaries[] = $this->getPartySummary($party);
        }

        return [
            'party_type' => $partyType,
            'parties' => $summaries,
            'total_receivable' => collect($summaries)->sum('total_receivable'),
            'total_payable' => collect($summaries)->sum('total_payable'),
        ];
    }

    /**
     * تقرير شامل للمنصة
     */
    public function getPlatformDashboard(): array
    {
        $platform = $this->getPlatformParty();
        $summary = $this->getPartySummary($platform);

        // تجميع حسب نوع الطرف
        $byType = [];
        foreach ($summary['receivables'] as $balance) {
            $type = $balance->counterparty->party_type;
            if (!isset($byType[$type])) {
                $byType[$type] = ['receivable' => 0, 'payable' => 0];
            }
            $byType[$type]['receivable'] += $balance->pending_amount;
        }

        foreach ($summary['payables'] as $balance) {
            $type = $balance->counterparty->party_type;
            if (!isset($byType[$type])) {
                $byType[$type] = ['receivable' => 0, 'payable' => 0];
            }
            $byType[$type]['payable'] += $balance->pending_amount;
        }

        return [
            'platform_summary' => $summary,
            'by_party_type' => $byType,
            'merchants_summary' => $this->getSummaryByPartyType(AccountParty::TYPE_MERCHANT),
            'couriers_summary' => $this->getSummaryByPartyType(AccountParty::TYPE_COURIER),
            'shipping_summary' => $this->getSummaryByPartyType(AccountParty::TYPE_SHIPPING_PROVIDER),
            'payment_summary' => $this->getSummaryByPartyType(AccountParty::TYPE_PAYMENT_PROVIDER),
        ];
    }
}
