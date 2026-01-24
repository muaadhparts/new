<?php

namespace App\Domain\Shipping\Enums;

/**
 * Shipping Type Enum
 *
 * Represents available shipping/delivery types.
 */
enum ShippingType: string
{
    case STANDARD = 'standard';
    case EXPRESS = 'express';
    case SAME_DAY = 'same_day';
    case PICKUP = 'pickup';
    case FREE = 'free';

    /**
     * Get Arabic label
     */
    public function label(): string
    {
        return match($this) {
            self::STANDARD => 'شحن عادي',
            self::EXPRESS => 'شحن سريع',
            self::SAME_DAY => 'توصيل نفس اليوم',
            self::PICKUP => 'استلام من الفرع',
            self::FREE => 'شحن مجاني',
        };
    }

    /**
     * Get estimated days
     */
    public function estimatedDays(): string
    {
        return match($this) {
            self::STANDARD => '3-5 أيام',
            self::EXPRESS => '1-2 يوم',
            self::SAME_DAY => 'نفس اليوم',
            self::PICKUP => 'فوري',
            self::FREE => '5-7 أيام',
        };
    }

    /**
     * Get icon
     */
    public function icon(): string
    {
        return match($this) {
            self::STANDARD => 'truck',
            self::EXPRESS => 'shipping-fast',
            self::SAME_DAY => 'bolt',
            self::PICKUP => 'store',
            self::FREE => 'gift',
        };
    }

    /**
     * Check if requires address
     */
    public function requiresAddress(): bool
    {
        return $this !== self::PICKUP;
    }

    /**
     * Get priority (lower is higher priority)
     */
    public function priority(): int
    {
        return match($this) {
            self::SAME_DAY => 1,
            self::EXPRESS => 2,
            self::STANDARD => 3,
            self::FREE => 4,
            self::PICKUP => 5,
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
