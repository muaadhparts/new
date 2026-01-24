<?php

namespace App\Domain\Identity\Enums;

/**
 * Verification Status Enum
 *
 * Represents verification status for email/phone.
 */
enum VerificationStatus: string
{
    case UNVERIFIED = 'unverified';
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case EXPIRED = 'expired';

    /**
     * Get Arabic label
     */
    public function label(): string
    {
        return match($this) {
            self::UNVERIFIED => 'غير موثق',
            self::PENDING => 'في انتظار التوثيق',
            self::VERIFIED => 'موثق',
            self::EXPIRED => 'منتهي الصلاحية',
        };
    }

    /**
     * Get CSS color class
     */
    public function color(): string
    {
        return match($this) {
            self::UNVERIFIED => 'danger',
            self::PENDING => 'warning',
            self::VERIFIED => 'success',
            self::EXPIRED => 'secondary',
        };
    }

    /**
     * Check if verification is complete
     */
    public function isVerified(): bool
    {
        return $this === self::VERIFIED;
    }

    /**
     * Check if can resend verification
     */
    public function canResend(): bool
    {
        return in_array($this, [self::UNVERIFIED, self::PENDING, self::EXPIRED]);
    }

    /**
     * Get all values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
