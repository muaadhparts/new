<?php

namespace App\Domain\Commerce\Repositories;

use App\Domain\Platform\Repositories\BaseRepository;
use App\Domain\Commerce\Models\MerchantPurchase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Merchant Purchase Repository
 *
 * Repository for merchant purchase data access.
 */
class MerchantPurchaseRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return MerchantPurchase::class;
    }

    /**
     * Get merchant orders.
     */
    public function getByMerchant(int $merchantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('merchant_id', $merchantId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get merchant orders by status.
     */
    public function getByMerchantAndStatus(int $merchantId, string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('merchant_id', $merchantId)
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get pending merchant orders.
     */
    public function getPendingByMerchant(int $merchantId): Collection
    {
        return $this->query()
            ->where('merchant_id', $merchantId)
            ->where('status', 'pending')
            ->get();
    }

    /**
     * Get merchant orders for date range.
     */
    public function getByMerchantForDateRange(int $merchantId, string $startDate, string $endDate): Collection
    {
        return $this->query()
            ->where('merchant_id', $merchantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
    }

    /**
     * Get merchant revenue for period.
     */
    public function getMerchantRevenue(int $merchantId, string $startDate, string $endDate): float
    {
        return (float) $this->query()
            ->where('merchant_id', $merchantId)
            ->where('status', 'delivered')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total');
    }

    /**
     * Get order with purchase details.
     */
    public function getWithPurchase(int $id): ?MerchantPurchase
    {
        return $this->query()
            ->with(['purchase.user', 'merchant'])
            ->find($id);
    }
}
