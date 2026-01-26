<?php

namespace App\Http\Controllers\Operator;

use App\Domain\Accounting\Models\AccountParty;
use App\Domain\Accounting\Models\AccountingLedger;
use App\Domain\Accounting\Models\AccountBalance;
use App\Domain\Accounting\Models\SettlementBatch;
use App\Domain\Platform\Models\MonetaryUnit;
use App\Domain\Accounting\Services\AccountLedgerService;
use App\Domain\Accounting\Services\AccountingEntryService;
use App\Domain\Accounting\Services\AccountingReportService;
use App\Domain\Accounting\Services\MerchantStatementService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * AccountLedgerController - إدارة سجل الحسابات
 *
 * الصفحة الموحدة لجميع الحسابات:
 * - التجار
 * - المناديب
 * - شركات الشحن
 * - شركات الدفع
 *
 * القاعدة: كل الأرقام من Ledger فقط - لا قراءة مباشرة من الطلبات
 */
class AccountLedgerController extends OperatorBaseController
{
    protected AccountLedgerService $ledgerService;
    protected AccountingEntryService $entryService;
    protected AccountingReportService $reportService;
    protected MerchantStatementService $statementService;

    public function __construct(
        AccountLedgerService $ledgerService,
        AccountingEntryService $entryService,
        AccountingReportService $reportService,
        MerchantStatementService $statementService
    ) {
        parent::__construct();
        $this->ledgerService = $ledgerService;
        $this->entryService = $entryService;
        $this->reportService = $reportService;
        $this->statementService = $statementService;
    }

    // ═══════════════════════════════════════════════════════════════
    // DASHBOARD - لوحة التحكم الموحدة
    // ═══════════════════════════════════════════════════════════════

