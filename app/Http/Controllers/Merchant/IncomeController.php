<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Domain\Platform\Models\MonetaryUnit;
use App\Domain\Accounting\Models\SettlementBatch;
use App\Domain\Accounting\Services\MerchantAccountingService;
use App\Domain\Merchant\Services\MerchantDisplayService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Merchant IncomeController - Merchant Financial Dashboard
 *
 * ARCHITECTURAL PRINCIPLE (2026-01-09):
 * - Single source of truth: MerchantPurchase
 * - owner_id = 0 → Platform service
 * - owner_id > 0 → Merchant/Owner service
 * - Shows merchant's complete financial picture
 *
 * DATA FLOW POLICY:
 * - Controller = Orchestration only
 * - All formatting in MerchantDisplayService (API-ready)
 */
class IncomeController extends Controller
{
    public function __construct(
        protected MerchantAccountingService $accountingService,
        protected MerchantDisplayService $displayService
    ) {
    }

    /**
     * Merchant Earnings Dashboard
     */
    public function index(Request $request)
    {
        $merchantId = Auth::user()->id;
        $currency = monetaryUnit()->getDefault();
        $currencySign = $currency->sign ?? 'SAR ';

        $startDate = $request->start_date ? Carbon::parse($request->start_date)->format('Y-m-d') : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->format('Y-m-d') : null;

        // Get comprehensive report from accounting service
        $report = $this->accountingService->getMerchantReport($merchantId, $startDate, $endDate);

        // Get statement for ledger view
        $statement = $this->accountingService->getMerchantStatement($merchantId, $startDate, $endDate);

        // Format using DisplayService (API-ready)
        $earningsDisplay = $this->displayService->formatEarningsSummary($report, $currencySign);
        $purchasesDisplay = $this->displayService->formatPurchasesForEarnings($report['purchases'], $currencySign);

        return view('merchant.earning', array_merge(
            [
                'currency' => $currency,
                'currencySign' => $currencySign,
                'start_date' => $startDate ?? '',
                'end_date' => $endDate ?? '',
                'purchases' => $purchasesDisplay,
                'statement' => $statement,
                'report' => $report,
            ],
            $earningsDisplay
        ));
    }

    /**
     * Merchant Tax Report
     * Tax is informational only - NOT part of settlement calculations
     */
    public function taxReport(Request $request)
    {
        $merchantId = Auth::user()->id;
        $currency = monetaryUnit()->getDefault();
        $currencySign = $currency->sign ?? 'SAR ';

        $startDate = $request->start_date ? Carbon::parse($request->start_date)->format('Y-m-d') : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->format('Y-m-d') : null;

        // Get tax report from service
        $report = $this->accountingService->getMerchantTaxReport($merchantId, $startDate, $endDate);

        // Format using DisplayService (API-ready)
        $taxDisplay = $this->displayService->formatTaxReport($report, $report['purchases'], $currencySign);

        return view('merchant.tax_report', array_merge(
            [
                'currency' => $currency,
                'currencySign' => $currencySign,
                'start_date' => $startDate ?? '',
                'end_date' => $endDate ?? '',
            ],
            $taxDisplay
        ));
    }

    /**
     * Merchant Account Statement
     */
    public function statement(Request $request)
    {
        $merchantId = Auth::user()->id;
        $currency = monetaryUnit()->getDefault();
        $currencySign = $currency->sign ?? 'SAR ';

        $startDate = $request->start_date ? Carbon::parse($request->start_date)->format('Y-m-d') : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->format('Y-m-d') : null;

        // Get statement from accounting service
        $statement = $this->accountingService->getMerchantStatement($merchantId, $startDate, $endDate);

        // Format using DisplayService (API-ready)
        $statementEntries = $this->displayService->formatStatementEntries($statement['statement']);
        $statementTotals = $this->displayService->formatStatementTotals($statement, $currencySign);

        return view('merchant.statement', array_merge(
            [
                'currency' => $currency,
                'currencySign' => $currencySign,
                'start_date' => $startDate ?? '',
                'end_date' => $endDate ?? '',
                'statement' => $statementEntries,
            ],
            $statementTotals
        ));
    }

