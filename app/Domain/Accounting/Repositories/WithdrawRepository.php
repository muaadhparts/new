<?php

namespace App\Domain\Accounting\Repositories;

use App\Domain\Platform\Repositories\BaseRepository;
use App\Domain\Accounting\Models\Withdraw;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Withdraw Repository
 *
 * Repository for withdrawal data access.
 */
class WithdrawRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return Withdraw::class;
    }

    /**
     * Get withdrawals by merchant.
     */
    public function getByMerchant(int $merchantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('merchant_id', $merchantId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get pending withdrawals.
     */
    public function getPending(): Collection
    {
        return $this->findBy('status', 'pending');
    }

    /**
     * Get pending withdrawals by merchant.
     */
    public function getPendingByMerchant(int $merchantId): Collection
    {
        return $this->query()
            ->where('merchant_id', $merchantId)
            ->where('status', 'pending')
            ->get();
    }

    /**
     * Get withdrawals by status.
     */
    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get total withdrawn by merchant.
     */
    public function getTotalWithdrawnByMerchant(int $merchantId): float
    {
        return (float) $this->query()
            ->where('merchant_id', $merchantId)
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Get withdrawals for date range.
     */
    public function getForDateRange(string $startDate, string $endDate): Collection
    {
        return $this->query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
    }

    /**
     * Has pending withdrawal.
     */
    public function hasPending(int $merchantId): bool
    {
        return $this->query()
            ->where('merchant_id', $merchantId)
            ->where('status', 'pending')
            ->exists();
    }
}