    /**
     * لوحة التحكم الرئيسية للحسابات
     */
    public function index()
    {
        $dashboard = $this->ledgerService->getPlatformDashboard();
        $currency = monetaryUnit()->getDefault();

        // ملخص سريع لكل نوع طرف
        $summary = [
            'merchants' => $this->getQuickSummary(AccountParty::TYPE_MERCHANT),
            'couriers' => $this->getQuickSummary(AccountParty::TYPE_COURIER),
            'shipping' => $this->getQuickSummary(AccountParty::TYPE_SHIPPING_PROVIDER),
            'payment' => $this->getQuickSummary(AccountParty::TYPE_PAYMENT_PROVIDER),
        ];

        // آخر المعاملات - جلب من الـ Controller وليس من الـ View
        $recentTransactions = AccountingLedger::with(['fromParty', 'toParty'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('operator.accounts.index', [
            'dashboard' => $dashboard,
            'summary' => $summary,
            'currency' => $currency,
            'recentTransactions' => $recentTransactions,
        ]);
    }

    /**
     * ملخص سريع لنوع طرف
     */
    protected function getQuickSummary(string $partyType): array
    {
        $parties = AccountParty::where('party_type', $partyType)
            ->where('is_active', true)
            ->count();

        $receivable = AccountBalance::whereHas('party', function ($q) use ($partyType) {
            $q->where('party_type', $partyType);
        })->where('balance_type', AccountBalance::TYPE_RECEIVABLE)
          ->sum('pending_amount');

        $payable = AccountBalance::whereHas('party', function ($q) use ($partyType) {
            $q->where('party_type', $partyType);
        })->where('balance_type', AccountBalance::TYPE_PAYABLE)
          ->sum('pending_amount');

        return [
            'count' => $parties,
            'receivable' => $receivable,
            'payable' => $payable,
            'net' => $receivable - $payable,
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // PARTIES LIST - قوائم الأطراف
    // ═══════════════════════════════════════════════════════════════

    /**
     * قائمة التجار وحساباتهم
     */
    public function merchants(Request $request)
    {
        return $this->partyList(AccountParty::TYPE_MERCHANT, 'operator.accounts.merchants', $request);
    }

    /**
     * قائمة المناديب وحساباتهم
     */
    public function couriers(Request $request)
    {
        return $this->partyList(AccountParty::TYPE_COURIER, 'operator.accounts.couriers', $request);
    }

    /**
     * قائمة شركات الشحن وحساباتهم
     */
    public function shippingProviders(Request $request)
    {
        return $this->partyList(AccountParty::TYPE_SHIPPING_PROVIDER, 'operator.accounts.shipping', $request);
    }

    /**
     * قائمة شركات الدفع وحساباتهم
     */
    public function paymentProviders(Request $request)
    {
        return $this->partyList(AccountParty::TYPE_PAYMENT_PROVIDER, 'operator.accounts.payment', $request);
    }

    /**
     * Helper لعرض قائمة الأطراف
     */
    protected function partyList(string $partyType, string $view, Request $request)
    {
        $query = AccountParty::where('party_type', $partyType);

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('has_balance')) {
            $query->whereHas('balances', function ($q) {
                $q->where('pending_amount', '>', 0);
            });
        }

        $parties = $query->with(['balances.counterparty'])
            ->withCount('outgoingTransactions as transactions_count')
            ->orderBy('name')
            ->paginate(20);

        // إضافة ملخص لكل طرف
        $parties->getCollection()->transform(function ($party) {
            $party->summary = $this->ledgerService->getPartySummary($party);
            return $party;
        });

        $currency = monetaryUnit()->getDefault();

        return view($view, [
            'parties' => $parties,
            'partyType' => $partyType,
            'currency' => $currency,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // PARTY DETAILS - تفاصيل الطرف
    // ═══════════════════════════════════════════════════════════════

    /**
     * كشف حساب طرف
     */
    public function partyStatement(Request $request, AccountParty $party)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

        // الطرف المقابل (إذا تم اختياره)
        $counterparty = null;
        if ($request->counterparty_id) {
            $counterparty = AccountParty::find($request->counterparty_id);
        }

        $statement = $this->ledgerService->getAccountStatement($party, $counterparty, $startDate, $endDate);
        $summary = $this->ledgerService->getPartySummary($party);

        // الأطراف المقابلة المتاحة
        $counterparties = AccountParty::where('id', '!=', $party->id)
            ->where('is_active', true)
            ->orderBy('party_type')
            ->orderBy('name')
            ->get();

        $currency = monetaryUnit()->getDefault();

        return view('operator.accounts.statement', [
            'party' => $party,
            'statement' => $statement,
            'summary' => $summary,
            'counterparties' => $counterparties,
            'selectedCounterparty' => $counterparty,
            'startDate' => $startDate?->format('Y-m-d') ?? '',
            'endDate' => $endDate?->format('Y-m-d') ?? '',
            'currency' => $currency,
        ]);
    }

    /**
     * تفاصيل معاملة
     */
    public function transactionDetails(AccountingLedger $transaction)
    {
        $transaction->load(['fromParty', 'toParty', 'purchase', 'merchantPurchase', 'settlementBatch']);
        $currency = monetaryUnit()->getDefault();

        return view('operator.accounts.transaction-details', [
            'transaction' => $transaction,
            'currency' => $currency,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // SETTLEMENTS - التسويات
    // ═══════════════════════════════════════════════════════════════

    /**
     * قائمة دفعات التسوية
     */
    public function settlements(Request $request)
    {
        $query = SettlementBatch::with(['fromParty', 'toParty']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->party_id) {
            $query->forParty($request->party_id);
        }

        $settlements = $query->orderBy('created_at', 'desc')->paginate(20);
        $currency = monetaryUnit()->getDefault();

        return view('operator.accounts.settlements', [
            'settlements' => $settlements,
            'currency' => $currency,
        ]);
    }

    /**
     * إنشاء تسوية جديدة - عرض النموذج
     */
    public function createSettlementForm(Request $request)
    {
        $party = null;
        if ($request->party_id) {
            $party = AccountParty::find($request->party_id);
        }

        $parties = AccountParty::where('is_active', true)
            ->where('party_type', '!=', AccountParty::TYPE_PLATFORM)
            ->orderBy('party_type')
            ->orderBy('name')
            ->get()
            ->groupBy('party_type');

        $platform = $this->ledgerService->getPlatformParty();
        $currency = monetaryUnit()->getDefault();

        $pendingBalance = 0;
        if ($party) {
            $summary = $this->ledgerService->getPartySummary($party);
            $pendingBalance = $summary['total_payable'] ?? 0;
        }

        return view('operator.accounts.create-settlement', [
            'party' => $party,
            'parties' => $parties,
            'platform' => $platform,
            'pendingBalance' => $pendingBalance,
            'currency' => $currency,
        ]);
    }

    /**
     * حفظ تسوية جديدة
     */
    public function storeSettlement(Request $request)
    {
        $request->validate([
            'from_party_id' => 'required|exists:account_parties,id',
            'to_party_id' => 'required|exists:account_parties,id|different:from_party_id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $fromParty = AccountParty::findOrFail($request->from_party_id);
        $toParty = AccountParty::findOrFail($request->to_party_id);

        try {
            $settlement = $this->ledgerService->recordSettlement(
                $fromParty,
                $toParty,
                $request->amount,
                $request->payment_method,
                $request->payment_reference,
                auth('operator')->id()
            );

            return redirect()
                ->route('operator.accounts.settlements')
                ->with('success', __('Settlement recorded successfully. Reference: :ref', ['ref' => $settlement->transaction_ref]));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * تفاصيل دفعة تسوية
     */
    public function settlementDetails(SettlementBatch $batch)
    {
        $batch->load(['fromParty', 'toParty', 'ledgerEntries', 'createdByUser', 'approvedByUser']);
        $currency = monetaryUnit()->getDefault();

        return view('operator.accounts.settlement-details', [
            'batch' => $batch,
            'currency' => $currency,
        ]);
    }

    /**
     * تسوية المندوب - استلام COD
     */
    public function courierSettlement(Request $request)
    {
        $request->validate([
            'courier_id' => 'required|exists:delivery_couriers,id',
            'purchase_ids' => 'required|array',
            'purchase_ids.*' => 'exists:merchant_purchases,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
        ]);

        try {
            $batch = $this->entryService->recordCourierSettlement(
                $request->courier_id,
                $request->purchase_ids,
                $request->amount,
                $request->payment_method,
                auth('operator')->id()
            );

            return redirect()
                ->route('operator.accounts.settlements.show', $batch)
                ->with('success', __('Courier settlement recorded. Reference: :ref', ['ref' => $batch->batch_ref]));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * تسوية شركة الشحن - استلام COD
     */
    public function shippingCompanySettlement(Request $request)
    {
        $request->validate([
            'provider_code' => 'required|string',
            'purchase_ids' => 'required|array',
            'purchase_ids.*' => 'exists:merchant_purchases,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_reference' => 'nullable|string',
            'settlement_type' => 'required|in:to_platform,to_merchant',
            'merchant_id' => 'required_if:settlement_type,to_merchant|exists:users,id',
        ]);

        try {
            if ($request->settlement_type === 'to_platform') {
                $batch = $this->entryService->recordShippingCompanySettlement(
                    $request->provider_code,
                    $request->purchase_ids,
                    $request->amount,
                    $request->payment_method,
                    $request->payment_reference,
                    auth('operator')->id()
                );
            } else {
                $batch = $this->entryService->recordShippingCompanySettlementToMerchant(
                    $request->provider_code,
                    $request->merchant_id,
                    $request->purchase_ids,
                    $request->amount,
                    $request->payment_method,
                    $request->payment_reference,
                    auth('operator')->id()
                );
            }

            return redirect()
                ->route('operator.accounts.settlements.show', $batch)
                ->with('success', __('Shipping company settlement recorded. Reference: :ref', ['ref' => $batch->batch_ref]));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * قائمة الطلبات المعلقة للتسوية حسب شركة الشحن
     */
    public function pendingSettlementsByProvider(Request $request, string $providerCode)
    {
        $purchases = \App\Domain\Commerce\Models\MerchantPurchase::where('delivery_provider', $providerCode)
            ->where(function ($q) {
                $q->where('shipping_company_owes_platform', '>', 0)
                    ->orWhere('shipping_company_owes_merchant', '>', 0);
            })
            ->with(['purchase', 'merchant'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $totalOwed = $purchases->sum('shipping_company_owes_platform') + $purchases->sum('shipping_company_owes_merchant');
        $currency = monetaryUnit()->getDefault();

        return view('operator.accounts.pending-settlements', [
            'purchases' => $purchases,
            'providerCode' => $providerCode,
            'totalOwed' => $totalOwed,
            'currency' => $currency,
        ]);
    }

    /**
     * قائمة الطلبات المعلقة للتسوية حسب المندوب
     */
    public function pendingSettlementsByCourier(Request $request, int $courierId)
    {
        $purchases = \App\Domain\Commerce\Models\MerchantPurchase::where('courier_id', $courierId)
            ->where('courier_owes_platform', '>', 0)
            ->with(['purchase', 'merchant'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $totalOwed = $purchases->sum('courier_owes_platform');
        $courier = \App\Domain\Shipping\Models\DeliveryCourier::find($courierId);
        $currency = monetaryUnit()->getDefault();

        return view('operator.accounts.pending-courier-settlements', [
            'purchases' => $purchases,
            'courier' => $courier,
            'totalOwed' => $totalOwed,
            'currency' => $currency,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // SYNC - مزامنة الأطراف
    // ═══════════════════════════════════════════════════════════════

    /**
     * مزامنة جميع الأطراف
     */
    public function syncParties()
    {
        $counts = [
            'merchants' => $this->ledgerService->syncMerchantParties(),
            'couriers' => $this->ledgerService->syncCourierParties(),
            'shipping' => $this->ledgerService->syncShippingProviders(),
            'payment' => $this->ledgerService->syncPaymentProviders(),
        ];

        $total = array_sum($counts);

        return back()->with('success', __('Synced :count parties successfully.', ['count' => $total]));
    }

    // ═══════════════════════════════════════════════════════════════
    // REPORTS - التقارير
    // ═══════════════════════════════════════════════════════════════

    /**
     * تقرير الذمم المدينة
     */
    public function receivablesReport(Request $request)
    {
        $platform = $this->ledgerService->getPlatformParty();

        $receivables = AccountBalance::where('party_id', $platform->id)
            ->where('balance_type', AccountBalance::TYPE_RECEIVABLE)
            ->where('pending_amount', '>', 0)
            ->with('counterparty')
            ->orderBy('pending_amount', 'desc')
            ->get();

        $total = $receivables->sum('pending_amount');
        $currency = monetaryUnit()->getDefault();

        return view('operator.accounts.reports.receivables', [
            'receivables' => $receivables,
            'total' => $total,
            'currency' => $currency,
        ]);
    }

    /**
     * تقرير الذمم الدائنة
     */
    public function payablesReport(Request $request)
    {
        $platform = $this->ledgerService->getPlatformParty();

        $payables = AccountBalance::where('party_id', $platform->id)
            ->where('balance_type', AccountBalance::TYPE_PAYABLE)
            ->where('pending_amount', '>', 0)
            ->with('counterparty')
            ->orderBy('pending_amount', 'desc')
            ->get();

        $total = $payables->sum('pending_amount');
        $currency = monetaryUnit()->getDefault();

        return view('operator.accounts.reports.payables', [
            'payables' => $payables,
            'total' => $total,
            'currency' => $currency,
        ]);
    }

    /**
     * تقرير شركات الشحن
     */
    public function shippingReport(Request $request)
    {
        $shippingParties = AccountParty::where('party_type', AccountParty::TYPE_SHIPPING_PROVIDER)
            ->where('is_active', true)
            ->get();

        $report = [];
        foreach ($shippingParties as $party) {
            $summary = $this->ledgerService->getPartySummary($party);
            $report[] = [
                'party' => $party,
                'summary' => $summary,
            ];
        }

        $currency = monetaryUnit()->getDefault();

        return view('operator.accounts.reports.shipping', [
            'report' => $report,
            'currency' => $currency,
        ]);
    }

    /**
     * تقرير شركات الدفع
     */
    public function paymentReport(Request $request)
    {
        $paymentParties = AccountParty::where('party_type', AccountParty::TYPE_PAYMENT_PROVIDER)
            ->where('is_active', true)
            ->get();

        $report = [];
        foreach ($paymentParties as $party) {
            $summary = $this->ledgerService->getPartySummary($party);
            $report[] = [
                'party' => $party,
                'summary' => $summary,
            ];
        }

        $currency = monetaryUnit()->getDefault();

        return view('operator.accounts.reports.payment', [
            'report' => $report,
            'currency' => $currency,
        ]);
    }

    /**
     * تقرير الضرائب المحصلة (من Ledger فقط)
     */
    public function taxReport(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

        $report = $this->reportService->getTaxReport($startDate, $endDate);
        $currency = monetaryUnit()->getDefault();

        return view('operator.accounts.reports.tax', [
            'report' => $report,
            'taxEntries' => $report['entries'],
            'byLocation' => $report['by_location'],
            'byMonth' => $report['by_month'],
            'totalTax' => $report['totals']['collected'],
            'pendingTax' => $report['totals']['pending'],
            'remittedTax' => $report['totals']['remitted'],
            'startDate' => $startDate?->format('Y-m-d') ?? '',
            'endDate' => $endDate?->format('Y-m-d') ?? '',
            'currency' => $currency,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // ENHANCED REPORTS - التقارير المحسنة (من Ledger فقط)
    // ═══════════════════════════════════════════════════════════════

    /**
     * تقرير المنصة الشامل
     */
    public function platformReport(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

        $report = $this->reportService->getPlatformReport($startDate, $endDate);
        $currency = monetaryUnit()->getDefault();

        return view('operator.accounts.reports.platform', [
            'report' => $report,
            'startDate' => $startDate?->format('Y-m-d') ?? '',
            'endDate' => $endDate?->format('Y-m-d') ?? '',
            'currency' => $currency,
        ]);
    }

    /**
     * ملخص جميع التجار
     */
    public function merchantsSummary(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

        $merchants = $this->reportService->getMerchantsSummaryReport($startDate, $endDate);
        $currency = monetaryUnit()->getDefault();

        // Pre-compute totals for view (DATA_FLOW_POLICY)
        $totalSales = $merchants->sum('total_sales');
        $totalCommission = $merchants->sum('total_commission');
        $totalTax = $merchants->sum('total_tax');
        $totals = [
            'total_sales' => $totalSales,
            'total_commission' => $totalCommission,
            'total_tax' => $totalTax,
            'balance_due' => $merchants->sum('balance_due'),
            'net_amount' => $totalSales - $totalCommission - $totalTax,
            'settlements_received' => $merchants->sum('settlements_received'),
            'transaction_count' => $merchants->sum('transaction_count'),
        ];

        return view('operator.accounts.reports.merchants-summary', [
            'merchants' => $merchants,
            'totals' => $totals,
            'startDate' => $startDate?->format('Y-m-d') ?? '',
            'endDate' => $endDate?->format('Y-m-d') ?? '',
            'currency' => $currency,
        ]);
    }

    /**
     * كشف حساب تاجر (من Ledger فقط)
     */
    public function merchantStatement(Request $request, $merchantId)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

        $statement = $this->statementService->generateStatement($merchantId, $startDate, $endDate);
        $pendingAmounts = $this->statementService->getPendingAmounts($merchantId);
        $currency = monetaryUnit()->getDefault();

        return view('operator.accounts.merchant-statement', [
            'statement' => $statement,
            'pendingAmounts' => $pendingAmounts,
            'startDate' => $startDate?->format('Y-m-d') ?? '',
            'endDate' => $endDate?->format('Y-m-d') ?? '',
            'currency' => $currency,
        ]);
    }

    /**
     * تقرير المناديب
     */
    public function couriersReport(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

        $couriers = $this->reportService->getCouriersReport($startDate, $endDate);
        $currency = monetaryUnit()->getDefault();

        // Pre-compute totals for view (DATA_FLOW_POLICY)
        $totals = [
            'fees_earned' => $couriers->sum('fees_earned'),
            'cod_collected' => $couriers->sum('cod_collected'),
            'cod_pending' => $couriers->sum('cod_pending'),
            'owes_to_platform' => $couriers->sum('owes_to_platform'),
            'settlement_amount' => $couriers->sum('settlement_amount'),
            'settlements_made' => $couriers->sum('settlements_made'),
            'delivery_count' => $couriers->sum('delivery_count'),
        ];

        return view('operator.accounts.reports.couriers', [
            'couriers' => $couriers,
            'totals' => $totals,
            'startDate' => $startDate?->format('Y-m-d') ?? '',
            'endDate' => $endDate?->format('Y-m-d') ?? '',
            'currency' => $currency,
        ]);
    }

    /**
     * تقرير شركات الشحن (محسن)
     */
    public function shippingCompaniesReport(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;

        $companies = $this->reportService->getShippingCompaniesReport($startDate, $endDate);
        $currency = monetaryUnit()->getDefault();

        // Pre-compute totals for view (DATA_FLOW_POLICY)
        $receivable = $companies->sum('receivable_from_platform');
        $payable = $companies->sum('payable_to_platform');
        $totals = [
            'fees_earned' => $companies->sum('fees_earned'),
            'cod_collected' => $companies->sum('cod_collected'),
            'receivable_from_platform' => $receivable,
            'payable_to_platform' => $payable,
            'net_balance' => $receivable - $payable,
            'shipment_count' => $companies->sum('shipment_count'),
        ];

        return view('operator.accounts.reports.shipping-companies', [
            'companies' => $companies,
            'totals' => $totals,
            'startDate' => $startDate?->format('Y-m-d') ?? '',
            'endDate' => $endDate?->format('Y-m-d') ?? '',
            'currency' => $currency,
        ]);
    }

    /**
     * تقرير الذمم الشامل (مدينة ودائنة)
     */
    public function receivablesPayablesReport(Request $request)
    {
        $report = $this->reportService->getReceivablesPayablesReport();
        $currency = monetaryUnit()->getDefault();

        return view('operator.accounts.reports.receivables-payables', [
            'report' => $report,
            'currency' => $currency,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // SHIPPING COMPANY STATEMENT - كشف حساب شركة الشحن المفصل
    // ═══════════════════════════════════════════════════════════════

    /**
     * كشف حساب شركة شحن مفصل (ديناميكي من delivery_provider)
     *
     * يعرض:
     * - جميع الشحنات مع المبالغ
     * - COD المحصل
     * - من له ومن عليه (للمنصة أو للتاجر)
     * - تاريخ التسويات
     */
    public function shippingCompanyStatement(Request $request, string $providerCode)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now();
        $currency = monetaryUnit()->getDefault();

        // === جلب جميع الشحنات لهذه الشركة ===
        $query = \App\Domain\Commerce\Models\MerchantPurchase::where('delivery_provider', $providerCode)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->with(['purchase', 'merchant', 'settlementBatch']);

        $purchases = $query->orderBy('created_at', 'desc')->get();

        // === حساب الملخصات ===
        // إجمالي الشحنات
        $totalShipments = $purchases->count();

        // إجمالي رسوم الشحن
        $totalShippingFees = $purchases->sum('shipping_cost');

        // إجمالي COD المحصل (الطلبات المسلمة)
        $totalCodCollected = $purchases
            ->where('collection_status', 'collected')
            ->sum('cod_amount');

        // المبالغ المستحقة للمنصة من شركة الشحن
        $owesToPlatform = $purchases->sum('shipping_company_owes_platform');

        // المبالغ المستحقة للتاجر من شركة الشحن
        $owesToMerchant = $purchases->sum('shipping_company_owes_merchant');

        // المبالغ المعلقة (لم تُسوى بعد)
        $pendingPurchases = $purchases->where('settlement_status', '!=', 'settled');
        $pendingToPlatform = $pendingPurchases->sum('shipping_company_owes_platform');
        $pendingToMerchant = $pendingPurchases->sum('shipping_company_owes_merchant');

        // المبالغ المسواة
        $settledPurchases = $purchases->where('settlement_status', 'settled');
        $settledToPlatform = $purchases->sum('shipping_company_owes_platform') - $pendingToPlatform;
        $settledToMerchant = $purchases->sum('shipping_company_owes_merchant') - $pendingToMerchant;

        // === بناء كشف الحساب المفصل ===
        $statement = [];
        $runningBalance = 0;

        foreach ($purchases->sortBy('created_at') as $mp) {
            // تحديد نوع المعاملة
            $debit = 0;  // ما يدفعه شركة الشحن
            $credit = 0; // ما تستحقه شركة الشحن

            // رسوم الشحن المستحقة للشركة (credit)
            $credit = (float) $mp->shipping_cost;

            // COD المحصل يجب تسليمه (debit)
            if ($mp->collection_status === 'collected' && $mp->cod_amount > 0) {
                $debit = (float) $mp->cod_amount;
            }

            $balance = $credit - $debit;
            $runningBalance += $balance;

            $statement[] = [
                'date' => $mp->created_at,
                'purchase_number' => $mp->purchase_number,
                'description' => $this->getShipmentDescription($mp),
                'merchant_name' => $mp->merchant->name ?? '-',
                'shipping_fee' => $credit,
                'cod_collected' => $mp->collection_status === 'collected' ? $mp->cod_amount : 0,
                'owes_platform' => (float) $mp->shipping_company_owes_platform,
                'owes_merchant' => (float) $mp->shipping_company_owes_merchant,
                'credit' => $credit,
                'debit' => $debit,
                'balance' => $runningBalance,
                'settlement_status' => $mp->settlement_status,
                'collection_status' => $mp->collection_status,
                'delivery_status' => $mp->purchase->delivery_status ?? 'unknown',
            ];
        }

        // === جلب اسم الشركة ===
        $companyName = $this->getShippingCompanyName($providerCode);

        // === إحصائيات إضافية ===
        $statusBreakdown = [
            'delivered' => $purchases->where('purchase.delivery_status', 'delivered')->count(),
            'in_transit' => $purchases->whereIn('purchase.delivery_status', ['shipped', 'in_transit'])->count(),
            'returned' => $purchases->where('purchase.delivery_status', 'returned')->count(),
            'failed' => $purchases->where('purchase.delivery_status', 'failed')->count(),
        ];

        return view('operator.accounts.shipping-company-statement', [
            'providerCode' => $providerCode,
            'companyName' => $companyName,
            'statement' => $statement,
            'currency' => $currency,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),

            // Summary
            'totalShipments' => $totalShipments,
            'totalShippingFees' => $totalShippingFees,
            'totalCodCollected' => $totalCodCollected,
            'owesToPlatform' => $owesToPlatform,
            'owesToMerchant' => $owesToMerchant,
            'pendingToPlatform' => $pendingToPlatform,
            'pendingToMerchant' => $pendingToMerchant,
            'settledToPlatform' => $settledToPlatform,
            'settledToMerchant' => $settledToMerchant,
            'netBalance' => $totalShippingFees - $totalCodCollected,
            'statusBreakdown' => $statusBreakdown,
        ]);
    }

    /**
     * تصدير كشف حساب شركة الشحن PDF
     */
    public function shippingCompanyStatementPdf(Request $request, string $providerCode)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now();
        $currency = monetaryUnit()->getDefault();

        // نفس منطق shippingCompanyStatement
        $purchases = \App\Domain\Commerce\Models\MerchantPurchase::where('delivery_provider', $providerCode)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->with(['purchase', 'merchant'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalShipments = $purchases->count();
        $totalShippingFees = $purchases->sum('shipping_cost');
        $totalCodCollected = $purchases->where('collection_status', 'collected')->sum('cod_amount');
        $owesToPlatform = $purchases->sum('shipping_company_owes_platform');
        $owesToMerchant = $purchases->sum('shipping_company_owes_merchant');
        $pendingPurchases = $purchases->where('settlement_status', '!=', 'settled');
        $pendingToPlatform = $pendingPurchases->sum('shipping_company_owes_platform');
        $pendingToMerchant = $pendingPurchases->sum('shipping_company_owes_merchant');

        $statement = [];
        $runningBalance = 0;

        foreach ($purchases->sortBy('created_at') as $mp) {
            $credit = (float) $mp->shipping_cost;
            $debit = ($mp->collection_status === 'collected' && $mp->cod_amount > 0) ? (float) $mp->cod_amount : 0;
            $runningBalance += ($credit - $debit);

            $statement[] = [
                'date' => $mp->created_at,
                'purchase_number' => $mp->purchase_number,
                'merchant_name' => $mp->merchant->name ?? '-',
                'shipping_fee' => $credit,
                'cod_collected' => $mp->collection_status === 'collected' ? $mp->cod_amount : 0,
                'owes_platform' => (float) $mp->shipping_company_owes_platform,
                'owes_merchant' => (float) $mp->shipping_company_owes_merchant,
                'balance' => $runningBalance,
                'settlement_status' => $mp->settlement_status,
            ];
        }

        $companyName = $this->getShippingCompanyName($providerCode);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.shipping-company-statement', [
            'providerCode' => $providerCode,
            'companyName' => $companyName,
            'statement' => $statement,
            'currency' => $currency,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'totalShipments' => $totalShipments,
            'totalShippingFees' => $totalShippingFees,
            'totalCodCollected' => $totalCodCollected,
            'owesToPlatform' => $owesToPlatform,
            'owesToMerchant' => $owesToMerchant,
            'pendingToPlatform' => $pendingToPlatform,
            'pendingToMerchant' => $pendingToMerchant,
            'netBalance' => $totalShippingFees - $totalCodCollected,
            'generatedAt' => now()->format('Y-m-d H:i'),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("shipping-statement-{$providerCode}-{$startDate->format('Ymd')}-{$endDate->format('Ymd')}.pdf");
    }

    /**
     * قائمة شركات الشحن الديناميكية (من delivery_provider)
     */
    public function shippingCompanyList(Request $request)
    {
        $currency = monetaryUnit()->getDefault();

        // جلب جميع شركات الشحن الفريدة من الطلبات
        $providers = \App\Domain\Commerce\Models\MerchantPurchase::whereNotNull('delivery_provider')
            ->where('delivery_provider', '!=', '')
            ->select('delivery_provider')
            ->distinct()
            ->pluck('delivery_provider');

        $companies = [];
        foreach ($providers as $providerCode) {
            $purchases = \App\Domain\Commerce\Models\MerchantPurchase::where('delivery_provider', $providerCode)->get();

            $companies[] = [
                'code' => $providerCode,
                'name' => $this->getShippingCompanyName($providerCode),
                'shipment_count' => $purchases->count(),
                'total_shipping_fees' => $purchases->sum('shipping_cost'),
                'total_cod_collected' => $purchases->where('collection_status', 'collected')->sum('cod_amount'),
                'owes_platform' => $purchases->sum('shipping_company_owes_platform'),
                'owes_merchant' => $purchases->sum('shipping_company_owes_merchant'),
                'pending_count' => $purchases->where('settlement_status', '!=', 'settled')->count(),
            ];
        }

        // ترتيب حسب عدد الشحنات
        usort($companies, fn($a, $b) => $b['shipment_count'] <=> $a['shipment_count']);

        return view('operator.accounts.shipping-company-list', [
            'companies' => $companies,
            'currency' => $currency,
        ]);
    }

    /**
     * Helper: وصف الشحنة
     */
    protected function getShipmentDescription(\App\Domain\Commerce\Models\MerchantPurchase $mp): string
    {
        $status = $mp->purchase->delivery_status ?? 'unknown';
        $statusLabels = [
            'pending' => __('Pending'),
            'shipped' => __('Shipped'),
            'in_transit' => __('In Transit'),
            'delivered' => __('Delivered'),
            'returned' => __('Returned'),
            'failed' => __('Failed'),
        ];

        $label = $statusLabels[$status] ?? $status;

        if ($mp->collection_status === 'collected') {
            $label .= ' - ' . __('COD Collected');
        }

        return $label;
    }

    /**
     * Helper: اسم شركة الشحن
     */
    protected function getShippingCompanyName(string $providerCode): string
    {
        // محاولة جلب الاسم من AccountParty
        $party = AccountParty::where('party_type', AccountParty::TYPE_SHIPPING_PROVIDER)
            ->where('code', $providerCode)
            ->first();

        if ($party) {
            return $party->name;
        }

        // أسماء افتراضية للشركات المعروفة
        $knownProviders = [
            'aramex' => 'Aramex',
            'smsa' => 'SMSA Express',
            'dhl' => 'DHL',
            'fedex' => 'FedEx',
            'ups' => 'UPS',
            'naqel' => 'Naqel Express',
            'zajil' => 'Zajil Express',
            'local_courier' => __('Local Courier'),
        ];

        return $knownProviders[strtolower($providerCode)] ?? ucfirst($providerCode);
    }
}
