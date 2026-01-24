<?php

namespace App\Domain\Merchant\Enums;

/**
 * Stock Status Enum
 *
 * Represents stock availability status.
 */
enum StockStatus: string
{
    case IN_STOCK = 'in_stock';
    case LOW_STOCK = 'low_stock';
    case OUT_OF_STOCK = 'out_of_stock';
    case PREORDER = 'preorder';
    case DISCONTINUED = 'discontinued';

    /**
     * Get Arabic label
     */
    public function label(): string
    {
        return match($this) {
            self::IN_STOCK => 'متوفر',
            self::LOW_STOCK => 'كمية محدودة',
            self::OUT_OF_STOCK => 'غير متوفر',
            self::PREORDER => 'طلب مسبق',
            self::DISCONTINUED => 'توقف الإنتاج',
        };
    }

    /**
     * Get CSS color class
     */
    public function color(): string
    {
        return match($this) {
            self::IN_STOCK => 'success',
            self::LOW_STOCK => 'warning',
            self::OUT_OF_STOCK => 'danger',
            self::PREORDER => 'info',
            self::DISCONTINUED => 'secondary',
        };
    }

    /**
     * Check if purchasable
     */
    public function isPurchasable(): bool
    {
        return in_array($this, [self::IN_STOCK, self::LOW_STOCK, self::PREORDER]);
    }

    /**
     * Check if should show warning
     */
    public function showWarning(): bool
    {
        return in_array($this, [self::LOW_STOCK, self::PREORDER]);
    }

    /**
     * Create from quantity
     */
    public static function fromQuantity(int $quantity, int $lowThreshold = 5): self
    {
        if ($quantity <= 0) {
            return self::OUT_OF_STOCK;
        }

        if ($quantity <= $lowThreshold) {
            return self::LOW_STOCK;
        }

        return self::IN_STOCK;
    }

    /**
     * Get all values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
