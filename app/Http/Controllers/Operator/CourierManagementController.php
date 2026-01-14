<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Currency;
use App\Models\DeliveryCourier;
use App\Services\CourierAccountingService;
use Illuminate\Http\Request;

class CourierManagementController extends Controller
{
    protected CourierAccountingService $accountingService;

    public function __construct(CourierAccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Display all couriers with their balances
     */
    public function index(Request $request)
    {
        $currency = Currency::where('is_default', 1)->first();
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $report = $this->accountingService->getAdminCouriersReport($startDate, $endDate);

        return view('operator.courier.balances', [
            'report' => $report,
            'currency' => $currency,
            'start_date' => $startDate ?? '',
            'end_date' => $endDate ?? '',
        ]);
    }

    /**
     * View a specific courier's details
     */
    public function show($id)
    {
        $courier = Courier::findOrFail($id);
        $currency = Currency::where('is_default', 1)->first();
        $report = $this->accountingService->getCourierReport($id);
        $settlementCalc = $this->accountingService->calculateSettlementAmount($id);

        // Get recent deliveries
        $recentDeliveries = DeliveryCourier::where('courier_id', $id)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return view('operator.courier.details', [
            'courier' => $courier,
            'report' => $report,
            'currency' => $currency,
            'settlementCalc' => $settlementCalc,
            'recentDeliveries' => $recentDeliveries,
        ]);
    }

    /**
     * View all unsettled deliveries for a courier
     */
    public function unsettledDeliveries($courierId)
    {
        $courier = Courier::findOrFail($courierId);
        $currency = Currency::where('is_default', 1)->first();
        $unsettled = $this->accountingService->getUnsettledDeliveriesForCourier($courierId);

        // حساب الملخص في الـ Controller بدلاً من الـ View
        $codTotal = $unsettled->where('payment_method', 'cod')->sum('purchase_amount');
        $feesTotal = $unsettled->sum('delivery_fee');
        $net = $feesTotal - $codTotal;

        $summary = [
            'cod_total' => $codTotal,
            'fees_total' => $feesTotal,
            'net' => $net,
        ];

        return view('operator.courier.unsettled_deliveries', [
            'courier' => $courier,
            'currency' => $currency,
            'unsettled' => $unsettled,
            'summary' => $summary,
        ]);
    }
}
