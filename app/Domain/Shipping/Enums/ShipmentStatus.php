<?php

namespace App\Domain\Shipping\Enums;

/**
 * Shipment Status Enum
 *
 * Represents all possible states of a shipment.
 */
enum ShipmentStatus: string
{
    case PENDING = 'pending';
    case PICKED_UP = 'picked_up';
    case IN_TRANSIT = 'in_transit';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case FAILED_ATTEMPT = 'failed_attempt';
    case RETURNED = 'returned';
    case CANCELLED = 'cancelled';

    /**
     * Get Arabic label
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'في انتظار الاستلام',
            self::PICKED_UP => 'تم الاستلام',
            self::IN_TRANSIT => 'في الطريق',
            self::OUT_FOR_DELIVERY => 'خارج للتوصيل',
            self::DELIVERED => 'تم التوصيل',
            self::FAILED_ATTEMPT => 'محاولة فاشلة',
            self::RETURNED => 'مرتجع',
            self::CANCELLED => 'ملغي',
        };
    }

    /**
     * Get CSS color class
     */
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::PICKED_UP => 'info',
            self::IN_TRANSIT => 'primary',
            self::OUT_FOR_DELIVERY => 'info',
            self::DELIVERED => 'success',
            self::FAILED_ATTEMPT => 'danger',
            self::RETURNED => 'secondary',
            self::CANCELLED => 'dark',
        };
    }

    /**
     * Get icon
     */
    public function icon(): string
    {
        return match($this) {
            self::PENDING => 'clock',
            self::PICKED_UP => 'box',
            self::IN_TRANSIT => 'truck',
            self::OUT_FOR_DELIVERY => 'truck-loading',
            self::DELIVERED => 'check-circle',
            self::FAILED_ATTEMPT => 'exclamation-circle',
            self::RETURNED => 'undo',
            self::CANCELLED => 'times-circle',
        };
    }

    /**
     * Check if shipment is complete
     */
    public function isComplete(): bool
    {
        return in_array($this, [self::DELIVERED, self::RETURNED, self::CANCELLED]);
    }

    /**
     * Check if shipment is active
     */
    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::PICKED_UP, self::IN_TRANSIT, self::OUT_FOR_DELIVERY]);
    }

    /**
     * Check if shipment was successful
     */
    public function isSuccessful(): bool
    {
        return $this === self::DELIVERED;
    }

    /**
     * Get progress percentage
     */
    public function progress(): int
    {
        return match($this) {
            self::PENDING => 10,
            self::PICKED_UP => 25,
            self::IN_TRANSIT => 50,
            self::OUT_FOR_DELIVERY => 75,
            self::DELIVERED => 100,
            self::FAILED_ATTEMPT => 75,
            self::RETURNED, self::CANCELLED => 0,
        };
    }

    /**
     * Get all values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
