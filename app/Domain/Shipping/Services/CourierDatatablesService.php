<?php

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\Models\Courier;
use App\Domain\Accounting\Models\Withdraw;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service for optimized Courier datatables queries.
 * Uses withCount and eager loading to prevent N+1 problems.
 */
class CourierDatatablesService
{
    /**
     * Get couriers with delivery count (optimized).
     * Uses withCount instead of loading all deliveries.
     *
     * @return Collection
     */
    public function getCouriersWithDeliveryCount(): Collection
    {
        return Courier::withCount('deliveries')
            ->latest('id')
            ->get();
    }

    /**
     * Get courier withdraws with courier relationship (optimized).
     * Eager loads courier to prevent N+1.
     *
     * @return Collection
     */
    public function getCourierWithdraws(): Collection
    {
        return Withdraw::where('type', 'courier')
            ->with('courier:id,email,phone')
            ->latest('id')
            ->get();
    }
}
