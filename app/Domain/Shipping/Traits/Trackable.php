<?php

namespace App\Domain\Shipping\Traits;

use App\Domain\Shipping\Models\ShipmentTracking;
use App\Domain\Shipping\Enums\ShipmentStatus;

/**
 * Trackable Trait
 *
 * Provides shipment tracking functionality.
 */
trait Trackable
{
    /**
     * Get tracking number column
     */
    public function getTrackingNumberColumn(): string
    {
        return $this->trackingNumberColumn ?? 'tracking_number';
    }

    /**
     * Get tracking number
     */
    public function getTrackingNumber(): ?string
    {
        return $this->{$this->getTrackingNumberColumn()};
    }

    /**
     * Check if has tracking number
     */
    public function hasTrackingNumber(): bool
    {
        return !empty($this->getTrackingNumber());
    }

    /**
     * Get shipment tracking relationship
     */
    public function shipmentTracking()
    {
        return $this->hasOne(ShipmentTracking::class, 'purchase_id');
    }

    /**
     * Get tracking history
     */
    public function getTrackingHistory(): array
    {
        $tracking = $this->shipmentTracking;

        if (!$tracking) {
            return [];
        }

        return $tracking->tracking_history ?? [];
    }

    /**
     * Get current shipment status
     */
    public function getShipmentStatus(): ?ShipmentStatus
    {
        $tracking = $this->shipmentTracking;

        if (!$tracking) {
            return null;
        }

        $status = $tracking->status;

        if ($status instanceof ShipmentStatus) {
            return $status;
        }

        return ShipmentStatus::tryFrom($status);
    }

    /**
     * Check if shipment is delivered
     */
    public function isDelivered(): bool
    {
        return $this->getShipmentStatus() === ShipmentStatus::DELIVERED;
    }

    /**
     * Check if shipment is in transit
     */
    public function isInTransit(): bool
    {
        $status = $this->getShipmentStatus();
        return $status?->isActive() ?? false;
    }

    /**
     * Get estimated delivery date
     */
    public function getEstimatedDeliveryDate(): ?\Carbon\Carbon
    {
        return $this->shipmentTracking?->estimated_delivery;
    }

    /**
     * Get actual delivery date
     */
    public function getActualDeliveryDate(): ?\Carbon\Carbon
    {
        return $this->shipmentTracking?->delivered_at;
    }

    /**
     * Generate tracking URL
     */
    public function getTrackingUrl(): ?string
    {
        $trackingNumber = $this->getTrackingNumber();

        if (!$trackingNumber) {
            return null;
        }

        return route('tracking.show', ['tracking' => $trackingNumber]);
    }

    /**
     * Add tracking event
     */
    public function addTrackingEvent(string $status, ?string $location = null, ?string $notes = null): bool
    {
        $tracking = $this->shipmentTracking;

        if (!$tracking) {
            return false;
        }

        $history = $tracking->tracking_history ?? [];
        $history[] = [
            'status' => $status,
            'location' => $location,
            'notes' => $notes,
            'timestamp' => now()->toISOString(),
        ];

        return $tracking->update([
            'status' => $status,
            'tracking_history' => $history,
        ]);
    }

    /**
     * Scope with tracking
     */
    public function scopeWithTracking($query)
    {
        return $query->whereNotNull($this->getTrackingNumberColumn());
    }

    /**
     * Scope delivered
     */
    public function scopeDelivered($query)
    {
        return $query->whereHas('shipmentTracking', function ($q) {
            $q->where('status', 'delivered');
        });
    }
}
