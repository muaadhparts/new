<?php

namespace App\Domain\Shipping\Policies;

use App\Models\User;
use App\Models\ShipmentTracking;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Shipment Policy
 *
 * Determines authorization for shipment actions.
 */
class ShipmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view shipment tracking
     */
    public function view(User $user, ShipmentTracking $shipment): bool
    {
        // Load purchase if not loaded
        $purchase = $shipment->purchase;

        if (!$purchase) {
            return false;
        }

        // Customer can view their shipment
        if ($user->id === $purchase->user_id) {
            return true;
        }

        // Merchant can view if they're part of the order
        if ($user->role === 'merchant') {
            return $purchase->merchantPurchases()
                ->where('merchant_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine if user can update shipment status
     */
    public function updateStatus(User $user, ShipmentTracking $shipment): bool
    {
        // Only merchants can update their shipments
        if ($user->role !== 'merchant') {
            return false;
        }

        $purchase = $shipment->purchase;
        if (!$purchase) {
            return false;
        }

        // Check if merchant is part of this order
        return $purchase->merchantPurchases()
            ->where('merchant_id', $user->id)
            ->exists();
    }

    /**
     * Determine if user can cancel shipment
     */
    public function cancel(User $user, ShipmentTracking $shipment): bool
    {
        // Must be merchant
        if ($user->role !== 'merchant') {
            return false;
        }

        // Can only cancel before pickup
        if (!in_array($shipment->status, ['pending', 'processing'])) {
            return false;
        }

        $purchase = $shipment->purchase;
        return $purchase && $purchase->merchantPurchases()
            ->where('merchant_id', $user->id)
            ->exists();
    }

    /**
     * Determine if user can request pickup
     */
    public function requestPickup(User $user, ShipmentTracking $shipment): bool
    {
        if ($user->role !== 'merchant') {
            return false;
        }

        // Can only request pickup for pending shipments
        if ($shipment->status !== 'pending') {
            return false;
        }

        $purchase = $shipment->purchase;
        return $purchase && $purchase->merchantPurchases()
            ->where('merchant_id', $user->id)
            ->exists();
    }

    /**
     * Determine if user can print label
     */
    public function printLabel(User $user, ShipmentTracking $shipment): bool
    {
        if ($user->role !== 'merchant') {
            return false;
        }

        $purchase = $shipment->purchase;
        return $purchase && $purchase->merchantPurchases()
            ->where('merchant_id', $user->id)
            ->exists();
    }
}
