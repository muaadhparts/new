<?php

namespace App\Domain\Shipping\Scopes;

use Illuminate\Database\Eloquent\Builder;

/**
 * Shipment Scope
 *
 * Local scopes for shipment tracking queries.
 */
trait ShipmentScope
{
    /**
     * Scope to filter by courier.
     */
    public function scopeForCourier(Builder $query, int $courierId): Builder
    {
        return $query->where('courier_id', $courierId);
    }

    /**
     * Scope to filter by merchant purchase.
     */
    public function scopeForMerchantPurchase(Builder $query, int $merchantPurchaseId): Builder
    {
        return $query->where('merchant_purchase_id', $merchantPurchaseId);
    }

    /**
     * Scope to get pending shipments.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get picked up shipments.
     */
    public function scopePickedUp(Builder $query): Builder
    {
        return $query->where('status', 'picked_up');
    }

    /**
     * Scope to get in-transit shipments.
     */
    public function scopeInTransit(Builder $query): Builder
    {
        return $query->where('status', 'in_transit');
    }

    /**
     * Scope to get out-for-delivery shipments.
     */
    public function scopeOutForDelivery(Builder $query): Builder
    {
        return $query->where('status', 'out_for_delivery');
    }

    /**
     * Scope to get delivered shipments.
     */
    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Scope to get failed shipments.
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get active shipments (not delivered/failed).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['delivered', 'failed', 'cancelled']);
    }

    /**
     * Scope to get completed shipments.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereIn('status', ['delivered', 'returned']);
    }

    /**
     * Scope to get delayed shipments.
     */
    public function scopeDelayed(Builder $query): Builder
    {
        return $query->whereNotNull('estimated_delivery')
            ->where('estimated_delivery', '<', now())
            ->whereNotIn('status', ['delivered', 'failed', 'cancelled']);
    }

    /**
     * Scope to get shipments by tracking number.
     */
    public function scopeByTrackingNumber(Builder $query, string $trackingNumber): Builder
    {
        return $query->where('tracking_number', $trackingNumber);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeShippedBetween(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('shipped_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get today's shipments.
     */
    public function scopeShippedToday(Builder $query): Builder
    {
        return $query->whereDate('shipped_at', today());
    }

    /**
     * Scope to get recently updated.
     */
    public function scopeRecentlyUpdated(Builder $query, int $hours = 24): Builder
    {
        return $query->where('updated_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope to get stale shipments (no update in X hours).
     */
    public function scopeStale(Builder $query, int $hours = 48): Builder
    {
        return $query->active()
            ->where('updated_at', '<', now()->subHours($hours));
    }
}
