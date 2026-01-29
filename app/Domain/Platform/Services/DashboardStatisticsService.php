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
     * @return array
     */
    public function getBasicCounts(): array
    {
        $thirtyDaysAgo = now()->subDays(30);

        return [
            'users' => User::count(),
            'catalogItems' => CatalogItem::count(),
            'newCustomersLast30Days' => User::where('created_at', '>', $thirtyDaysAgo)->count(),
            'totalSalesLast30Days' => Purchase::where('status', 'completed')
                ->where('created_at', '>', $thirtyDaysAgo)
                ->count(),
            'totalSalesAllTime' => Purchase::where('status', 'completed')->count(),
        ];
    }

    /**
     * Get latest catalog items with active merchant offers.
     *
     * @param int $limit
     * @return Collection Pre-computed display data for each item
     */
    public function getLatestCatalogItems(int $limit = 5): Collection
    {
        $items = CatalogItem::whereHas('merchantItems', fn($q) => $q->where('status', 1))
            ->with([
                'merchantItems' => fn($q) => $q->where('status', 1)->orderBy('price'),
                'fitments.brand:id,name,name_ar,photo',
            ])
            ->latest('id')
            ->take($limit)
            ->get();

        return $this->formatCatalogItemsForDisplay($items);
    }

    /**
     * Get popular catalog items by views.
     *
     * @param int $limit
     * @return Collection Pre-computed display data for each item
     */
    public function getPopularCatalogItems(int $limit = 5): Collection
    {
        $items = CatalogItem::whereHas('merchantItems', fn($q) => $q->where('status', 1))
            ->with([
                'merchantItems' => fn($q) => $q->where('status', 1)->orderBy('price'),
                'fitments.brand:id,name,name_ar,photo',
            ])
            ->orderByDesc('views')
            ->take($limit)
            ->get();

        return $this->formatCatalogItemsForDisplay($items);
    }

    /**
     * Format catalog items for dashboard display.
     * Pre-computes all values needed by the View (DATA_FLOW_POLICY).
     *
     * @param Collection $items
     * @return Collection
     */
    private function formatCatalogItemsForDisplay(Collection $items): Collection
    {
        return $items->transform(function ($catalogItem) {
            // Best merchant item (lowest price)
            $bestMerchantItem = $catalogItem->merchantItems->first();

            // Photo URL
            $photo = $catalogItem->photo;
            $catalogItem->photo_url = $photo
                ? (filter_var($photo, FILTER_VALIDATE_URL) ? $photo : \Illuminate\Support\Facades\Storage::url($photo))
                : asset('assets/images/noimage.png');

            // Localized name
            $catalogItem->localized_name = getLocalizedCatalogItemName($catalogItem, 50);

            // First brand name from fitments
            $firstBrand = $catalogItem->fitments
                ->map(fn($f) => $f->brand)
                ->filter()
                ->first();
            $catalogItem->first_brand_name = $firstBrand
                ? getLocalizedBrandName($firstBrand)
                : __('N/A');

            // Views with default
            $catalogItem->views_count = $catalogItem->views ?? 0;

            // Part number with default
            $catalogItem->part_number_display = $catalogItem->part_number ?? __('N/A');

            // Pre-formatted price
            $catalogItem->price_formatted = $bestMerchantItem
                ? app(\App\Domain\Merchant\Services\MerchantItemDisplayService::class)->formatPrice($bestMerchantItem)
                : __('N/A');

            // Edit URL
            $catalogItem->edit_url = route('operator-catalog-item-edit', $catalogItem->id);

            // View URL (front page - part result)
            $catalogItem->view_url = $catalogItem->part_number
                ? route('front.part-result', $catalogItem->part_number)
                : '#';

            return $catalogItem;
        });
    }

    /**
     * Get recent purchases.
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentPurchases(int $limit = 5): Collection
    {
        $purchases = Purchase::latest('id')->take($limit)->get();

        // PRE-COMPUTED: Add formatted date (DATA_FLOW_POLICY)
        $purchases->transform(function ($purchase) {
            $purchase->created_at_formatted = $purchase->created_at?->format('Y-m-d') ?? 'N/A';
            return $purchase;
        });

        return $purchases;
    }

    /**
     * Get recent users.
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentUsers(int $limit = 5): Collection
    {
        $users = User::latest('id')->take($limit)->get();

        // PRE-COMPUTED: Add formatted date (DATA_FLOW_POLICY)
        $users->transform(function ($user) {
            $user->created_at_formatted = $user->created_at?->format('Y-m-d') ?? 'N/A';
            return $user;
        });

        return $users;
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
        $purchaseCounts = $this->getPurchaseCountsByStatus();

        return [
            // Pre-computed counts - View should use these directly
            'pendingCount' => $purchaseCounts['pending'],
            'processingCount' => $purchaseCounts['processing'],
            'completedCount' => $purchaseCounts['completed'],
            // Legacy: return counts as values for existing view (count($pending))
            'pending' => $purchaseCounts['pending'],
            'processing' => $purchaseCounts['processing'],
            'completed' => $purchaseCounts['completed'],
            'days' => $chartData['days'],
            'sales' => $chartData['sales'],
            'users' => $counts['users'],
            'catalogItems' => $counts['catalogItems'],
            'newCustomersLast30Days' => $counts['newCustomersLast30Days'],
            'totalSalesLast30Days' => $counts['totalSalesLast30Days'],
            'totalSalesAllTime' => $counts['totalSalesAllTime'],
            'latestCatalogItems' => $this->getLatestCatalogItems(5),
            'recentPurchases' => $this->getRecentPurchases(5),
            'popularCatalogItems' => $this->getPopularCatalogItems(5),
            'recentUsers' => $this->getRecentUsers(5),
        ];
    }
}
