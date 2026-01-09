<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\MerchantPurchase;
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
        $currency = Currency::where('is_default', 1)->first();
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
     */
    public function taxReport(Request $request)
    {
        $merchantId = Auth::user()->id;
        $currency = Currency::where('is_default', 1)->first();
        $currencySign = $currency->sign ?? 'SAR ';

        $startDate = $request->start_date ? Carbon::parse($request->start_date)->format('Y-m-d') : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->format('Y-m-d') : null;

        // Query tax transactions
        $query = MerchantPurchase::where('user_id', $merchantId)
            ->where('tax_amount', '>', 0)
            ->with(['purchase']);

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $purchases = $query->orderBy('created_at', 'desc')->get();

        // Calculate totals
        $totalTax = $purchases->sum('tax_amount');
        $totalSales = $purchases->sum('price');

        // Group by payment owner
        $taxFromPlatformPayments = $purchases->where('payment_owner_id', 0)->sum('tax_amount');
        $taxFromMerchantPayments = $purchases->filter(fn($p) => $p->payment_owner_id > 0)->sum('tax_amount');

        return view('merchant.tax_report', [
            'currency' => $currency,
            'currencySign' => $currencySign,
            'start_date' => $startDate ?? '',
            'end_date' => $endDate ?? '',
            'purchases' => $purchases,
            'total_tax' => $currencySign . number_format($totalTax, 2),
            'total_sales' => $currencySign . number_format($totalSales, 2),
            'tax_from_platform_payments' => $currencySign . number_format($taxFromPlatformPayments, 2),
            'tax_from_merchant_payments' => $currencySign . number_format($taxFromMerchantPayments, 2),
        ]);
    }

    /**
     * Merchant Account Statement
     */
    public function statement(Request $request)
    {
        $merchantId = Auth::user()->id;
        $currency = Currency::where('is_default', 1)->first();
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
