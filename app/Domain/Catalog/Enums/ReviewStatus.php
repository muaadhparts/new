<?php

namespace App\Domain\Catalog\Enums;

/**
 * Review Status Enum
 *
 * Represents product review moderation status.
 */
enum ReviewStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case FLAGGED = 'flagged';

    /**
     * Get Arabic label
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'قيد المراجعة',
            self::APPROVED => 'معتمد',
            self::REJECTED => 'مرفوض',
            self::FLAGGED => 'مبلغ عنه',
        };
    }

    /**
     * Get CSS color class
     */
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::FLAGGED => 'info',
        };
    }

    /**
     * Check if visible to public
     */
    public function isVisible(): bool
    {
        return $this === self::APPROVED;
    }

    /**
     * Check if can be moderated
     */
    public function canBeModerated(): bool
    {
        return in_array($this, [self::PENDING, self::FLAGGED]);
    }

    /**
     * Get all values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
