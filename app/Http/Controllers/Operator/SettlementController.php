<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\CourierSettlement;
use App\Models\Currency;
use App\Models\MerchantSettlement;
use App\Models\User;
use App\Services\SettlementService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * SettlementController
 *
 * Manages all settlement operations from the operator dashboard.
 * Uses SettlementService as the single source of truth.
 */
class SettlementController extends Controller
{
    protected SettlementService $settlementService;

    public function __construct(SettlementService $settlementService)
    {
        $this->settlementService = $settlementService;
    }

    // =========================================================================
    // DASHBOARD
    // =========================================================================

    /**
     * Settlement dashboard - overview of all pending settlements
     */
    public function index(Request $request)
    {
        $fromDate = $request->from_date ? Carbon::parse($request->from_date) : null;
        $toDate = $request->to_date ? Carbon::parse($request->to_date) : null;

        $platformSummary = $this->settlementService->getPlatformSummary($fromDate, $toDate);
        $merchantsWithBalances = $this->settlementService->getMerchantsWithUnsettledBalances();
        $couriersWithBalances = $this->settlementService->getCouriersWithUnsettledBalances();

        $currency = Currency::where('is_default', 1)->first();

        return view('operator.settlement.index', [
            'platformSummary' => $platformSummary,
            'merchantsWithBalances' => $merchantsWithBalances,
            'couriersWithBalances' => $couriersWithBalances,
            'currency' => $currency,
            'fromDate' => $fromDate?->format('Y-m-d') ?? '',
            'toDate' => $toDate?->format('Y-m-d') ?? '',
        ]);
    }

    // =========================================================================
    // MERCHANT SETTLEMENTS
    // =========================================================================

    /**
     * List all merchant settlements
     */
    public function merchantSettlements(Request $request)
    {
        $query = MerchantSettlement::with('merchant')
            ->orderBy('created_at', 'desc');

        if ($request->status) {
            $query->byStatus($request->status);
        }

        if ($request->merchant_id) {
            $query->byMerchant($request->merchant_id);
        }

        $settlements = $query->paginate(20);
        $merchants = User::where('is_merchant', 2)->get();
        $currency = Currency::where('is_default', 1)->first();

        return view('operator.settlement.merchant-list', [
            'settlements' => $settlements,
            'merchants' => $merchants,
            'currency' => $currency,
        ]);
    }

    /**
     * Preview merchant settlement before creation
     */
    public function merchantSettlementPreview(Request $request)
    {
        $request->validate([
            'merchant_id' => 'required|exists:users,id',
        ]);

        $fromDate = $request->from_date ? Carbon::parse($request->from_date) : null;
        $toDate = $request->to_date ? Carbon::parse($request->to_date) : null;

        $summary = $this->settlementService->getMerchantSettlementSummary(
            $request->merchant_id,
            $fromDate,
            $toDate
        );

        $currency = Currency::where('is_default', 1)->first();

        return view('operator.settlement.merchant-preview', [
            'summary' => $summary,
            'currency' => $currency,
            'fromDate' => $fromDate?->format('Y-m-d') ?? '',
            'toDate' => $toDate?->format('Y-m-d') ?? '',
        ]);
    }

