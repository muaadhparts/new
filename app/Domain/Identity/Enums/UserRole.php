<?php

namespace App\Domain\Identity\Enums;

/**
 * User Role Enum
 *
 * Represents user roles in the system.
 */
enum UserRole: string
{
    case CUSTOMER = 'customer';
    case MERCHANT = 'merchant';
    case COURIER = 'courier';
    case OPERATOR = 'operator';

    /**
     * Get Arabic label
     */
    public function label(): string
    {
        return match($this) {
            self::CUSTOMER => 'عميل',
            self::MERCHANT => 'تاجر',
            self::COURIER => 'مندوب توصيل',
            self::OPERATOR => 'مشغل',
        };
    }

    /**
     * Get guard name
     */
    public function guard(): string
    {
        return match($this) {
            self::CUSTOMER => 'web',
            self::MERCHANT => 'web',
            self::COURIER => 'courier',
            self::OPERATOR => 'admin',
        };
    }

    /**
     * Get dashboard route
     */
    public function dashboardRoute(): string
    {
        return match($this) {
            self::CUSTOMER => 'user.dashboard',
            self::MERCHANT => 'merchant.dashboard',
            self::COURIER => 'courier.dashboard',
            self::OPERATOR => 'operator.dashboard',
        };
    }

    /**
     * Check if can manage orders
     */
    public function canManageOrders(): bool
    {
        return in_array($this, [self::MERCHANT, self::OPERATOR]);
    }

    /**
     * Get all values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
