<?php

namespace App\Domain\Commerce\Services;

/**
 * PriceFormatterService - Centralized price formatting service
 *
 * Single source of truth for all price formatting operations.
 * Replaces static methods in CatalogItem model.
 *
 * Domain: Commerce
 * Responsibility: Format prices with currency conversion and localization
 *
 * ARCHITECTURE:
 * - Service Layer Pattern
 * - Single Responsibility Principle
 * - Dependency Injection ready
 */
class PriceFormatterService
{
    /**
     * Format price with currency symbol and conversion
     *
     * @param float|int|null $price Raw price value
     * @return string Formatted price with currency symbol
     */
    public function format($price): string
    {
        return monetaryUnit()->convertAndFormat((float) ($price ?? 0));
    }

    /**
     * Format price without currency symbol (numeric only)
     *
     * @param float|int|null $price Raw price value
     * @return float Converted price without formatting
     */
    public function convert($price): float
    {
        return monetaryUnit()->convert((float) ($price ?? 0));
    }

    /**
     * Format price with custom decimal places
     *
     * @param float|int|null $price Raw price value
     * @param int $decimals Number of decimal places
     * @return string Formatted price
     */
    public function formatWithDecimals($price, int $decimals = 2): string
    {
        $converted = $this->convert($price);
        $currency = monetaryUnit()->getCurrent();
        
        return $currency->sign . ' ' . number_format($converted, $decimals);
    }

    /**
     * Format multiple prices at once
     *
     * @param array $prices Array of prices
     * @return array Array of formatted prices
     */
    public function formatBatch(array $prices): array
    {
        return array_map(fn($price) => $this->format($price), $prices);
    }

    /**
     * Check if price is zero or null
     *
     * @param float|int|null $price
     * @return bool
     */
    public function isZero($price): bool
    {
        return empty($price) || (float) $price === 0.0;
    }

    /**
     * Format price range (e.g., "$10 - $20")
     *
     * @param float|int|null $minPrice
     * @param float|int|null $maxPrice
     * @return string
     */
    public function formatRange($minPrice, $maxPrice): string
    {
        if ($this->isZero($minPrice) && $this->isZero($maxPrice)) {
            return __('No price');
        }

        if ($minPrice === $maxPrice) {
            return $this->format($minPrice);
        }

        return $this->format($minPrice) . ' - ' . $this->format($maxPrice);
    }

    /**
     * Calculate and format discount percentage
     *
     * @param float|int $originalPrice
     * @param float|int $discountedPrice
     * @return string Formatted discount (e.g., "20%")
     */
    public function formatDiscountPercentage($originalPrice, $discountedPrice): string
    {
        if ($this->isZero($originalPrice) || $discountedPrice >= $originalPrice) {
            return '0%';
        }

        $percentage = (($originalPrice - $discountedPrice) / $originalPrice) * 100;
        return round($percentage) . '%';
    }

    /**
     * Format price with "Free" text if zero
     *
     * @param float|int|null $price
     * @return string
     */
    public function formatOrFree($price): string
    {
        return $this->isZero($price) ? __('Free') : $this->format($price);
    }

    /**
     * Get current currency symbol
     *
     * @return string
     */
    public function getCurrencySymbol(): string
    {
        return monetaryUnit()->getCurrent()->sign ?? '';
    }

    /**
     * Get current currency code
     *
     * @return string
     */
    public function getCurrencyCode(): string
    {
        return monetaryUnit()->getCurrent()->name ?? '';
    }

    /**
     * Calculate final price with merchant commission
     *
     * @param float $basePrice Base price before commission
     * @param object|null $commission MerchantCommission object
     * @return float Final price with commission applied
     */
    public function calculateFinalPriceWithCommission(float $basePrice, $commission = null): float
    {
        if ($basePrice <= 0) {
            return 0.0;
        }

        $final = $basePrice;

        if ($commission && $commission->is_active) {
            $fixed = (float) ($commission->fixed_commission ?? 0);
            $percent = (float) ($commission->percentage_commission ?? 0);

            if ($fixed > 0) {
                $final += $fixed;
            }

            if ($percent > 0) {
                $final += $basePrice * ($percent / 100);
            }
        }

        return round($final, 2);
    }

    /**
     * Calculate discount percentage between two prices
     *
     * @param float $currentPrice Current/sale price
     * @param float $previousPrice Original/previous price
     * @return float Discount percentage (0.00 if no discount)
     */
    public function calculateDiscountPercentage(float $currentPrice, float $previousPrice): float
    {
        if ($previousPrice <= 0 || $currentPrice >= $previousPrice) {
            return 0.0;
        }

        return round((($previousPrice - $currentPrice) / $previousPrice) * 100, 2);
    }
}
