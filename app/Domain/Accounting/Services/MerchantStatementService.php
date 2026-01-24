<?php

namespace App\Domain\Accounting\Services;

use App\Models\AccountParty;
use App\Models\AccountingLedger;
use App\Models\AccountBalance;
use App\Models\MonetaryUnit;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * MerchantStatementService - خدمة كشف حساب التاجر
 *
 * القاعدة الأساسية: كل البيانات تأتي من Ledger فقط
 * لا قراءة مباشرة من MerchantPurchase في التقارير
 */
class MerchantStatementService
{
    protected AccountingEntryService $entryService;

    public function __construct(AccountingEntryService $entryService)
    {
        $this->entryService = $entryService;
    }

    // ═══════════════════════════════════════════════════════════════
    // STATEMENT GENERATION - إنشاء كشف الحساب
    // ═══════════════════════════════════════════════════════════════

    /**
     * إنشاء كشف حساب تاجر كامل
     *
     * @param int $merchantId
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return MerchantStatement
     */
    public function generateStatement(
        int $merchantId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): MerchantStatement {
        $merchant = $this->entryService->getOrCreateMerchantParty($merchantId);
        $platform = $this->entryService->getPlatformParty();

        // جلب كل القيود من Ledger فقط
        $entries = $this->getEntriesForMerchant($merchant, $startDate, $endDate);

        // حساب الملخص من القيود
        $summary = $this->calculateSummary($entries, $merchant);

        // حساب الرصيد الافتتاحي (قبل الفترة)
        $openingBalance = $this->calculateOpeningBalance($merchant, $startDate);

        // حساب الرصيد الختامي
        $closingBalance = $openingBalance + $summary['net_movement'];

        return new MerchantStatement(
            merchant: $merchant,
            entries: $entries,
            summary: $summary,
            openingBalance: $openingBalance,
            closingBalance: $closingBalance,
            startDate: $startDate,
            endDate: $endDate
        );
    }

