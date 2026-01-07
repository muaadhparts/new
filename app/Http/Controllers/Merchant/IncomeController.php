<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\MerchantPurchase;
use App\Services\MerchantAccountingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncomeController extends Controller
{
    protected MerchantAccountingService $accountingService;

    public function __construct(MerchantAccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function index(Request $request)
    {
        $merchantId = Auth::user()->id;
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->format('Y-m-d') : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->format('Y-m-d') : null;

        // Build query
        $query = MerchantPurchase::with('purchase')
            ->where('user_id', $merchantId);

        if ($startDate && $endDate) {
            $query->whereDate('created_at', '>=', $startDate)
                  ->whereDate('created_at', '<=', $endDate);
        }

        $datas = $query->get();

        // Get detailed report from accounting service
        $report = $this->accountingService->getMerchantReport($merchantId, $startDate, $endDate);

        // Get currency sign from first purchase or use default
        $currencySign = $datas->isNotEmpty() && $datas[0]->purchase
            ? $datas[0]->purchase->currency_sign
            : 'SAR ';

        return view('merchant.earning', [
            'datas' => $datas,
            'total' => $currencySign . number_format($datas->sum('price'), 2),
            'start_date' => $startDate ?? '',
            'end_date' => $endDate ?? '',
            // New detailed report data
            'report' => $report,
            'currencySign' => $currencySign,
            'total_sales' => $currencySign . number_format($report['total_sales'], 2),
            'total_commission' => $currencySign . number_format($report['total_commission'], 2),
            'total_tax' => $currencySign . number_format($report['total_tax'], 2),
            'total_shipping' => $currencySign . number_format($report['total_shipping'], 2),
            'total_packing' => $currencySign . number_format($report['total_packing'], 2),
            'total_courier_fees' => $currencySign . number_format($report['total_courier_fees'], 2),
            'total_net' => $currencySign . number_format($report['total_net'], 2),
            'count_orders' => $report['count_orders'],
            'merchant_payments' => $currencySign . number_format($report['merchant_payments'], 2),
            'platform_payments' => $currencySign . number_format($report['platform_payments'], 2),
            'courier_deliveries' => $report['courier_deliveries'],
            'shipping_deliveries' => $report['shipping_deliveries'],
        ]);
    }
}
