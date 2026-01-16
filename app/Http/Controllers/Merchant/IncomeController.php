<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\MonetaryUnit;
use App\Services\MerchantAccountingService;
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
            'total_platform_packing_fee' => $currencySign . number_format($report['total_platform_packing_fee'], 2),

            // Shipping Costs
            'total_shipping_cost' => $currencySign . number_format($report['total_shipping_cost'], 2),
            'total_packing_cost' => $currencySign . number_format($report['total_packing_cost'], 2),
            'total_courier_fee' => $currencySign . number_format($report['total_courier_fee'], 2),

            // Net Amount
            'total_net' => $currencySign . number_format($report['total_net'], 2),

            // Settlement Balances
            'platform_owes_merchant' => $report['platform_owes_merchant'],
            'merchant_owes_platform' => $report['merchant_owes_platform'],
            'net_balance' => $report['net_balance'],

            // Payment Method Breakdown
            'platform_payments' => $report['platform_payments'],
            'merchant_payments' => $report['merchant_payments'],

            // Shipping Breakdown
            'platform_shipping' => $report['platform_shipping'],
            'merchant_shipping' => $report['merchant_shipping'],
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

        return view('merchant.statement', [
            'currency' => $currency,
            'currencySign' => $currencySign,
            'start_date' => $startDate ?? '',
            'end_date' => $endDate ?? '',
            'statement' => $statement['statement'],
            'opening_balance' => $statement['opening_balance'],
            'closing_balance' => $statement['closing_balance'],
            'total_credit' => $currencySign . number_format($statement['total_credit'], 2),
            'total_debit' => $currencySign . number_format($statement['total_debit'], 2),
        ]);
    }
}