    /**
     * جلب القيود للتاجر من Ledger
     */
    protected function getEntriesForMerchant(
        AccountParty $merchant,
        ?Carbon $startDate,
        ?Carbon $endDate
    ): Collection {
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

        return $query
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * حساب الملخص من القيود
     */
    protected function calculateSummary(Collection $entries, AccountParty $merchant): array
    {
        // المبيعات (دائن للتاجر)
        $totalSales = $entries
            ->where('entry_type', AccountingLedger::ENTRY_SALE_REVENUE)
            ->where('to_party_id', $merchant->id)
            ->sum('amount');

        // العمولات (مدين على التاجر)
        $totalCommission = $entries
            ->where('entry_type', AccountingLedger::ENTRY_COMMISSION_EARNED)
            ->where('from_party_id', $merchant->id)
            ->sum('amount');

        // الضرائب (مدين على التاجر - محصل للجهة الضريبية)
        $totalTax = $entries
            ->where('entry_type', AccountingLedger::ENTRY_TAX_COLLECTED)
            ->where('from_party_id', $merchant->id)
            ->sum('amount');

        // إيرادات الشحن (دائن للتاجر)
        $shippingEarned = $entries
            ->where('entry_type', AccountingLedger::ENTRY_SHIPPING_FEE_MERCHANT)
            ->where('to_party_id', $merchant->id)
            ->sum('amount');

        // التسويات المستلمة (مدين - نقص في المستحق)
        $settlementsReceived = $entries
            ->where('entry_type', AccountingLedger::ENTRY_SETTLEMENT_PAYMENT)
            ->where('to_party_id', $merchant->id)
            ->sum('amount');

        // المرتجعات (مدين على التاجر)
        $totalRefunds = $entries
            ->where('entry_type', AccountingLedger::ENTRY_REFUND)
            ->where('from_party_id', $merchant->id)
            ->sum('amount');

        // الإلغاءات (عكس القيود)
        $totalCancellations = $entries
            ->where('entry_type', AccountingLedger::ENTRY_CANCELLATION_REVERSAL)
            ->sum('amount');

        // الصافي المستحق = المبيعات + شحن التاجر - العمولة - الضريبة
        $netReceivable = $totalSales + $shippingEarned - $totalCommission - $totalTax - $totalRefunds;

        // الرصيد المتبقي = الصافي المستحق - التسويات
        $balanceDue = $netReceivable - $settlementsReceived;

        // الحركة الصافية للفترة
        $netMovement = $totalSales + $shippingEarned - $totalCommission - $totalTax
            - $settlementsReceived - $totalRefunds;

        return [
            'total_sales' => $totalSales,
            'total_commission' => $totalCommission,
            'total_tax' => $totalTax,
            'shipping_earned' => $shippingEarned,
            'settlements_received' => $settlementsReceived,
            'total_refunds' => $totalRefunds,
            'total_cancellations' => $totalCancellations,
            'net_receivable' => $netReceivable,
            'balance_due' => $balanceDue,
            'net_movement' => $netMovement,
            'transaction_count' => $entries->count(),
        ];
    }

    /**
     * حساب الرصيد الافتتاحي
     */
    protected function calculateOpeningBalance(AccountParty $merchant, ?Carbon $beforeDate): float
    {
        if (!$beforeDate) {
            return 0;
        }

        $query = AccountingLedger::where(function ($q) use ($merchant) {
            $q->where('from_party_id', $merchant->id)
                ->orWhere('to_party_id', $merchant->id);
        })->where('transaction_date', '<', $beforeDate);

        $entries = $query->get();

        // نفس منطق calculateSummary لكن قبل الفترة
        $credits = $entries
            ->where('to_party_id', $merchant->id)
            ->where('direction', AccountingLedger::DIRECTION_CREDIT)
            ->sum('amount');

        $debits = $entries
            ->where('from_party_id', $merchant->id)
            ->where('direction', AccountingLedger::DIRECTION_DEBIT)
            ->sum('amount');

        $settlementsReceived = $entries
            ->where('entry_type', AccountingLedger::ENTRY_SETTLEMENT_PAYMENT)
            ->where('to_party_id', $merchant->id)
            ->sum('amount');

        return $credits - $debits - $settlementsReceived;
    }

    // ═══════════════════════════════════════════════════════════════
    // DETAILED BREAKDOWN - التفصيل
    // ═══════════════════════════════════════════════════════════════

    /**
     * تفصيل المبيعات حسب اليوم
     */
    public function getSalesByDay(
        int $merchantId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): Collection {
        $merchant = $this->entryService->getOrCreateMerchantParty($merchantId);

        $query = AccountingLedger::where('to_party_id', $merchant->id)
            ->where('entry_type', AccountingLedger::ENTRY_SALE_REVENUE);

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        return $query->selectRaw('DATE(transaction_date) as date, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * تفصيل العمولات حسب اليوم
     */
    public function getCommissionsByDay(
        int $merchantId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): Collection {
        $merchant = $this->entryService->getOrCreateMerchantParty($merchantId);

        $query = AccountingLedger::where('from_party_id', $merchant->id)
            ->where('entry_type', AccountingLedger::ENTRY_COMMISSION_EARNED);

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        return $query->selectRaw('DATE(transaction_date) as date, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    // ═══════════════════════════════════════════════════════════════
    // PENDING AMOUNTS - المبالغ المعلقة
    // ═══════════════════════════════════════════════════════════════

    /**
     * الحصول على المبالغ المعلقة للتاجر
     */
    public function getPendingAmounts(int $merchantId): array
    {
        $merchant = $this->entryService->getOrCreateMerchantParty($merchantId);
        $platform = $this->entryService->getPlatformParty();

        // المستحق للتاجر من المنصة
        $fromPlatform = AccountBalance::where('party_id', $merchant->id)
            ->where('counterparty_id', $platform->id)
            ->where('balance_type', 'receivable')
            ->value('pending_amount') ?? 0;

        // المستحق على التاجر للمنصة
        $toPlatform = AccountBalance::where('party_id', $merchant->id)
            ->where('counterparty_id', $platform->id)
            ->where('balance_type', 'payable')
            ->value('pending_amount') ?? 0;

        // صافي المستحق
        $netReceivable = $fromPlatform - $toPlatform;

        return [
            'from_platform' => $fromPlatform,
            'to_platform' => $toPlatform,
            'net_receivable' => $netReceivable,
        ];
    }

    /**
     * الحصول على الطلبات غير المسواة
     */
    public function getUnsettledOrders(int $merchantId): Collection
    {
        $merchant = $this->entryService->getOrCreateMerchantParty($merchantId);

        return AccountingLedger::where('to_party_id', $merchant->id)
            ->where('entry_type', AccountingLedger::ENTRY_SALE_REVENUE)
            ->where('debt_status', AccountingLedger::DEBT_PENDING)
            ->with(['merchantPurchase'])
            ->orderBy('transaction_date')
            ->get();
    }
}

/**
 * MerchantStatement - كائن كشف الحساب
 */
class MerchantStatement
{
    public function __construct(
        public readonly AccountParty $merchant,
        public readonly Collection $entries,
        public readonly array $summary,
        public readonly float $openingBalance,
        public readonly float $closingBalance,
        public readonly ?Carbon $startDate,
        public readonly ?Carbon $endDate
    ) {}

    /**
     * تحويل الحركات لصيغة الكشف
     */
    public function getFormattedEntries(): Collection
    {
        $runningBalance = $this->openingBalance;

        return $this->entries->map(function ($entry) use (&$runningBalance) {
            $isCredit = $entry->to_party_id === $this->merchant->id
                && $entry->direction === AccountingLedger::DIRECTION_CREDIT;

            $isDebit = $entry->from_party_id === $this->merchant->id
                || $entry->direction === AccountingLedger::DIRECTION_DEBIT;

            // حساب الأثر على الرصيد
            $credit = $isCredit ? $entry->amount : 0;
            $debit = $isDebit ? $entry->amount : 0;

            // استثناء: التسويات المستلمة تخفض الرصيد
            if ($entry->entry_type === AccountingLedger::ENTRY_SETTLEMENT_PAYMENT
                && $entry->to_party_id === $this->merchant->id) {
                $credit = 0;
                $debit = $entry->amount;
            }

            $runningBalance = $runningBalance + $credit - $debit;

            return [
                'date' => $entry->transaction_date->format('Y-m-d'),
                'ref' => $entry->transaction_ref,
                'entry_type' => $entry->entry_type,
                'entry_type_ar' => $entry->getEntryTypeNameAr(),
                'description' => $entry->description,
                'description_ar' => $entry->description_ar,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance,
                'debt_status' => $entry->debt_status,
                'debt_status_ar' => $entry->getDebtStatusNameAr(),
                'merchant_purchase_id' => $entry->merchant_purchase_id,
                'purchase_id' => $entry->purchase_id,
            ];
        });
    }

    /**
     * الحصول على العملة الافتراضية
     */
    public function getMonetaryUnit(): MonetaryUnit
    {
        return monetaryUnit()->getDefault();
    }

    /**
     * تنسيق الفترة
     */
    public function getPeriodLabel(): string
    {
        if ($this->startDate && $this->endDate) {
            return $this->startDate->format('Y-m-d') . ' - ' . $this->endDate->format('Y-m-d');
        }

        if ($this->startDate) {
            return 'From ' . $this->startDate->format('Y-m-d');
        }

        if ($this->endDate) {
            return 'Until ' . $this->endDate->format('Y-m-d');
        }

        return 'All Time';
    }
}
