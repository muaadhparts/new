<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Models\Withdraw;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service for withdraw fee calculations and reports.
 */
class WithdrawCalculationService
{
    /**
     * Get withdraw fee summary for a date range.
     *
     * @param string|null $startDate Y-m-d format
     * @param string|null $endDate Y-m-d format
     * @return array
     */
    public function getFeeSummary(?string $startDate = null, ?string $endDate = null): array
    {
        $withdraws = $this->getCompletedWithdraws($startDate, $endDate);

        return [
            'withdraws' => $withdraws,
            'total_fee' => $withdraws->sum('fee'),
            'total_amount' => $withdraws->sum('amount'),
            'count' => $withdraws->count(),
        ];
    }

    /**
     * Get completed withdraws with user data for a date range.
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return Collection
     */
    public function getCompletedWithdraws(?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = Withdraw::with('user')->where('status', 'completed');

        if ($startDate && $endDate) {
            $query->whereDate('created_at', '>=', $startDate)
                  ->whereDate('created_at', '<=', $endDate);
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Get fee sum for last N days.
     *
     * @param int $days
     * @return float
     */
    public function getFeeSumForLastDays(int $days): float
    {
        return Withdraw::whereDate('created_at', '>=', Carbon::now()->subDays($days))
            ->whereDate('created_at', '<=', Carbon::now())
            ->where('status', 'completed')
            ->sum('fee');
    }

    /**
     * Get fee sum for current month.
     *
     * @return float
     */
    public function getFeeSumForCurrentMonth(): float
    {
        return Withdraw::whereDate('created_at', '>=', Carbon::now()->startOfMonth())
            ->whereDate('created_at', '<=', Carbon::now())
            ->where('status', 'completed')
            ->sum('fee');
    }

    /**
     * Get withdraw income report data for the view.
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getWithdrawIncomeReport(?string $startDate = null, ?string $endDate = null): array
    {
        $summary = $this->getFeeSummary($startDate, $endDate);

        return [
            'withdraws' => $summary['withdraws'],
            'total_fee' => $summary['total_fee'],
            'last_30_days_fee' => $this->getFeeSumForLastDays(30),
            'current_month_fee' => $this->getFeeSumForCurrentMonth(),
        ];
    }
}
