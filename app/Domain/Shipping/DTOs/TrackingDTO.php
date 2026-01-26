<?php

namespace App\Domain\Shipping\DTOs;

use App\Domain\Shipping\Models\ShipmentTracking;

/**
 * TrackingDTO - Pre-computed data for shipment tracking page
 *
 * DATA FLOW POLICY: Views must only read properties, no logic, no queries
 */
final class TrackingDTO
{
    public function __construct(
        // Shipment info
        public readonly string $trackingNumber,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly string $statusClass,

        // Order info
        public readonly ?string $orderNumber,
        public readonly string $formattedOrderDate,

        // Shipping info
        public readonly ?string $carrierName,
        public readonly ?string $carrierLogo,
        public readonly ?string $carrierTrackingUrl,

        // Origin and destination
        public readonly ?string $originCity,
        public readonly ?string $destinationCity,
        public readonly ?string $destinationAddress,

        // Timeline
        public readonly array $timeline,
        public readonly string $currentStep,

        // Estimated delivery
        public readonly ?string $estimatedDelivery,
        public readonly bool $isDelivered,

        // Meta
        public readonly string $lastUpdated,
    ) {}

    /**
     * Build DTO from ShipmentTracking model
     */
    public static function fromModel(ShipmentTracking $tracking): self
    {
        $status = $tracking->status ?? 'pending';

        return new self(
            // Shipment info
            trackingNumber: $tracking->tracking_number ?? '',
            status: $status,
            statusLabel: self::getStatusLabel($status),
            statusClass: self::getStatusClass($status),

            // Order info
            orderNumber: $tracking->merchantPurchase?->purchase_number,
            formattedOrderDate: $tracking->created_at?->format('Y-m-d H:i') ?? '',

            // Shipping info
            carrierName: $tracking->carrier_name ?? $tracking->deliveryCourier?->name,
            carrierLogo: $tracking->deliveryCourier?->logo_url,
            carrierTrackingUrl: $tracking->carrier_tracking_url,

            // Origin and destination
            originCity: $tracking->origin_city,
            destinationCity: $tracking->destination_city,
            destinationAddress: $tracking->destination_address,

            // Timeline
            timeline: self::buildTimeline($tracking),
            currentStep: self::getCurrentStep($status),

            // Estimated delivery
            estimatedDelivery: $tracking->estimated_delivery?->format('Y-m-d'),
            isDelivered: $status === 'delivered',

            // Meta
            lastUpdated: $tracking->updated_at?->diffForHumans() ?? '',
        );
    }

    /**
     * Build tracking timeline
     */
    private static function buildTimeline(ShipmentTracking $tracking): array
    {
        $history = $tracking->tracking_history ?? [];

        if (empty($history)) {
            // Default timeline based on status
            return self::buildDefaultTimeline($tracking->status ?? 'pending');
        }

        return collect($history)->map(function ($event) {
            return [
                'status' => $event['status'] ?? '',
                'label' => $event['description'] ?? self::getStatusLabel($event['status'] ?? ''),
                'location' => $event['location'] ?? null,
                'date' => $event['date'] ?? null,
                'time' => $event['time'] ?? null,
                'completed' => true,
            ];
        })->toArray();
    }

    /**
     * Build default timeline based on status
     */
    private static function buildDefaultTimeline(string $currentStatus): array
    {
        $statuses = ['pending', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered'];
        $currentIndex = array_search($currentStatus, $statuses);

        return collect($statuses)->map(function ($status, $index) use ($currentIndex) {
            return [
                'status' => $status,
                'label' => self::getStatusLabel($status),
                'location' => null,
                'date' => null,
                'time' => null,
                'completed' => $index <= $currentIndex,
                'current' => $index === $currentIndex,
            ];
        })->toArray();
    }

    /**
     * Get current step for progress indicator
     */
    private static function getCurrentStep(string $status): string
    {
        return match ($status) {
            'pending' => '1',
            'picked_up' => '2',
            'in_transit' => '3',
            'out_for_delivery' => '4',
            'delivered' => '5',
            default => '1',
        };
    }

    /**
     * Get localized status label
     */
    private static function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => __('Order Placed'),
            'picked_up' => __('Picked Up'),
            'in_transit' => __('In Transit'),
            'out_for_delivery' => __('Out for Delivery'),
            'delivered' => __('Delivered'),
            'returned' => __('Returned'),
            'cancelled' => __('Cancelled'),
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * Get CSS class for status badge
     */
    private static function getStatusClass(string $status): string
    {
        return match ($status) {
            'pending' => 'bg-warning text-dark',
            'picked_up', 'in_transit' => 'bg-info text-white',
            'out_for_delivery' => 'bg-primary text-white',
            'delivered' => 'bg-success text-white',
            'returned', 'cancelled' => 'bg-danger text-white',
            default => 'bg-secondary text-white',
        };
    }
}
