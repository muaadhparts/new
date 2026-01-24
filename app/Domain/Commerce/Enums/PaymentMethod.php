<?php

namespace App\Domain\Commerce\Enums;

/**
 * Payment Method Enum
 *
 * Represents available payment methods.
 */
enum PaymentMethod: string
{
    case CASH_ON_DELIVERY = 'cod';
    case CREDIT_CARD = 'credit_card';
    case BANK_TRANSFER = 'bank_transfer';
    case MADA = 'mada';
    case APPLE_PAY = 'apple_pay';
    case STC_PAY = 'stc_pay';
    case TABBY = 'tabby';
    case TAMARA = 'tamara';

    /**
     * Get Arabic label
     */
    public function label(): string
    {
        return match($this) {
            self::CASH_ON_DELIVERY => 'الدفع عند الاستلام',
            self::CREDIT_CARD => 'بطاقة ائتمان',
            self::BANK_TRANSFER => 'تحويل بنكي',
            self::MADA => 'مدى',
            self::APPLE_PAY => 'Apple Pay',
            self::STC_PAY => 'STC Pay',
            self::TABBY => 'تابي',
            self::TAMARA => 'تمارا',
        };
    }

    /**
     * Get icon
     */
    public function icon(): string
    {
        return match($this) {
            self::CASH_ON_DELIVERY => 'money-bill',
            self::CREDIT_CARD => 'credit-card',
            self::BANK_TRANSFER => 'university',
            self::MADA => 'credit-card',
            self::APPLE_PAY => 'apple',
            self::STC_PAY => 'mobile',
            self::TABBY => 'calendar-alt',
            self::TAMARA => 'calendar-alt',
        };
    }

    /**
     * Check if requires online processing
     */
    public function requiresOnlineProcessing(): bool
    {
        return !in_array($this, [self::CASH_ON_DELIVERY, self::BANK_TRANSFER]);
    }

    /**
     * Check if is installment payment
     */
    public function isInstallment(): bool
    {
        return in_array($this, [self::TABBY, self::TAMARA]);
    }

    /**
     * Check if is cash based
     */
    public function isCashBased(): bool
    {
        return $this === self::CASH_ON_DELIVERY;
    }

    /**
     * Get all values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get online payment methods
     */
    public static function onlineMethods(): array
    {
        return array_filter(self::cases(), fn($m) => $m->requiresOnlineProcessing());
    }
}