    /**
     * Export Statement as PDF
     */
    public function statementPdf(Request $request)
    {
        $merchant = Auth::user();
        $merchantId = $merchant->id;
        $currency = monetaryUnit()->getDefault();
        $currencySign = $currency->sign ?? 'SAR ';

        $startDate = $request->start_date ? Carbon::parse($request->start_date)->format('Y-m-d') : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->format('Y-m-d') : null;

        // Get statement from accounting service
        $statement = $this->accountingService->getMerchantStatement($merchantId, $startDate, $endDate);

        // Prepare period label
        $period = __('All Time');
        if ($startDate && $endDate) {
            $period = Carbon::parse($startDate)->format('d/m/Y') . ' - ' . Carbon::parse($endDate)->format('d/m/Y');
        } elseif ($startDate) {
            $period = __('From') . ' ' . Carbon::parse($startDate)->format('d/m/Y');
        } elseif ($endDate) {
            $period = __('Until') . ' ' . Carbon::parse($endDate)->format('d/m/Y');
        }

        $data = [
            'merchant_id' => $merchantId,
            'merchant_name' => $merchant->shop_name ?: $merchant->name,
            'currency_sign' => $currencySign,
            'period' => $period,
            'start_date' => $startDate ? Carbon::parse($startDate)->format('d/m/Y') : null,
            'end_date' => $endDate ? Carbon::parse($endDate)->format('d/m/Y') : null,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
            'statement' => $statement['statement'],
            'opening_balance' => $statement['opening_balance'],
            'closing_balance' => $statement['closing_balance'],
            'total_credit' => $statement['total_credit'],
            'total_debit' => $statement['total_debit'],
        ];

        $pdf = Pdf::loadView('pdf.merchant-statement', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'statement_' . $merchantId . '_' . Carbon::now()->format('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Monthly Ledger Report
     */
    public function monthlyLedger(Request $request)
    {
        $merchantId = Auth::user()->id;
        $currency = monetaryUnit()->getDefault();
        $currencySign = $currency->sign ?? 'SAR ';

        // Default to current month if not specified
        $month = $request->month ? Carbon::parse($request->month . '-01') : Carbon::now()->startOfMonth();
        $startDate = $month->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $month->copy()->endOfMonth()->format('Y-m-d');

        // Get statement for the month
        $statement = $this->accountingService->getMerchantStatement($merchantId, $startDate, $endDate);

        // Get report for summary
        $report = $this->accountingService->getMerchantReport($merchantId, $startDate, $endDate);

        // Get previous months for navigation
        $months = collect();
        for ($i = 0; $i < 12; $i++) {
            $m = Carbon::now()->subMonths($i)->startOfMonth();
            $months->push([
                'value' => $m->format('Y-m'),
                'label' => $m->translatedFormat('F Y'),
            ]);
        }

        // Format using DisplayService (API-ready)
        $statementEntries = $this->displayService->formatStatementEntries($statement['statement']);
        $statementTotals = $this->displayService->formatStatementTotals($statement, $currencySign);
        $ledgerSummary = $this->displayService->formatMonthlyLedgerSummary($report, $currencySign);

        return view('merchant.monthly_ledger', array_merge(
            [
                'currency' => $currency,
                'currencySign' => $currencySign,
                'current_month' => $month->format('Y-m'),
                'month_label' => $month->translatedFormat('F Y'),
                'months' => $months,
                'statement' => $statementEntries,
            ],
            $statementTotals,
            $ledgerSummary
        ));
    }

    /**
     * Export Monthly Ledger as PDF
     */
    public function monthlyLedgerPdf(Request $request)
    {
        $merchant = Auth::user();
        $merchantId = $merchant->id;
        $currency = monetaryUnit()->getDefault();
        $currencySign = $currency->sign ?? 'SAR ';

        $month = $request->month ? Carbon::parse($request->month . '-01') : Carbon::now()->startOfMonth();
        $startDate = $month->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $month->copy()->endOfMonth()->format('Y-m-d');

        $statement = $this->accountingService->getMerchantStatement($merchantId, $startDate, $endDate);

        $data = [
            'merchant_id' => $merchantId,
            'merchant_name' => $merchant->shop_name ?: $merchant->name,
            'currency_sign' => $currencySign,
            'period' => $month->translatedFormat('F Y'),
            'start_date' => Carbon::parse($startDate)->format('d/m/Y'),
            'end_date' => Carbon::parse($endDate)->format('d/m/Y'),
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
            'statement' => $statement['statement'],
            'opening_balance' => $statement['opening_balance'],
            'closing_balance' => $statement['closing_balance'],
            'total_credit' => $statement['total_credit'],
            'total_debit' => $statement['total_debit'],
        ];

        $pdf = Pdf::loadView('pdf.merchant-statement', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'ledger_' . $merchantId . '_' . $month->format('Y_m') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Payouts / Settlements received by merchant
     */
    public function payouts(Request $request)
    {
        $merchant = Auth::user();
        $merchantId = $merchant->id;
        $currency = monetaryUnit()->getDefault();
        $currencySign = $currency->sign ?? 'SAR ';

        // Get merchant's party for settlements
        $merchantParty = \App\Domain\Accounting\Models\AccountParty::where('party_type', 'merchant')
            ->where('party_id', $merchantId)
            ->first();

        $payouts = collect();
        $payoutsFormatted = collect();
        $pendingAmount = 0;
        $totalReceived = 0;

        if ($merchantParty) {
            // Get completed settlements where merchant is recipient
            $payouts = SettlementBatch::where('to_party_id', $merchantParty->id)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            $totalReceived = SettlementBatch::where('to_party_id', $merchantParty->id)
                ->where('status', SettlementBatch::STATUS_COMPLETED)
                ->sum('total_amount');

            // Get pending amount from platform_owes_merchant
            $pendingAmount = \App\Domain\Commerce\Models\MerchantPurchase::where('user_id', $merchantId)
                ->where('settlement_status', '!=', 'settled')
                ->sum('platform_owes_merchant');

            // Format using DisplayService (API-ready)
            $payoutsFormatted = $this->displayService->formatPayouts($payouts->getCollection(), $currencySign);
        }

        // Format summary using DisplayService (API-ready)
        $payoutsSummary = $this->displayService->formatPayoutsSummary($pendingAmount, $totalReceived, $currencySign);

        return view('merchant.payouts', array_merge(
            [
                'currency' => $currency,
                'currencySign' => $currencySign,
                'payouts' => $payouts,
                'payoutsFormatted' => $payoutsFormatted,
            ],
            $payoutsSummary
        ));
    }
}
