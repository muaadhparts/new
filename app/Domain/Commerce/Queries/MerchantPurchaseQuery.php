<?php

namespace App\Domain\Commerce\Queries;

use App\Models\MerchantPurchase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * MerchantPurchaseQuery - Query builder for merchant purchases
 *
 * Provides fluent interface for building MerchantPurchase queries.
 * MerchantPurchase represents a portion of a Purchase for a specific merchant.
 */
class MerchantPurchaseQuery
{
    protected Builder $query;

    public function __construct()
    {
        $this->query = MerchantPurchase::query();
    }

    /**
     * Create new query instance
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Filter by merchant ID
     */
    public function forMerchant(int $merchantId): self
    {
        $this->query->where('user_id', $merchantId);
        return $this;
    }

    /**
     * Filter by branch ID
     */
    public function forBranch(int $branchId): self
    {
        $this->query->where('branch_id', $branchId);
        return $this;
    }

    /**
     * Filter by parent purchase ID
     */
    public function forPurchase(int $purchaseId): self
    {
        $this->query->where('purchase_id', $purchaseId);
        return $this;
    }

    /**
     * Filter by status
     */
    public function withStatus(string $status): self
    {
        $this->query->where('status', $status);
        return $this;
    }

    /**
     * Filter by multiple statuses
     */
    public function withStatuses(array $statuses): self
    {
        $this->query->whereIn('status', $statuses);
        return $this;
    }

    /**
     * Filter new orders (pending)
     */
    public function newOrders(): self
    {
        return $this->withStatus('pending');
    }

    /**
     * Filter in-progress orders
     */
    public function inProgress(): self
    {
        return $this->withStatuses(['processing', 'shipped']);
    }

    /**
     * Filter delivered orders
     */
    public function delivered(): self
    {
        return $this->withStatus('delivered');
    }

    /**
     * Filter by date range
     */
    public function betweenDates(string $startDate, string $endDate): self
    {
        $this->query->whereBetween('created_at', [$startDate, $endDate]);
        return $this;
    }

    /**
     * Filter today's orders
     */
    public function today(): self
    {
        $this->query->whereDate('created_at', today());
        return $this;
    }

    /**
     * Filter this week's orders
     */
    public function thisWeek(): self
    {
        $this->query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
        return $this;
    }

    /**
     * Filter this month's orders
     */
    public function thisMonth(): self
    {
        $this->query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
        return $this;
    }

    /**
     * Filter by shipping status
     */
    public function withShippingStatus(string $status): self
    {
        $this->query->where('shipping_status', $status);
        return $this;
    }

    /**
     * Filter orders needing shipping
     */
    public function needsShipping(): self
    {
        $this->query->whereIn('status', ['processing', 'confirmed'])
            ->whereNull('shipping_status');
        return $this;
    }

    /**
     * Filter by minimum total
     */
    public function minTotal(float $amount): self
    {
        $this->query->where('total', '>=', $amount);
        return $this;
    }

    /**
     * Search by order number
     */
    public function search(string $term): self
    {
        $this->query->whereHas('purchase', function ($q) use ($term) {
            $q->where('order_number', 'like', '%' . $term . '%')
                ->orWhere('customer_name', 'like', '%' . $term . '%')
                ->orWhere('customer_phone', 'like', '%' . $term . '%');
        });
        return $this;
    }

    /**
     * Order by newest first
     */
    public function latest(): self
    {
        $this->query->orderBy('created_at', 'desc');
        return $this;
    }

    /**
     * Order by oldest first
     */
    public function oldest(): self
    {
        $this->query->orderBy('created_at', 'asc');
        return $this;
    }

    /**
     * Eager load relations
     */
    public function withRelations(): self
    {
        $this->query->with([
            'purchase',
            'purchase.user',
            'merchant',
            'branch',
            'shipmentTracking',
        ]);
        return $this;
    }

    /**
     * Get paginated results
     */
    public function paginate(int $perPage = 15)
    {
        return $this->query->paginate($perPage)->withQueryString();
    }

    /**
     * Get all results
     */
    public function get(): Collection
    {
        return $this->query->get();
    }

    /**
     * Get first result
     */
    public function first(): ?MerchantPurchase
    {
        return $this->query->first();
    }

    /**
     * Get count
     */
    public function count(): int
    {
        return $this->query->count();
    }

    /**
     * Get sum of totals
     */
    public function totalAmount(): float
    {
        return (float) $this->query->sum('total');
    }

    /**
     * Get daily sales summary
     */
    public function getDailySummary(int $merchantId, int $days = 7): Collection
    {
        return MerchantPurchase::where('user_id', $merchantId)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total) as revenue')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Get the underlying query builder
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }
}
