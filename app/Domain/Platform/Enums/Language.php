<?php

namespace App\Domain\Platform\Enums;

/**
 * Language Enum
 *
 * Represents supported languages.
 */
enum Language: string
{
    case ARABIC = 'ar';
    case ENGLISH = 'en';

    /**
     * Get native name
     */
    public function nativeName(): string
    {
        return match($this) {
            self::ARABIC => 'العربية',
            self::ENGLISH => 'English',
        };
    }

    /**
     * Get direction
     */
    public function direction(): string
    {
        return match($this) {
            self::ARABIC => 'rtl',
            self::ENGLISH => 'ltr',
        };
    }

    /**
     * Check if is RTL
     */
    public function isRtl(): bool
    {
        return $this === self::ARABIC;
    }

    /**
     * Get locale code
     */
    public function locale(): string
    {
        return match($this) {
            self::ARABIC => 'ar_SA',
            self::ENGLISH => 'en_US',
        };
    }

    /**
     * Get flag emoji
     */
    public function flag(): string
    {
        return match($this) {
            self::ARABIC => '🇸🇦',
            self::ENGLISH => '🇺🇸',
        };
    }

    /**
     * Get default language
     */
    public static function default(): self
    {
        return self::ARABIC;
    }

    /**
     * Get all values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
