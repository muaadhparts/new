<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\MonetaryUnit;
use App\Models\MerchantPurchase;
use App\Models\Withdraw;
use App\Services\MerchantAccountingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * IncomeController - Admin Financial Reports
 *
 * ARCHITECTURAL PRINCIPLE (2026-01-09):
 * - Single source of truth: MerchantPurchase
 * - owner_id = 0 → Platform service
 * - owner_id > 0 → Merchant/Owner service
 * - Tax collected regardless of payment receiver
 */
class IncomeController extends Controller
{
    protected MerchantAccountingService $accountingService;

    public function __construct(MerchantAccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Tax Report - Based on MerchantPurchase.tax_amount
     * Tax is collected regardless of who received the payment
     */
    public function taxCalculate(Request $request)
    {
        $currency = monetaryUnit()->getDefault();
        $currentDate = Carbon::now();
        $firstDayOfMonth = Carbon::now()->startOfMonth();
        $last30Days = Carbon::now()->subDays(30);

        // Parse date filters
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->format('Y-m-d') : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->format('Y-m-d') : null;

        // Get tax report from service
        $report = $this->accountingService->getAdminTaxReport($startDate, $endDate);

        // Last 30 days summary
        $last30DaysReport = $this->accountingService->getAdminTaxReport(
            $last30Days->format('Y-m-d'),
            $currentDate->format('Y-m-d')
        );

        // Current month summary
        $currentMonthReport = $this->accountingService->getAdminTaxReport(
            $firstDayOfMonth->format('Y-m-d'),
            $currentDate->format('Y-m-d')
        );

        return view('operator.earning.tax_calculate', [
            'report' => $report,
            'purchases' => $report['purchases'],
            'total' => $currency->sign . number_format($report['total_tax_collected'], 2),
            'start_date' => $startDate ? Carbon::parse($startDate) : '',
            'end_date' => $endDate ? Carbon::parse($endDate) : '',
            'currency' => $currency,
            'current_month' => $currency->sign . number_format($currentMonthReport['total_tax_collected'], 2),
            'last_30_days' => $currency->sign . number_format($last30DaysReport['total_tax_collected'], 2),

            // New architecture data
            'by_merchant' => $report['by_merchant'],
            'tax_from_platform_payments' => $currency->sign . number_format($report['tax_from_platform_payments'], 2),
            'tax_from_merchant_payments' => $currency->sign . number_format($report['tax_from_merchant_payments'], 2),
        ]);
    }

    /**
     * Withdraw Fee Income Report
     */
    public function withdrawIncome(Request $request)
    {
        $currency = monetaryUnit()->getDefault();
        $currentDate = Carbon::now();
        $firstDayOfMonth = Carbon::now()->startOfMonth();
        $last30Days = Carbon::now()->subDays(30);

        $last30DaysSum = Withdraw::whereDate('created_at', '>=', $last30Days)
            ->whereDate('created_at', '<=', $currentDate)
            ->where('status', 'completed')
            ->sum('fee');

        $currentMonthSum = Withdraw::whereDate('created_at', '>=', $firstDayOfMonth)
            ->whereDate('created_at', '<=', $currentDate)
            ->where('status', 'completed')
            ->sum('fee');

        // Build filtered query
        $query = Withdraw::with('user')->where('status', 'completed');

        if ($request->start_date && $request->end_date) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $query->whereDate('created_at', '>=', $startDate)
                  ->whereDate('created_at', '<=', $endDate);
        }

        $withdraws = $query->get();

        return view('operator.earning.withdraw_income', [
            'withdraws' => $withdraws,
            'total' => $currency->sign . number_format($withdraws->sum('fee'), 2),
            'start_date' => isset($startDate) ? $startDate : '',
            'end_date' => isset($endDate) ? $endDate : '',
            'currency' => $currency,
            'current_month' => $currency->sign . number_format($currentMonthSum, 2),
            'last_30_days' => $currency->sign . number_format($last30DaysSum, 2),
        ]);
    }

