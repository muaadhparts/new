<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\CourierSettlement;
use App\Models\CourierTransaction;
use App\Models\Currency;
use App\Models\DeliveryCourier;
use App\Services\CourierAccountingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
     * View a specific courier's details and transactions
     */
    public function show($id)
    {
        $courier = Courier::findOrFail($id);
        $currency = Currency::where('is_default', 1)->first();
        $report = $this->accountingService->getCourierReport($id);
        $settlementCalc = $this->accountingService->calculateSettlementAmount($id);

        $transactions = CourierTransaction::where('courier_id', $id)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        $settlements = CourierSettlement::where('courier_id', $id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('operator.courier.details', [
            'courier' => $courier,
            'report' => $report,
            'currency' => $currency,
            'settlementCalc' => $settlementCalc,
            'transactions' => $transactions,
            'settlements' => $settlements,
        ]);
    }

    /**
     * Show settlements management page
     */
    public function settlements(Request $request)
    {
        $currency = Currency::where('is_default', 1)->first();

        $query = CourierSettlement::with('courier')
            ->orderBy('created_at', 'desc');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->courier_id) {
            $query->where('courier_id', $request->courier_id);
        }

        $settlements = $query->paginate(20);
        $couriers = Courier::where('status', 1)->get();

        return view('operator.courier.settlements', [
            'settlements' => $settlements,
            'currency' => $currency,
            'couriers' => $couriers,
        ]);
    }

    /**
     * Show form to create a new settlement
     */
    public function createSettlement($courierId)
    {
        $courier = Courier::findOrFail($courierId);
        $currency = Currency::where('is_default', 1)->first();
        $settlementCalc = $this->accountingService->calculateSettlementAmount($courierId);
        $unsettled = $this->accountingService->getUnsettledDeliveriesForCourier($courierId);

        return view('operator.courier.create_settlement', [
            'courier' => $courier,
            'currency' => $currency,
            'settlementCalc' => $settlementCalc,
            'unsettled' => $unsettled,
        ]);
    }

    /**
     * Store a new settlement
     */
    public function storeSettlement(Request $request, $courierId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:pay_to_courier,receive_from_courier',
            'payment_method' => 'nullable|string|max:100',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $courier = Courier::findOrFail($courierId);

        $settlement = $this->accountingService->createSettlement(
            $courierId,
            $request->amount,
            $request->type,
            $request->payment_method,
            $request->reference_number,
            $request->notes
        );

        return redirect()
            ->route('operator-courier-show', $courierId)
            ->with('success', __('Settlement created successfully. Pending approval.'));
    }

    /**
     * Process (approve) a settlement
     */
    public function processSettlement($settlementId)
    {
        $settlement = CourierSettlement::findOrFail($settlementId);

        if ($settlement->status !== 'pending') {
            return back()->with('unsuccess', __('This settlement has already been processed.'));
        }

        $processedBy = Auth::id() ?? 0;
        $success = $this->accountingService->processSettlement($settlementId, $processedBy);

        if ($success) {
            // Mark related deliveries as settled
            DeliveryCourier::where('courier_id', $settlement->courier_id)
                ->where('status', 'delivered')
                ->where('settlement_status', 'pending')
                ->update([
                    'settlement_status' => 'settled',
                    'settled_at' => now(),
                ]);

            return back()->with('success', __('Settlement processed successfully.'));
        }

        return back()->with('unsuccess', __('Failed to process settlement.'));
    }

    /**
     * Cancel a pending settlement
     */
    public function cancelSettlement($settlementId)
    {
        $settlement = CourierSettlement::findOrFail($settlementId);

        if ($settlement->status !== 'pending') {
            return back()->with('unsuccess', __('Only pending settlements can be cancelled.'));
        }

        $settlement->update(['status' => 'cancelled']);

        return back()->with('success', __('Settlement cancelled successfully.'));
    }

    /**
     * View all unsettled deliveries for a courier
     */
    public function unsettledDeliveries($courierId)
    {
        $courier = Courier::findOrFail($courierId);
        $currency = Currency::where('is_default', 1)->first();
        $unsettled = $this->accountingService->getUnsettledDeliveriesForCourier($courierId);

        return view('operator.courier.unsettled_deliveries', [
            'courier' => $courier,
            'currency' => $currency,
            'unsettled' => $unsettled,
        ]);
    }
}
