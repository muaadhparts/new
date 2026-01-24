<?php

namespace App\Domain\Accounting\Enums;

/**
 * Withdrawal Status Enum
 *
 * Represents withdrawal request status.
 */
enum WithdrawalStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    /**
     * Get Arabic label
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'قيد الانتظار',
            self::APPROVED => 'موافق عليه',
            self::PROCESSING => 'جاري التحويل',
            self::COMPLETED => 'مكتمل',
            self::REJECTED => 'مرفوض',
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
            self::APPROVED => 'info',
            self::PROCESSING => 'primary',
            self::COMPLETED => 'success',
            self::REJECTED => 'danger',
            self::CANCELLED => 'secondary',
        };
    }

    /**
     * Check if can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this, [self::PENDING, self::APPROVED]);
    }

    /**
     * Check if is final
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::REJECTED, self::CANCELLED]);
    }

    /**
     * Check if is successful
     */
    public function isSuccessful(): bool
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
