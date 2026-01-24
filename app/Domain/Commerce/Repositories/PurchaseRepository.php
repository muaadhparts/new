<?php

namespace App\Domain\Commerce\Repositories;

use App\Domain\Platform\Repositories\BaseRepository;
use App\Domain\Commerce\Models\Purchase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Purchase Repository
 *
 * Repository for purchase (order) data access.
 */
class PurchaseRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return Purchase::class;
    }

    /**
     * Find by order number.
     */
    public function findByOrderNumber(string $orderNumber): ?Purchase
    {
        return $this->findFirstBy('order_number', $orderNumber);
    }

    /**
     * Get user orders.
     */
    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get orders by status.
     */
    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get pending orders.
     */
    public function getPending(): Collection
    {
        return $this->findBy('status', 'pending');
    }

    /**
     * Get orders for date range.
     */
    public function getForDateRange(string $startDate, string $endDate): Collection
    {
        return $this->query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get recent orders.
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->query()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get order with all relations.
     */
    public function getWithRelations(int $id): ?Purchase
    {
        return $this->query()
            ->with(['user', 'merchantPurchases.merchant', 'shipment'])
            ->find($id);
    }

    /**
     * Get total orders count by status.
     */
    public function getCountByStatus(): array
    {
        return $this->query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }
}
