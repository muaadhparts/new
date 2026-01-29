<?php

namespace App\Domain\Merchant\Services;

use App\Domain\Commerce\Models\MerchantPurchase;
use App\Domain\Merchant\Models\TrustBadge;
use App\Domain\Merchant\Queries\MerchantItemQuery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * MerchantDashboardService
 *
 * Handles all dashboard-related business logic for merchants.
 * Provides clean, optimized data for dashboard display.
 *
 * RESPONSIBILITIES:
 * - Calculate statistics (purchases, sales, items)
 * - Fetch recent items and purchases
 * - Generate sales chart data
 * - Check verification status
 *
 * DATA FLOW:
 * Controller → DashboardService → Query/Model → Database
 */
class MerchantDashboardService
{
    public function __construct(
        private MerchantItemQuery $itemQuery,
        private MerchantItemDisplayService $displayService,
    ) {}

    /**
     * Get all dashboard data for a merchant
     *
     * @param int $merchantId
     * @return array
     */
    public function getDashboardData(int $merchantId): array
    {
        return [
            'statistics' => $this->getStatistics($merchantId),
            'recentItems' => $this->getRecentItems($merchantId),
            'recentPurchases' => $this->getRecentPurchases($merchantId),
            'salesChart' => $this->getSalesChartData($merchantId),
            'hasPendingVerification' => $this->hasPendingVerification($merchantId),
        ];
    }

    /**
     * Get all statistics in a single optimized query
     *
     * @param int $merchantId
     * @return array
     */
    private function getStatistics(int $merchantId): array
    {
        // Single optimized query for all purchase statistics
        $purchaseStats = DB::table('merchant_purchases')
            ->where('user_id', $merchantId)
            ->selectRaw('
                COUNT(CASE WHEN status = "pending" THEN 1 END) as pending,
                COUNT(CASE WHEN status = "processing" THEN 1 END) as processing,
                COUNT(CASE WHEN status = "completed" THEN 1 END) as completed,
                SUM(CASE WHEN status = "completed" THEN price ELSE 0 END) as totalSales,
                SUM(CASE WHEN status = "completed" THEN qty ELSE 0 END) as totalItemsSold
            ')
            ->first();

        // Item statistics
        $totalItems = $this->itemQuery::make()
            ->forMerchant($merchantId)
            ->count();

        $activeItems = $this->itemQuery::make()
            ->forMerchant($merchantId)
            ->active()
            ->count();

        // Get merchant balance
        $merchant = DB::table('users')
            ->where('id', $merchantId)
            ->select('balance')
            ->first();

        return [
            // Purchase statistics
            'pending' => (int) $purchaseStats->pending,
            'processing' => (int) $purchaseStats->processing,
            'completed' => (int) $purchaseStats->completed,
            'totalSales' => (float) $purchaseStats->totalSales,
            'totalItemsSold' => (int) $purchaseStats->totalItemsSold,
            
            // Item statistics
            'totalItems' => $totalItems,
            'activeItems' => $activeItems,
            
            // Financial
            'currentBalance' => (float) ($merchant->balance ?? 0),
        ];
    }

    /**
     * Get recent merchant items (last 5)
     *
     * @param int $merchantId
     * @return array
     */
    private function getRecentItems(int $merchantId): array
    {
        $items = $this->itemQuery::make()
            ->forMerchant($merchantId)
            ->active()
            ->withRelations()
            ->latest()
            ->paginate(5)
            ->items();

        return collect($items)->map(fn($item) => $this->displayService->format($item))
            ->toArray();
    }

    /**
     * Get recent purchases (last 5)
     *
     * @param int $merchantId
     * @return Collection
     */
    private function getRecentPurchases(int $merchantId): Collection
    {
        return MerchantPurchase::where('user_id', $merchantId)
            ->latest()
            ->take(5)
            ->get();
    }

    /**
     * Generate sales chart data for last 30 days
     *
     * @param int $merchantId
     * @return array
     */
    private function getSalesChartData(int $merchantId): array
    {
        $startDate = now()->subDays(29)->startOfDay();
        $endDate = now()->endOfDay();

        // Get sales data grouped by date
        $salesData = MerchantPurchase::where('user_id', $merchantId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, SUM(price) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        return $this->formatChartData($salesData);
    }

    /**
     * Format sales data for chart display
     *
     * @param array $salesData
     * @return array
     */
    private function formatChartData(array $salesData): array
    {
        $days = [];
        $sales = [];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $formattedDate = now()->subDays($i)->format('d M');
            
            $days[] = $formattedDate;
            $sales[] = $salesData[$date] ?? 0;
        }

        return [
            'days' => $days,
            'sales' => $sales,
            // Format for JavaScript chart (comma-separated strings with quotes)
            'daysFormatted' => implode(',', array_map(fn($d) => "'{$d}'", $days)),
            'salesFormatted' => implode(',', array_map(fn($s) => "'{$s}'", $sales)),
        ];
    }

    /**
     * Check if merchant has pending verification
     *
     * @param int $merchantId
     * @return bool
     */
    private function hasPendingVerification(int $merchantId): bool
    {
        $trustBadge = TrustBadge::where('user_id', $merchantId)->first();
        return $trustBadge && $trustBadge->status === 'pending';
    }
}
