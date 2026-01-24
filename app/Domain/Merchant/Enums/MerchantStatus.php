<?php

namespace App\Domain\Merchant\Enums;

/**
 * Merchant Status Enum
 *
 * Represents merchant account status.
 */
enum MerchantStatus: int
{
    case PENDING = 0;
    case ACTIVE = 1;
    case SUSPENDED = 2;
    case BANNED = 3;

    /**
     * Get Arabic label
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'قيد المراجعة',
            self::ACTIVE => 'نشط',
            self::SUSPENDED => 'موقوف',
            self::BANNED => 'محظور',
        };
    }

    /**
     * Get CSS color class
     */
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::ACTIVE => 'success',
            self::SUSPENDED => 'secondary',
            self::BANNED => 'danger',
        };
    }

    /**
     * Check if merchant can sell
     */
    public function canSell(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if merchant can login
     */
    public function canLogin(): bool
    {
        return in_array($this, [self::PENDING, self::ACTIVE, self::SUSPENDED]);
    }

    /**
     * Get all values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get options for select
     */
    public static function options(): array
    {
        return array_map(
            fn($case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }
}
