<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Domain\Platform\Models\MonetaryUnit;
use App\Domain\Commerce\Models\MerchantPurchase;
use App\Domain\Accounting\Services\MerchantAccountingService;
use App\Domain\Accounting\Services\WithdrawCalculationService;
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
    public function __construct(
        protected MerchantAccountingService $accountingService,
        protected WithdrawCalculationService $withdrawService
    ) {}

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

        // Parse date filters
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->format('Y-m-d') : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->format('Y-m-d') : null;

        // Get report data from service
        $report = $this->withdrawService->getWithdrawIncomeReport($startDate, $endDate);

        return view('operator.earning.withdraw_income', [
            'withdraws' => $report['withdraws'],
            'total' => $currency->sign . number_format($report['total_fee'], 2),
            'start_date' => $startDate ? Carbon::parse($startDate) : '',
            'end_date' => $endDate ? Carbon::parse($endDate) : '',
            'currency' => $currency,
            'current_month' => $currency->sign . number_format($report['current_month_fee'], 2),
            'last_30_days' => $currency->sign . number_format($report['last_30_days_fee'], 2),
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
