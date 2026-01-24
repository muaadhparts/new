<?php

namespace App\Domain\Commerce\Enums;

/**
 * Payment Status Enum
 *
 * Represents all possible states of a payment.
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case CANCELLED = 'cancelled';

    /**
     * Get Arabic label
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'في انتظار الدفع',
            self::PROCESSING => 'جاري المعالجة',
            self::COMPLETED => 'مكتمل',
            self::FAILED => 'فشل',
            self::REFUNDED => 'مسترد',
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
            self::PROCESSING => 'info',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::REFUNDED => 'secondary',
            self::CANCELLED => 'dark',
        };
    }

    /**
     * Check if payment is successful
     */
    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Check if payment is final
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::REFUNDED, self::CANCELLED]);
    }

    /**
     * Check if payment can be refunded
     */
    public function canBeRefunded(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Get all values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
