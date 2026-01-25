<?php

namespace App\Domain\Platform\Services;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service for operator dashboard statistics.
 * Optimizes queries to avoid N+1 and loop-based database calls.
 */
class DashboardStatisticsService
{
    /**
     * Get purchase counts by status.
     *
     * @return array ['pending' => int, 'processing' => int, 'completed' => int]
     */
    public function getPurchaseCountsByStatus(): array
    {
        $counts = Purchase::query()
            ->select('status', DB::raw('count(*) as count'))
            ->whereIn('status', ['pending', 'processing', 'completed'])
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'pending' => $counts['pending'] ?? 0,
            'processing' => $counts['processing'] ?? 0,
            'completed' => $counts['completed'] ?? 0,
        ];
    }

    /**
     * Get purchases by status (for display).
     *
     * @param string $status
     * @return Collection
     */
    public function getPurchasesByStatus(string $status): Collection
    {
        return Purchase::where('status', '=', $status)->get();
    }

    /**
     * Get sales data for last N days (optimized - single query).
     *
     * @param int $days Number of days to fetch
     * @return array ['days' => string, 'sales' => string] - comma-separated for JS
     */
    public function getSalesChartData(int $days = 30): array
    {
        // Single query to get all sales counts for the period
        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

        $salesByDate = Purchase::where('status', '=', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('count', 'date')
            ->toArray();

        // Build the chart strings in chronological order (oldest to newest for chart display)
        // But the original code was newest to oldest (0 = today, 29 = 29 days ago)
        $daysArray = [];
        $salesArray = [];

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $displayDate = now()->subDays($i)->format('d M');

            $daysArray[] = "'{$displayDate}'";
            $salesArray[] = "'" . ($salesByDate[$date] ?? 0) . "'";
        }

        return [
            'days' => implode(',', $daysArray),
            'sales' => implode(',', $salesArray),
        ];
    }

    /**
     * Get basic counts for dashboard.
     *
     * @return array ['users' => int, 'catalogItems' => int]
     */
    public function getBasicCounts(): array
    {
        return [
            'users' => User::count(),
            'catalogItems' => CatalogItem::count(),
        ];
    }

    /**
     * Get latest catalog items with active merchant offers.
     *
     * @param int $limit
     * @return Collection
     */
    public function getLatestCatalogItems(int $limit = 5): Collection
    {
        return CatalogItem::whereHas('merchantItems', fn($q) => $q->where('status', 1))
            ->with(['merchantItems' => fn($q) => $q->where('status', 1)->orderBy('price')])
            ->latest('id')
            ->take($limit)
            ->get();
    }

    /**
     * Get popular catalog items by views.
     *
     * @param int $limit
     * @return Collection
     */
    public function getPopularCatalogItems(int $limit = 5): Collection
    {
        return CatalogItem::whereHas('merchantItems', fn($q) => $q->where('status', 1))
            ->with(['merchantItems' => fn($q) => $q->where('status', 1)->orderBy('price')])
            ->orderByDesc('views')
            ->take($limit)
            ->get();
    }

    /**
     * Get recent purchases.
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentPurchases(int $limit = 5): Collection
    {
        return Purchase::latest('id')->take($limit)->get();
    }

    /**
     * Get recent users.
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentUsers(int $limit = 5): Collection
    {
        return User::latest('id')->take($limit)->get();
    }

    /**
     * Get all dashboard data in one call.
     *
     * @return array
     */
    public function getDashboardData(): array
    {
        $chartData = $this->getSalesChartData(30);
        $counts = $this->getBasicCounts();

        return [
            'pending' => $this->getPurchasesByStatus('pending'),
            'processing' => $this->getPurchasesByStatus('processing'),
            'completed' => $this->getPurchasesByStatus('completed'),
            'days' => $chartData['days'],
            'sales' => $chartData['sales'],
            'users' => $counts['users'],
            'catalogItems' => $counts['catalogItems'],
            'latestCatalogItems' => $this->getLatestCatalogItems(5),
            'recentPurchases' => $this->getRecentPurchases(5),
            'popularCatalogItems' => $this->getPopularCatalogItems(5),
            'recentUsers' => $this->getRecentUsers(5),
        ];
    }
}
