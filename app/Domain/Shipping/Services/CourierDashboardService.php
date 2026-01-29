<?php

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\Models\DeliveryCourier;
use App\Domain\Accounting\Services\CourierAccountingService;
use Illuminate\Support\Facades\DB;

/**
 * CourierDashboardService
 *
 * Handles all dashboard-related business logic for couriers.
 * Provides clean, optimized data for dashboard display.
 *
 * RESPONSIBILITIES:
 * - Calculate delivery statistics
 * - Fetch recent deliveries
 * - Get accounting reports
 * - Calculate tab counts for orders page
 *
 * DATA FLOW:
 * Controller → DashboardService → Query/Model → Database
 */
class CourierDashboardService
{
    public function __construct(
        private CourierAccountingService $accountingService,
        private CourierDisplayService $displayService,
    ) {}

    /**
     * Get all dashboard data for a courier
     *
     * @param int $courierId
     * @return array
     */
    public function getDashboardData(int $courierId): array
    {
        $deliveries = DeliveryCourier::where('courier_id', $courierId)
            ->whereNotNull('purchase_id')
            ->whereHas('purchase')
            ->with(['purchase', 'merchantBranch'])
            ->orderby('id', 'desc')
            ->take(8)
            ->get();

        $report = $this->accountingService->getCourierReport($courierId);

        return [
            'recentDeliveries' => $this->displayService->formatDeliveriesForDashboard($deliveries),
            'report' => $this->displayService->formatReportForDashboard($report),
            'statistics' => $this->getStatistics($courierId),
        ];
    }

    /**
     * Get delivery statistics in a single optimized query
     *
     * @param int $courierId
     * @return array
     */
    private function getStatistics(int $courierId): array
    {
        // Single optimized query for all delivery statistics
        $stats = DB::table('delivery_couriers')
            ->where('courier_id', $courierId)
            ->selectRaw('
                COUNT(*) as total,
                COUNT(CASE WHEN status = ? THEN 1 END) as pending,
                COUNT(CASE WHEN status IN (?, ?, ?) THEN 1 END) as in_progress,
                COUNT(CASE WHEN status IN (?, ?) THEN 1 END) as completed,
                SUM(CASE WHEN status IN (?, ?) THEN courier_fee ELSE 0 END) as total_earnings
            ', [
                DeliveryCourier::STATUS_PENDING_APPROVAL,
                DeliveryCourier::STATUS_APPROVED,
                DeliveryCourier::STATUS_READY_FOR_PICKUP,
                DeliveryCourier::STATUS_PICKED_UP,
                DeliveryCourier::STATUS_DELIVERED,
                DeliveryCourier::STATUS_CONFIRMED,
                DeliveryCourier::STATUS_DELIVERED,
                DeliveryCourier::STATUS_CONFIRMED,
            ])
            ->first();

        return [
            'total' => (int) $stats->total,
            'pending' => (int) $stats->pending,
            'inProgress' => (int) $stats->in_progress,
            'completed' => (int) $stats->completed,
            'totalEarnings' => (float) $stats->total_earnings,
        ];
    }

    /**
     * Get tab counts for orders page
     *
     * @param int $courierId
     * @return array
     */
    public function getOrdersTabCounts(int $courierId): array
    {
        // Single optimized query for all tab counts
        $counts = DB::table('delivery_couriers')
            ->where('courier_id', $courierId)
            ->selectRaw('
                COUNT(CASE WHEN status IN (?, ?, ?, ?) THEN 1 END) as active,
                COUNT(CASE WHEN status = ? THEN 1 END) as pending,
                COUNT(CASE WHEN status IN (?, ?, ?) THEN 1 END) as in_progress
            ', [
                DeliveryCourier::STATUS_PENDING_APPROVAL,
                DeliveryCourier::STATUS_APPROVED,
                DeliveryCourier::STATUS_READY_FOR_PICKUP,
                DeliveryCourier::STATUS_PICKED_UP,
                DeliveryCourier::STATUS_PENDING_APPROVAL,
                DeliveryCourier::STATUS_APPROVED,
                DeliveryCourier::STATUS_READY_FOR_PICKUP,
                DeliveryCourier::STATUS_PICKED_UP,
            ])
            ->first();

        return [
            'active' => (int) $counts->active,
            'pending' => (int) $counts->pending,
            'in_progress' => (int) $counts->in_progress,
        ];
    }

    /**
     * Get orders by type with pagination
     *
     * @param int $courierId
     * @param string|null $type
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getOrders(int $courierId, ?string $type = null, int $perPage = 10)
    {
        $query = DeliveryCourier::where('courier_id', $courierId)
            ->whereNotNull('purchase_id')
            ->whereHas('purchase')
            ->with(['purchase.merchantPurchases', 'merchantBranch', 'merchant'])
            ->orderby('id', 'desc');

        switch ($type) {
            case 'completed':
                $query->whereIn('status', [
                    DeliveryCourier::STATUS_DELIVERED,
                    DeliveryCourier::STATUS_CONFIRMED
                ]);
                break;

            case 'pending':
                $query->where('status', DeliveryCourier::STATUS_PENDING_APPROVAL);
                break;

            case 'in_progress':
                $query->whereIn('status', [
                    DeliveryCourier::STATUS_APPROVED,
                    DeliveryCourier::STATUS_READY_FOR_PICKUP,
                    DeliveryCourier::STATUS_PICKED_UP,
                ]);
                break;

            default:
                // All active orders (pending + in progress)
                $query->whereIn('status', [
                    DeliveryCourier::STATUS_PENDING_APPROVAL,
                    DeliveryCourier::STATUS_APPROVED,
                    DeliveryCourier::STATUS_READY_FOR_PICKUP,
                    DeliveryCourier::STATUS_PICKED_UP,
                ]);
                break;
        }

        return $query->paginate($perPage);
    }

    /**
     * Get transactions data
     *
     * @param int $courierId
     * @param string|null $status
     * @param int $perPage
     * @return array
     */
    public function getTransactionsData(int $courierId, ?string $status = null, int $perPage = 20): array
    {
        $query = DeliveryCourier::where('courier_id', $courierId)
            ->with('purchase')
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        $deliveries = $query->paginate($perPage);
        $report = $this->accountingService->getCourierReport($courierId);

        return [
            'deliveries' => $this->displayService->formatDeliveriesForTransactions($deliveries),
            'report' => $this->displayService->formatReportForTransactions($report),
        ];
    }

    /**
     * Get settlements data
     *
     * @param int $courierId
     * @return array
     */
    public function getSettlementsData(int $courierId): array
    {
        $settlementCalc = $this->accountingService->calculateSettlementAmount($courierId);
        $unsettledDeliveries = $this->accountingService->getUnsettledDeliveriesForCourier($courierId);
        $report = $this->accountingService->getCourierReport($courierId);

        return [
            'settlementCalc' => $this->displayService->formatSettlementCalc($settlementCalc),
            'unsettledDeliveries' => $this->displayService->formatUnsettledDeliveries(collect($unsettledDeliveries)),
            'report' => $this->displayService->formatReportForSettlements($report),
        ];
    }

    /**
     * Get financial report data
     *
     * @param int $courierId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getFinancialReportData(int $courierId, ?string $startDate = null, ?string $endDate = null): array
    {
        $report = $this->accountingService->getCourierReport($courierId, $startDate, $endDate);

        return [
            'report' => $this->displayService->formatFinancialReport($report),
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }
}
