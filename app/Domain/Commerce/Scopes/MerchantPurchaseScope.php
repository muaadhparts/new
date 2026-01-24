<?php

namespace App\Domain\Commerce\Scopes;

use Illuminate\Database\Eloquent\Builder;

/**
 * Merchant Purchase Scope
 *
 * Local scopes for merchant purchase queries.
 */
trait MerchantPurchaseScope
{
    /**
     * Scope to filter by merchant.
     */
    public function scopeForMerchant(Builder $query, int $merchantId): Builder
    {
        return $query->where('user_id', $merchantId);
    }

    /**
     * Scope to filter by parent purchase.
     */
    public function scopeForPurchase(Builder $query, int $purchaseId): Builder
    {
        return $query->where('purchase_id', $purchaseId);
    }

    /**
     * Scope to get pending orders.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get accepted orders.
     */
    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope to get ready orders.
     */
    public function scopeReady(Builder $query): Builder
    {
        return $query->where('status', 'ready');
    }

    /**
     * Scope to get shipped orders.
     */
    public function scopeShipped(Builder $query): Builder
    {
        return $query->where('status', 'shipped');
    }

    /**
     * Scope to get delivered orders.
     */
    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Scope to get actionable orders (needs merchant action).
     */
    public function scopeActionable(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'accepted']);
    }

    /**
     * Scope to get orders needing shipment.
     */
    public function scopeNeedsShipment(Builder $query): Builder
    {
        return $query->where('status', 'ready')
            ->whereDoesntHave('shipmentTracking');
    }

    /**
     * Scope to get orders with shipment.
     */
    public function scopeWithShipment(Builder $query): Builder
    {
        return $query->whereHas('shipmentTracking');
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateBetween(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get today's orders.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope to get overdue orders.
     */
    public function scopeOverdue(Builder $query, int $hours = 48): Builder
    {
        return $query->whereIn('status', ['pending', 'accepted'])
            ->where('created_at', '<', now()->subHours($hours));
    }

    /**
     * Scope to calculate total commission.
     */
    public function scopeWithCommissionSum(Builder $query): Builder
    {
        return $query->selectRaw('SUM(commission_amount) as total_commission');
    }
}
