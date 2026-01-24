<?php

namespace App\Domain\Commerce\Enums;

/**
 * Purchase Status Enum
 *
 * Represents all possible states of a purchase order.
 */
enum PurchaseStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    /**
     * Get Arabic label
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'قيد الانتظار',
            self::CONFIRMED => 'مؤكد',
            self::PROCESSING => 'قيد التجهيز',
            self::SHIPPED => 'تم الشحن',
            self::DELIVERED => 'تم التوصيل',
            self::CANCELLED => 'ملغي',
            self::REFUNDED => 'مسترد',
        };
    }

    /**
     * Get CSS color class
     */
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'info',
            self::PROCESSING => 'primary',
            self::SHIPPED => 'secondary',
            self::DELIVERED => 'success',
            self::CANCELLED => 'danger',
            self::REFUNDED => 'dark',
        };
    }

    /**
     * Get icon name
     */
    public function icon(): string
    {
        return match($this) {
            self::PENDING => 'clock',
            self::CONFIRMED => 'check-circle',
            self::PROCESSING => 'cog',
            self::SHIPPED => 'truck',
            self::DELIVERED => 'check-double',
            self::CANCELLED => 'times-circle',
            self::REFUNDED => 'undo',
        };
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED]);
    }

    /**
     * Check if order is complete
     */
    public function isComplete(): bool
    {
        return in_array($this, [self::DELIVERED, self::CANCELLED, self::REFUNDED]);
    }

    /**
     * Check if order is active
     */
    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED, self::PROCESSING, self::SHIPPED]);
    }

    /**
     * Get next possible statuses
     */
    public function nextStatuses(): array
    {
        return match($this) {
            self::PENDING => [self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::SHIPPED, self::CANCELLED],
            self::SHIPPED => [self::DELIVERED],
            self::DELIVERED => [self::REFUNDED],
            self::CANCELLED, self::REFUNDED => [],
        };
    }

    /**
     * Get all values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get options for select dropdown
     */
    public static function options(): array
    {
        return array_map(
            fn($case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }
}