    /**
     * Create merchant settlement
     */
    public function createMerchantSettlement(Request $request)
    {
        $request->validate([
            'merchant_id' => 'required|exists:users,id',
        ]);

        $fromDate = $request->from_date ? Carbon::parse($request->from_date) : null;
        $toDate = $request->to_date ? Carbon::parse($request->to_date) : null;

        try {
            $settlement = $this->settlementService->createMerchantSettlement(
                $request->merchant_id,
                $fromDate,
                $toDate,
                auth('operator')->id(),
                $request->notes
            );

            return redirect()
                ->route('operator.settlement.merchant.show', $settlement->id)
                ->with('success', __('Settlement created successfully.'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show merchant settlement details
     */
    public function showMerchantSettlement(MerchantSettlement $settlement)
    {
        $settlement->load(['merchant', 'items.merchantPurchase.purchase']);
        $currency = Currency::where('is_default', 1)->first();

        return view('operator.settlement.merchant-show', [
            'settlement' => $settlement,
            'currency' => $currency,
        ]);
    }

    /**
     * Submit merchant settlement for approval
     */
    public function submitMerchantSettlement(MerchantSettlement $settlement)
    {
        if ($settlement->submit()) {
            return back()->with('success', __('Settlement submitted for approval.'));
        }

        return back()->with('error', __('Cannot submit this settlement.'));
    }

    /**
     * Approve merchant settlement
     */
    public function approveMerchantSettlement(MerchantSettlement $settlement)
    {
        if ($settlement->approve(auth('operator')->id())) {
            return back()->with('success', __('Settlement approved.'));
        }

        return back()->with('error', __('Cannot approve this settlement.'));
    }

    /**
     * Mark merchant settlement as paid
     */
    public function payMerchantSettlement(Request $request, MerchantSettlement $settlement)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'payment_reference' => 'nullable|string',
        ]);

        if ($settlement->markAsPaid(
            $request->payment_method,
            $request->payment_reference,
            auth('operator')->id()
        )) {
            return back()->with('success', __('Settlement marked as paid.'));
        }

        return back()->with('error', __('Cannot mark this settlement as paid.'));
    }

    /**
     * Cancel merchant settlement
     */
    public function cancelMerchantSettlement(Request $request, MerchantSettlement $settlement)
    {
        if ($settlement->cancel(auth('operator')->id(), $request->reason)) {
            return redirect()
                ->route('operator.settlement.merchants')
                ->with('success', __('Settlement cancelled.'));
        }

        return back()->with('error', __('Cannot cancel this settlement.'));
    }

    // =========================================================================
    // COURIER SETTLEMENTS
    // =========================================================================

    /**
     * List all courier settlements
     */
    public function courierSettlements(Request $request)
    {
        $query = CourierSettlement::with('courier')
            ->orderBy('created_at', 'desc');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->courier_id) {
            $query->byCourier($request->courier_id);
        }

        $settlements = $query->paginate(20);
        $couriers = Courier::all();
        $currency = Currency::where('is_default', 1)->first();

        return view('operator.settlement.courier-list', [
            'settlements' => $settlements,
            'couriers' => $couriers,
            'currency' => $currency,
        ]);
    }

    /**
     * Preview courier settlement before creation
     */
    public function courierSettlementPreview(Request $request)
    {
        $request->validate([
            'courier_id' => 'required|exists:couriers,id',
        ]);

        $summary = $this->settlementService->getCourierSettlementSummary($request->courier_id);
        $currency = Currency::where('is_default', 1)->first();

        return view('operator.settlement.courier-preview', [
            'summary' => $summary,
            'currency' => $currency,
        ]);
    }

    /**
     * Create courier settlement
     */
    public function createCourierSettlement(Request $request)
    {
        $request->validate([
            'courier_id' => 'required|exists:couriers,id',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:pay_to_courier,receive_from_courier',
            'payment_method' => 'nullable|string',
        ]);

        try {
            $settlement = $this->settlementService->createCourierSettlement(
                $request->courier_id,
                $request->amount,
                $request->type,
                $request->payment_method,
                $request->reference,
                $request->notes,
                auth('operator')->id()
            );

            return redirect()
                ->route('operator.settlement.courier.show', $settlement->id)
                ->with('success', __('Settlement created successfully.'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show courier settlement details
     */
    public function showCourierSettlement(CourierSettlement $settlement)
    {
        $settlement->load('courier', 'transactions');
        $currency = Currency::where('is_default', 1)->first();

        return view('operator.settlement.courier-show', [
            'settlement' => $settlement,
            'currency' => $currency,
        ]);
    }

    /**
     * Process (execute) courier settlement
     */
    public function processCourierSettlement(CourierSettlement $settlement)
    {
        if ($this->settlementService->processCourierSettlement($settlement, auth('operator')->id())) {
            return back()->with('success', __('Settlement processed successfully.'));
        }

        return back()->with('error', __('Cannot process this settlement.'));
    }

    /**
     * Cancel courier settlement
     */
    public function cancelCourierSettlement(CourierSettlement $settlement)
    {
        if ($settlement->cancel()) {
            return redirect()
                ->route('operator.settlement.couriers')
                ->with('success', __('Settlement cancelled.'));
        }

        return back()->with('error', __('Cannot cancel this settlement.'));
    }

    // =========================================================================
    // REPORTS
    // =========================================================================

    /**
     * Platform revenue report
     */
    public function revenueReport(Request $request)
    {
        $fromDate = $request->from_date ? Carbon::parse($request->from_date) : Carbon::now()->startOfMonth();
        $toDate = $request->to_date ? Carbon::parse($request->to_date) : Carbon::now();

        $summary = $this->settlementService->getPlatformSummary($fromDate, $toDate);
        $history = $this->settlementService->getSettlementHistory($fromDate, $toDate);
        $currency = Currency::where('is_default', 1)->first();

        return view('operator.settlement.revenue-report', [
            'summary' => $summary,
            'history' => $history,
            'currency' => $currency,
            'fromDate' => $fromDate->format('Y-m-d'),
            'toDate' => $toDate->format('Y-m-d'),
        ]);
    }
}
