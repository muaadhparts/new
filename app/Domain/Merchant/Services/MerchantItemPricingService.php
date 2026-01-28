<?php

namespace App\Domain\Merchant\Services;

use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Merchant\Models\MerchantCommission;
use Illuminate\Support\Facades\Cache;

/**
 * MerchantItemPricingService - Centralized pricing logic for merchant items
 *
 * Handles all price calculations including commissions and discounts.
 * Single source of truth for pricing across Web/API/Mobile.
 */
class MerchantItemPricingService
{
    /**
     * Get base price (without commission)
     */
    public function getBasePrice(MerchantItem $item): float
    {
        return (float) $item->price;
    }

    /**
     * Get merchant commission for this item
     */
    public function getMerchantCommission(MerchantItem $item): ?MerchantCommission
    {
        if (!$item->user_id) {
            return null;
        }

        return Cache::remember(
            "merchant_commission_{$item->user_id}",
            3600,
            fn() => MerchantCommission::where('user_id', $item->user_id)
                ->where('is_active', true)
                ->first()
        );
    }

    /**
     * Calculate commission amount
     */
    public function calculateCommission(float $basePrice, ?MerchantCommission $commission): float
    {
        if (!$commission || !$commission->is_active) {
            return 0;
        }

        $commissionAmount = 0;

        // Fixed commission
        if ($commission->fixed_commission > 0) {
            $commissionAmount += (float) $commission->fixed_commission;
        }

        // Percentage commission
        if ($commission->percentage_commission > 0) {
            $commissionAmount += $basePrice * ((float) $commission->percentage_commission / 100);
        }

        return round($commissionAmount, 2);
    }

    /**
     * Get price with commission applied
     */
    public function getPriceWithCommission(MerchantItem $item): float
    {
        $basePrice = $this->getBasePrice($item);
        $commission = $this->getMerchantCommission($item);
        $commissionAmount = $this->calculateCommission($basePrice, $commission);

        return round($basePrice + $commissionAmount, 2);
    }

    /**
     * Get formatted price with commission
     */
    public function getFormattedPrice(MerchantItem $item): string
    {
        $price = $this->getPriceWithCommission($item);
        return monetaryUnit()->format($price);
    }

    /**
     * Get previous price with commission
     */
    public function getPreviousPriceWithCommission(MerchantItem $item): ?float
    {
        if (!$item->previous_price || $item->previous_price <= 0) {
            return null;
        }

        $basePrice = (float) $item->previous_price;
        $commission = $this->getMerchantCommission($item);
        $commissionAmount = $this->calculateCommission($basePrice, $commission);

        return round($basePrice + $commissionAmount, 2);
    }

    /**
     * Get formatted previous price
     */
    public function getFormattedPreviousPrice(MerchantItem $item): ?string
    {
        $price = $this->getPreviousPriceWithCommission($item);
        
        if ($price === null) {
            return null;
        }

        return monetaryUnit()->format($price);
    }

    /**
     * Calculate discount percentage
     */
    public function getDiscountPercentage(MerchantItem $item): float
    {
        $currentPrice = $this->getPriceWithCommission($item);
        $previousPrice = $this->getPreviousPriceWithCommission($item);

        if ($previousPrice === null || $previousPrice <= 0 || $currentPrice >= $previousPrice) {
            return 0;
        }

        $percentage = (($previousPrice - $currentPrice) / $previousPrice) * 100;
        return round($percentage, 2);
    }

    /**
     * Check if item has discount
     */
    public function hasDiscount(MerchantItem $item): bool
    {
        return $this->getDiscountPercentage($item) > 0;
    }

    /**
     * Get discount amount
     */
    public function getDiscountAmount(MerchantItem $item): float
    {
        $currentPrice = $this->getPriceWithCommission($item);
        $previousPrice = $this->getPreviousPriceWithCommission($item);

        if ($previousPrice === null || $currentPrice >= $previousPrice) {
            return 0;
        }

        return round($previousPrice - $currentPrice, 2);
    }

    /**
     * Get pricing summary (complete pricing data)
     */
    public function getPricingSummary(MerchantItem $item): array
    {
        $basePrice = $this->getBasePrice($item);
        $commission = $this->getMerchantCommission($item);
        $commissionAmount = $this->calculateCommission($basePrice, $commission);
        $finalPrice = $this->getPriceWithCommission($item);
        $previousPrice = $this->getPreviousPriceWithCommission($item);
        $discountPercentage = $this->getDiscountPercentage($item);

        return [
            'base_price' => $basePrice,
            'base_price_formatted' => monetaryUnit()->format($basePrice),
            
            'commission_amount' => $commissionAmount,
            'commission_amount_formatted' => monetaryUnit()->format($commissionAmount),
            
            'final_price' => $finalPrice,
            'final_price_formatted' => monetaryUnit()->format($finalPrice),
            
            'previous_price' => $previousPrice,
            'previous_price_formatted' => $previousPrice ? monetaryUnit()->format($previousPrice) : null,
            
            'has_discount' => $this->hasDiscount($item),
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $this->getDiscountAmount($item),
            'discount_amount_formatted' => monetaryUnit()->format($this->getDiscountAmount($item)),
        ];
    }

    /**
     * Get wholesale price if applicable
     */
    public function getWholesalePrice(MerchantItem $item, int $quantity): ?array
    {
        if (!$item->whole_sell_qty || $quantity < $item->whole_sell_qty) {
            return null;
        }

        $basePrice = $this->getPriceWithCommission($item);
        $discount = (float) ($item->whole_sell_discount ?? 0);
        
        if ($discount <= 0) {
            return null;
        }

        $wholesalePrice = $basePrice - ($basePrice * ($discount / 100));

        return [
            'min_quantity' => $item->whole_sell_qty,
            'discount_percentage' => $discount,
            'unit_price' => round($wholesalePrice, 2),
            'unit_price_formatted' => monetaryUnit()->format($wholesalePrice),
            'total_price' => round($wholesalePrice * $quantity, 2),
            'total_price_formatted' => monetaryUnit()->format($wholesalePrice * $quantity),
            'savings' => round(($basePrice - $wholesalePrice) * $quantity, 2),
            'savings_formatted' => monetaryUnit()->format(($basePrice - $wholesalePrice) * $quantity),
        ];
    }
}
