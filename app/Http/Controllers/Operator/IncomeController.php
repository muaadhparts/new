<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\MerchantPurchase;
use App\Models\Purchase;
use App\Models\UserMembershipPlan;
use App\Models\Withdraw;
use App\Services\MerchantAccountingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    protected MerchantAccountingService $accountingService;

    public function __construct(MerchantAccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }


    public function taxCalculate(Request $request)
    {

        $current_date = Carbon::now();
        $explode = explode('-',$current_date->format('d-m-Y'));
        $explode[0] = '1';
        $implode= implode("-",$explode);
        $first_day = Carbon::parse($implode);
        $last30days = date('Y-m-d', strtotime('today - 30 days'));
        
        
        $last_30_days =  $purchases = Purchase::whereDate('created_at','>=',$last30days)->whereDate('created_at','<=',$current_date)->select('id','purchase_number','txnId','created_at','tax','tax_location','currency_sign','currency_value')->where('tax','!=',0);
        $current_month =  $purchases = Purchase::whereDate('created_at','>=',$first_day)->whereDate('created_at','<=',$current_date)->select('id','purchase_number','txnId','created_at','tax','tax_location','currency_sign','currency_value')->where('tax','!=',0);


         $sign = Currency::where('is_default','=',1)->first();
         if($request->start_date && $request->end_date){
            $start_date = Carbon::parse($request->start_date);
            $end_date = Carbon::parse($request->end_date);
            $purchases = Purchase::with(['merchantPurchases.user'])->whereDate('created_at','>=',$start_date)->whereDate('created_at','<=',$end_date)->where('tax','!=',0);
        }else{
            $purchases = Purchase::with(['merchantPurchases.user'])->where('tax','!=',0);
        }

        return view('operator.earning.tax_calculate',[
            'purchases' => $purchases->count() > 0 ? $purchases->get() : [],
            'total' => $purchases->count() > 0 ? $sign->sign . $purchases->sum('tax') : 0,
            'start_date' => isset($start_date) ? $start_date : '',
            'end_date' => isset($end_date) ? $end_date : '',
            'currency' => $sign,
            'current_month' => $current_month->count() > 0 ? $sign->sign . $current_month->sum('tax') : 0,
           'last_30_days' => $last_30_days->count() > 0 ? $sign->sign . $last_30_days->sum('tax') : 0,
        ]);


    }


    public function membershipPlanIncome(Request $request)
    {

        $current_date = Carbon::now();
        $explode = explode('-',$current_date->format('d-m-Y'));
        $explode[0] = '1';
        $implode= implode("-",$explode);
        $first_day = Carbon::parse($implode);
        $last30days = date('Y-m-d', strtotime('today - 30 days'));


        $last_30_days =  $membershipPlans = UserMembershipPlan::whereDate('created_at','>=',$last30days)->whereDate('created_at','<=',$current_date)->where('price',"!=",0);
        $current_month =  $membershipPlans = UserMembershipPlan::whereDate('created_at','>=',$first_day)->whereDate('created_at','<=',$current_date)->where('price',"!=",0);


        $sign = Currency::where('is_default','=',1)->first();
        if($request->start_date && $request->end_date){
           $start_date = Carbon::parse($request->start_date);
           $end_date = Carbon::parse($request->end_date);
           $membershipPlans = UserMembershipPlan::with('user')->whereDate('created_at','>=',$start_date)->whereDate('created_at','<=',$end_date)->where('price',"!=",0);
       }else{
           $membershipPlans = UserMembershipPlan::with('user')->where('price',"!=",0);
       }

       return view('operator.earning.membership_plan_income',[
           'membershipPlans' => $membershipPlans->count() > 0 ? $membershipPlans->get() : [],
           'total' => $membershipPlans->count() > 0 ? $sign->sign . $membershipPlans->sum('price') : 0,
           'start_date' => isset($start_date) ? $start_date : '',
           'end_date' => isset($end_date) ? $end_date : '',
           'currency' => $sign,
           'current_month' => $current_month->count() > 0 ? $sign->sign . $current_month->sum('price') : 0,
           'last_30_days' => $last_30_days->count() > 0 ? $sign->sign . $last_30_days->sum('price') : 0,
       ]);


    }


    public function withdrawIncome(Request $request)
    {
   
        $current_date = Carbon::now();
        $explode = explode('-',$current_date->format('d-m-Y'));
        $explode[0] = '1';
        $implode= implode("-",$explode);
        $first_day = Carbon::parse($implode);
        $last30days = date('Y-m-d', strtotime('today - 30 days'));
        
        
        $last_30_days =  $withdraws = Withdraw::whereDate('created_at','>=',$last30days)->whereDate('created_at','<=',$current_date)->where('status','completed');
        $current_month =  $withdraws = Withdraw::whereDate('created_at','>=',$first_day)->whereDate('created_at','<=',$current_date)->where('status','completed');

        $sign = Currency::where('is_default','=',1)->first();
        if($request->start_date && $request->end_date){
           $start_date = Carbon::parse($request->start_date);
           $end_date = Carbon::parse($request->end_date);
           $withdraws = Withdraw::with('user')->whereDate('created_at','>=',$start_date)->whereDate('created_at','<=',$end_date)->where('status','completed');
       }else{
           $withdraws = Withdraw::with('user')->where('status','completed');
       }

       return view('operator.earning.withdraw_income',[
           'withdraws' => $withdraws->count() > 0 ? $withdraws->get() : [],
           'total' => $withdraws->count() > 0 ? $sign->sign . $withdraws->sum('fee') : 0,
           'start_date' => isset($start_date) ? $start_date : '',
           'end_date' => isset($end_date) ? $end_date : '',
           'currency' => $sign,
           'current_month' => $current_month->count() > 0 ? $sign->sign . $current_month->sum('fee') : 0,
           'last_30_days' => $last_30_days->count() > 0 ? $sign->sign . $last_30_days->sum('fee') : 0,
       ]);

    }


    public function commissionIncome(Request $request)
    {
        $current_date = Carbon::now();
        $explode = explode('-', $current_date->format('d-m-Y'));
        $explode[0] = '1';
        $implode = implode("-", $explode);
        $first_day = Carbon::parse($implode);
        $last30days = date('Y-m-d', strtotime('today - 30 days'));

        // Use MerchantPurchase for commission data
        $last_30_days_sum = MerchantPurchase::whereDate('created_at', '>=', $last30days)
            ->whereDate('created_at', '<=', $current_date)
            ->where('commission_amount', '>', 0)
            ->sum('commission_amount');

        $current_month_sum = MerchantPurchase::whereDate('created_at', '>=', $first_day)
            ->whereDate('created_at', '<=', $current_date)
            ->where('commission_amount', '>', 0)
            ->sum('commission_amount');

        $sign = Currency::where('is_default', '=', 1)->first();

        // Build query for purchases with commission
        $query = Purchase::with(['merchantPurchases.user'])
            ->whereHas('merchantPurchases', function ($q) {
                $q->where('commission_amount', '>', 0);
            });

        if ($request->start_date && $request->end_date) {
            $start_date = Carbon::parse($request->start_date);
            $end_date = Carbon::parse($request->end_date);
            $query->whereDate('created_at', '>=', $start_date)
                  ->whereDate('created_at', '<=', $end_date);
        }

        $purchases = $query->get();

        // Calculate total commission from merchant_purchases
        $total_commission = $purchases->flatMap(function ($purchase) {
            return $purchase->merchantPurchases;
        })->sum('commission_amount');

        return view('operator.earning.commission_earning', [
            'purchases' => $purchases,
            'total' => $sign->sign . number_format($total_commission, 2),
            'start_date' => isset($start_date) ? $start_date : '',
            'end_date' => isset($end_date) ? $end_date : '',
            'currency' => $sign,
            'current_month' => $sign->sign . number_format($current_month_sum, 2),
            'last_30_days' => $sign->sign . number_format($last_30_days_sum, 2),
        ]);
    }

    /**
     * Comprehensive Merchant Report
     * Shows detailed breakdown of all merchant transactions
     */
    public function merchantReport(Request $request)
    {
        $sign = Currency::where('is_default', '=', 1)->first();
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->format('Y-m-d') : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->format('Y-m-d') : null;

        // Get comprehensive report from accounting service
        $report = $this->accountingService->getAdminMerchantReport($startDate, $endDate);

        return view('operator.earning.merchant_report', [
            'report' => $report,
            'currency' => $sign,
            'start_date' => $startDate ?? '',
            'end_date' => $endDate ?? '',
            'total_sales' => $sign->sign . number_format($report['total_sales'], 2),
            'total_commissions' => $sign->sign . number_format($report['total_commissions'], 2),
            'total_taxes' => $sign->sign . number_format($report['total_taxes'], 2),
            'total_net_to_merchants' => $sign->sign . number_format($report['total_net_to_merchants'], 2),
            'merchants' => $report['merchants'],
        ]);
    }

    /**
     * Commission Income Report - Enhanced version using MerchantPurchase
     */
    public function commissionIncomeDetailed(Request $request)
    {
        $sign = Currency::where('is_default', '=', 1)->first();
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->format('Y-m-d') : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->format('Y-m-d') : null;

        // Query MerchantPurchase for detailed commission data
        $query = MerchantPurchase::with(['purchase', 'user'])
            ->where('commission_amount', '>', 0);

        if ($startDate && $endDate) {
            $query->whereDate('created_at', '>=', $startDate)
                  ->whereDate('created_at', '<=', $endDate);
        }

        $purchases = $query->get();

        // Calculate totals
        $totalCommission = $purchases->sum('commission_amount');
        $totalSales = $purchases->sum('price');

        // Group by merchant for summary
        $merchantSummary = $purchases->groupBy('user_id')->map(function ($items, $userId) {
            return [
                'merchant_id' => $userId,
                'merchant_name' => $items->first()->user->shop_name ?? 'Unknown',
                'total_sales' => $items->sum('price'),
                'total_commission' => $items->sum('commission_amount'),
                'orders_count' => $items->count(),
            ];
        })->values();

        return view('operator.earning.commission_detailed', [
            'purchases' => $purchases,
            'merchantSummary' => $merchantSummary,
            'currency' => $sign,
            'start_date' => $startDate ?? '',
            'end_date' => $endDate ?? '',
            'total_commission' => $sign->sign . number_format($totalCommission, 2),
            'total_sales' => $sign->sign . number_format($totalSales, 2),
        ]);
    }
}
