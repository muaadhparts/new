<?php

namespace App\Domain\Shipping\Repositories;

use App\Domain\Platform\Repositories\BaseRepository;
use App\Domain\Shipping\Models\Shipment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Shipment Repository
 *
 * Repository for shipment data access.
 */
class ShipmentRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return Shipment::class;
    }

    /**
     * Find by tracking number.
     */
    public function findByTrackingNumber(string $trackingNumber): ?Shipment
    {
        return $this->findFirstBy('tracking_number', $trackingNumber);
    }

    /**
     * Get shipments by status.
     */
    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get shipments by courier.
     */
    public function getByCourier(int $courierId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('courier_id', $courierId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get pending shipments.
     */
    public function getPending(): Collection
    {
        return $this->findBy('status', 'pending');
    }

    /**
     * Get in-transit shipments.
     */
    public function getInTransit(): Collection
    {
        return $this->findBy('status', 'in_transit');
    }

    /**
     * Get shipments for purchase.
     */
    public function getByPurchase(int $purchaseId): Collection
    {
        return $this->findBy('purchase_id', $purchaseId);
    }

    /**
     * Get shipment with tracking history.
     */
    public function getWithTracking(int $id): ?Shipment
    {
        return $this->query()
            ->with('trackingHistory')
            ->find($id);
    }

    /**
     * Get overdue shipments.
     */
    public function getOverdue(int $days = 7): Collection
    {
        return $this->query()
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->where('created_at', '<', now()->subDays($days))
            ->get();
    }
}
