<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Domain\Shipping\Models\Courier;
use App\Domain\Platform\Models\MonetaryUnit;
use App\Domain\Shipping\Models\DeliveryCourier;
use App\Domain\Accounting\Services\CourierAccountingService;
use App\Domain\Accounting\Models\SettlementBatch;
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
        $currency = monetaryUnit()->getDefault();
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
        $currency = monetaryUnit()->getDefault();
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
        $currency = monetaryUnit()->getDefault();
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

    /**
     * Display all settlements
     */
    public function settlements(Request $request)
    {
        $currency = monetaryUnit()->getDefault();
        
        // Get settlement batches for couriers
        // TODO: Filter by party_type='courier' when AccountParty is properly implemented
        $settlements = SettlementBatch::with(['toParty', 'fromParty'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('operator.courier.settlements', [
            'settlements' => $settlements,
            'currency' => $currency,
        ]);
    }

    /**
     * Create a new settlement for a courier
     */
    public function createSettlement(Request $request, $courierId)
    {
        $courier = Courier::findOrFail($courierId);
        
        // Get unsettled deliveries
        $unsettled = $this->accountingService->getUnsettledDeliveriesForCourier($courierId);
        
        if ($unsettled->isEmpty()) {
            return redirect()->back()->with('error', 'لا توجد توصيلات غير مسوّاة لهذا المندوب');
        }

        // Calculate settlement amounts
        $settlementCalc = $this->accountingService->calculateSettlementAmount($courierId);
        
        // TODO: Create proper settlement batch using AccountParty
        // For now, create a simple settlement record
        $settlement = SettlementBatch::create([
            'from_party_id' => 1, // Platform party (TODO: get from config)
            'to_party_id' => $courierId, // TODO: Map courier to AccountParty
            'total_amount' => $settlementCalc['net_amount'],
            'currency' => monetaryUnit()->getDefault()->name,
            'status' => SettlementBatch::STATUS_PENDING,
            'notes' => $request->input('notes', 'Courier settlement for ' . $courier->name),
        ]);

        // Mark deliveries as settled
        // TODO: Update DeliveryCourier table to add settlement_batch_id column
        $unsettled->each(function ($delivery) use ($settlement) {
            $delivery->update([
                'is_settled' => true,
                'settled_at' => now(),
            ]);
        });

        return redirect()
            ->route('operator-courier-balances')
            ->with('success', 'تم إنشاء التسوية بنجاح');
    }
}