    /**
     * Commission Income Report - Based on MerchantPurchase.commission_amount
     */
    public function commissionIncome(Request $request)
    {
        $currency = monetaryUnit()->getDefault();
        $currentDate = Carbon::now();
        $firstDayOfMonth = Carbon::now()->startOfMonth();
        $last30Days = Carbon::now()->subDays(30);

        // Parse date filters
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->format('Y-m-d') : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->format('Y-m-d') : null;

        // Get commission report from service
        $report = $this->accountingService->getAdminCommissionReport($startDate, $endDate);

        // Last 30 days
        $last30DaysReport = $this->accountingService->getAdminCommissionReport(
            $last30Days->format('Y-m-d'),
            $currentDate->format('Y-m-d')
        );

        // Current month
        $currentMonthReport = $this->accountingService->getAdminCommissionReport(
            $firstDayOfMonth->format('Y-m-d'),
            $currentDate->format('Y-m-d')
        );

        return view('operator.earning.commission_earning', [
            'report' => $report,
            'purchases' => $report['purchases'],
            'total' => $currency->sign . number_format($report['total_commission'], 2),
            'start_date' => $startDate ? Carbon::parse($startDate) : '',
            'end_date' => $endDate ? Carbon::parse($endDate) : '',
            'currency' => $currency,
            'current_month' => $currency->sign . number_format($currentMonthReport['total_commission'], 2),
            'last_30_days' => $currency->sign . number_format($last30DaysReport['total_commission'], 2),

            // New architecture data
            'by_merchant' => $report['by_merchant'],
            'total_sales' => $currency->sign . number_format($report['total_sales'], 2),
            'avg_commission_rate' => $report['avg_commission_rate'],
        ]);
    }

    /**
     * Comprehensive Merchant Report - Full financial breakdown
     */
    public function merchantReport(Request $request)
    {
        $currency = monetaryUnit()->getDefault();
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->format('Y-m-d') : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->format('Y-m-d') : null;

        // Get comprehensive report from accounting service
        $report = $this->accountingService->getAdminMerchantReport($startDate, $endDate);

        return view('operator.earning.merchant_report', [
            'report' => $report,
            'currency' => $currency,
            'start_date' => $startDate ?? '',
            'end_date' => $endDate ?? '',
            'total_sales' => $currency->sign . number_format($report['total_sales'], 2),
            'total_commissions' => $currency->sign . number_format($report['total_commissions'], 2),
            'total_taxes' => $currency->sign . number_format($report['total_taxes'], 2),
            'total_net_to_merchants' => $currency->sign . number_format($report['total_net_to_merchants'], 2),
            'merchants' => $report['merchants'],

            // Payment breakdown
            'platform_payments' => $report['platform_payments'],
            'merchant_payments' => $report['merchant_payments'],

            // Settlement summary
            'platform_owes_merchants' => $currency->sign . number_format($report['platform_owes_merchants'], 2),
            'merchants_owe_platform' => $currency->sign . number_format($report['merchants_owe_platform'], 2),
            'net_platform_position' => $report['net_platform_position'],
        ]);
    }

    /**
     * Commission Income Detailed Report - Per merchant breakdown
     */
    public function commissionIncomeDetailed(Request $request)
    {
        $currency = monetaryUnit()->getDefault();
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->format('Y-m-d') : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->format('Y-m-d') : null;

        // Get commission report from service
        $report = $this->accountingService->getAdminCommissionReport($startDate, $endDate);

        return view('operator.earning.commission_detailed', [
            'purchases' => $report['purchases'],
            'merchantSummary' => $report['by_merchant'],
            'currency' => $currency,
            'start_date' => $startDate ?? '',
            'end_date' => $endDate ?? '',
            'total_commission' => $currency->sign . number_format($report['total_commission'], 2),
            'total_sales' => $currency->sign . number_format($report['total_sales'], 2),
            'avg_commission_rate' => $report['avg_commission_rate'],
        ]);
    }
}
