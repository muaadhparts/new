<?php

if (!function_exists('formatPrice')) {
    /**
     * Format price using PriceFormatterService
     * 
     * @param float|int|null $price
     * @return string
     */
    function formatPrice($price): string
    {
        if ($price === null) {
            return '';
        }
        
        return app(\App\Domain\Commerce\Services\PriceFormatterService::class)->format($price);
    }
}
