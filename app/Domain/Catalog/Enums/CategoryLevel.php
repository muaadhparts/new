<?php

namespace App\Domain\Catalog\Enums;

/**
 * Category Level Enum
 *
 * Represents category hierarchy levels.
 */
enum CategoryLevel: int
{
    case ROOT = 0;
    case MAIN = 1;
    case SUB = 2;
    case CHILD = 3;

    /**
     * Get Arabic label
     */
    public function label(): string
    {
        return match($this) {
            self::ROOT => 'الجذر',
            self::MAIN => 'التصنيف الرئيسي',
            self::SUB => 'التصنيف الفرعي',
            self::CHILD => 'التصنيف النهائي',
        };
    }

    /**
     * Get depth for indentation
     */
    public function depth(): int
    {
        return $this->value;
    }

    /**
     * Check if can have children
     */
    public function canHaveChildren(): bool
    {
        return $this !== self::CHILD;
    }

    /**
     * Get next level
     */
    public function nextLevel(): ?self
    {
        return match($this) {
            self::ROOT => self::MAIN,
            self::MAIN => self::SUB,
            self::SUB => self::CHILD,
            self::CHILD => null,
        };
    }

    /**
     * Get parent level
     */
    public function parentLevel(): ?self
    {
        return match($this) {
            self::ROOT => null,
            self::MAIN => self::ROOT,
            self::SUB => self::MAIN,
            self::CHILD => self::SUB,
        };
    }

    /**
     * Get all values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
