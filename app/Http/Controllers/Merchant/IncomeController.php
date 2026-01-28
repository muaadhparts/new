<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Domain\Platform\Models\MonetaryUnit;
use App\Domain\Accounting\Models\SettlementBatch;
use App\Domain\Accounting\Services\MerchantAccountingService;
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
 */
class IncomeController extends Controller
{
    protected MerchantAccountingService $accountingService;

    public function __construct(MerchantAccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
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

        // PRE-COMPUTED: Add formatted values to purchases (DATA_FLOW_POLICY)
        $report['purchases']->transform(function ($purchase) use ($currencySign) {
            $purchase->date_formatted = $purchase->created_at?->format('d-m-Y') ?? 'N/A';
            $purchase->price_formatted = $currencySign . number_format($purchase->price, 2);
            $purchase->commission_amount_formatted = $currencySign . number_format($purchase->commission_amount, 2);
            $purchase->tax_amount_formatted = $currencySign . number_format($purchase->tax_amount, 2);
            $purchase->net_amount_formatted = $currencySign . number_format($purchase->net_amount, 2);
            $purchase->platform_owes_merchant_formatted = $currencySign . number_format($purchase->platform_owes_merchant, 2);
            $purchase->merchant_owes_platform_formatted = $currencySign . number_format($purchase->merchant_owes_platform, 2);
            return $purchase;
        });

        // PRE-COMPUTE: Formatted values for settlement cards (DATA_FLOW_POLICY)
        $netBalanceFormatted = $currencySign . number_format(abs($report['net_balance']), 2);
        $platformOwesMerchantFormatted = $currencySign . number_format($report['platform_owes_merchant'], 2);
        $merchantOwesPlatformFormatted = $currencySign . number_format($report['merchant_owes_platform'], 2);
        $platformPaymentsTotalFormatted = $currencySign . number_format($report['platform_payments']['total'], 2);
        $merchantPaymentsTotalFormatted = $currencySign . number_format($report['merchant_payments']['total'], 2);
        $platformShippingCostFormatted = $currencySign . number_format($report['platform_shipping']['cost'], 2);
        $merchantShippingCostFormatted = $currencySign . number_format($report['merchant_shipping']['cost'], 2);

        return view('merchant.earning', [
            'currency' => $currency,
            'currencySign' => $currencySign,
            'start_date' => $startDate ?? '',
            'end_date' => $endDate ?? '',

            // Sales Summary
            'total_sales' => $currencySign . number_format($report['total_sales'], 2),
            'total_orders' => $report['total_orders'],
            'total_qty' => $report['total_qty'],

            // Platform Deductions
            'total_commission' => $currencySign . number_format($report['total_commission'], 2),
            'total_tax' => $currencySign . number_format($report['total_tax'], 2),
            'total_platform_shipping_fee' => $currencySign . number_format($report['total_platform_shipping_fee'], 2),

            // Shipping Costs
            'total_shipping_cost' => $currencySign . number_format($report['total_shipping_cost'], 2),
            'total_courier_fee' => $currencySign . number_format($report['total_courier_fee'], 2),

            // Net Amount
            'total_net' => $currencySign . number_format($report['total_net'], 2),

            // Settlement Balances
            'platform_owes_merchant' => $report['platform_owes_merchant'],
            'merchant_owes_platform' => $report['merchant_owes_platform'],
            'net_balance' => $report['net_balance'],
            'net_balance_formatted' => $netBalanceFormatted,
            'platform_owes_merchant_formatted' => $platformOwesMerchantFormatted,
            'merchant_owes_platform_formatted' => $merchantOwesPlatformFormatted,

            // Payment Method Breakdown
            'platform_payments' => $report['platform_payments'],
            'merchant_payments' => $report['merchant_payments'],
            'platform_payments_total_formatted' => $platformPaymentsTotalFormatted,
            'merchant_payments_total_formatted' => $merchantPaymentsTotalFormatted,

            // Shipping Breakdown
            'platform_shipping' => $report['platform_shipping'],
            'merchant_shipping' => $report['merchant_shipping'],
            'platform_shipping_cost_formatted' => $platformShippingCostFormatted,
            'merchant_shipping_cost_formatted' => $merchantShippingCostFormatted,
            'courier_deliveries' => $report['courier_deliveries'],

            // Raw purchases for table
            'purchases' => $report['purchases'],

            // Statement for ledger
            'statement' => $statement,

            // Report object for additional details
            'report' => $report,
        ]);
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

        // Get tax report from service (NO direct calculations in controller)
        $report = $this->accountingService->getMerchantTaxReport($merchantId, $startDate, $endDate);

        // PRE-COMPUTED: Add date_formatted to purchases (DATA_FLOW_POLICY)
        $report['purchases']->transform(function ($purchase) {
            $purchase->date_formatted = $purchase->created_at?->format('d-m-Y') ?? 'N/A';
            return $purchase;
        });

        return view('merchant.tax_report', [
            'currency' => $currency,
            'currencySign' => $currencySign,
            'start_date' => $startDate ?? '',
            'end_date' => $endDate ?? '',
            'purchases' => $report['purchases'],
            'total_tax' => $currencySign . number_format($report['total_tax'], 2),
            'total_sales' => $currencySign . number_format($report['total_sales'], 2),
            'tax_from_platform_payments' => $currencySign . number_format($report['tax_from_platform_payments'], 2),
            'tax_from_merchant_payments' => $currencySign . number_format($report['tax_from_merchant_payments'], 2),
        ]);
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

        // PRE-COMPUTED: Add date_formatted to statement entries (DATA_FLOW_POLICY)
        $statementEntries = collect($statement['statement'])->map(function ($entry) {
            $entry['date_formatted'] = isset($entry['date']) ? $entry['date']->format('d-m-Y') : 'N/A';
            return $entry;
        })->toArray();

        return view('merchant.statement', [
            'currency' => $currency,
            'currencySign' => $currencySign,
            'start_date' => $startDate ?? '',
            'end_date' => $endDate ?? '',
            'statement' => $statementEntries,
            'opening_balance' => $statement['opening_balance'],
            'closing_balance' => $statement['closing_balance'],
            'total_credit' => $currencySign . number_format($statement['total_credit'], 2),
            'total_debit' => $currencySign . number_format($statement['total_debit'], 2),
        ]);
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

        // PRE-COMPUTED: Add date_formatted to statement entries (DATA_FLOW_POLICY)
        $statementEntries = collect($statement['statement'])->map(function ($entry) {
            $entry['date_formatted'] = isset($entry['date']) ? $entry['date']->format('d-m-Y') : 'N/A';
            return $entry;
        })->toArray();

        return view('merchant.monthly_ledger', [
            'currency' => $currency,
            'currencySign' => $currencySign,
            'current_month' => $month->format('Y-m'),
            'month_label' => $month->translatedFormat('F Y'),
            'months' => $months,

            // Statement data
            'statement' => $statementEntries,
            'opening_balance' => $statement['opening_balance'],
            'closing_balance' => $statement['closing_balance'],
            'total_credit' => $currencySign . number_format($statement['total_credit'], 2),
            'total_debit' => $currencySign . number_format($statement['total_debit'], 2),

            // Summary
            'total_sales' => $currencySign . number_format($report['total_sales'], 2),
            'total_commission' => $currencySign . number_format($report['total_commission'], 2),
            'total_tax' => $currencySign . number_format($report['total_tax'], 2),
            'total_net' => $currencySign . number_format($report['total_net'], 2),
            'total_orders' => $report['total_orders'],
        ]);
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

            // PRE-COMPUTED: Add formatted display values to payouts (DATA_FLOW_POLICY)
            $payouts->getCollection()->transform(function ($payout) {
                $payout->date_formatted = $payout->settlement_date
                    ? $payout->settlement_date->format('d-m-Y')
                    : ($payout->created_at ? $payout->created_at->format('d-m-Y') : 'N/A');
                $payout->amount_formatted = $payout->getFormattedAmount();
                $payout->status_color = $payout->getStatusColor();
                $payout->status_name_ar = $payout->getStatusNameAr();
                return $payout;
            });
        }

        return view('merchant.payouts', [
            'currency' => $currency,
            'currencySign' => $currencySign,
            'payouts' => $payouts,
            'pending_amount' => $currencySign . number_format($pendingAmount, 2),
            'total_received' => $currencySign . number_format($totalReceived, 2),
            'pending_raw' => $pendingAmount,
            'total_received_raw' => $totalReceived,
        ]);
    }
}
