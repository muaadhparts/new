<?php

namespace App\Domain\Platform\Enums;

/**
 * Currency Enum
 *
 * Represents supported currencies.
 */
enum Currency: string
{
    case SAR = 'SAR';
    case USD = 'USD';
    case AED = 'AED';
    case KWD = 'KWD';
    case BHD = 'BHD';
    case QAR = 'QAR';
    case OMR = 'OMR';

    /**
     * Get Arabic name
     */
    public function name(): string
    {
        return match($this) {
            self::SAR => 'ريال سعودي',
            self::USD => 'دولار أمريكي',
            self::AED => 'درهم إماراتي',
            self::KWD => 'دينار كويتي',
            self::BHD => 'دينار بحريني',
            self::QAR => 'ريال قطري',
            self::OMR => 'ريال عماني',
        };
    }

    /**
     * Get symbol
     */
    public function symbol(): string
    {
        return match($this) {
            self::SAR => 'ر.س',
            self::USD => '$',
            self::AED => 'د.إ',
            self::KWD => 'د.ك',
            self::BHD => 'د.ب',
            self::QAR => 'ر.ق',
            self::OMR => 'ر.ع',
        };
    }

    /**
     * Get decimal places
     */
    public function decimals(): int
    {
        return match($this) {
            self::KWD, self::BHD, self::OMR => 3,
            default => 2,
        };
    }

    /**
     * Get exchange rate to SAR
     */
    public function toSarRate(): float
    {
        return match($this) {
            self::SAR => 1.0,
            self::USD => 3.75,
            self::AED => 1.02,
            self::KWD => 12.23,
            self::BHD => 9.95,
            self::QAR => 1.03,
            self::OMR => 9.74,
        };
    }

    /**
     * Format amount
     */
    public function format(float $amount): string
    {
        return number_format($amount, $this->decimals()) . ' ' . $this->symbol();
    }

    /**
     * Get default currency
     */
    public static function default(): self
    {
        return self::SAR;
    }

    /**
     * Get all values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
