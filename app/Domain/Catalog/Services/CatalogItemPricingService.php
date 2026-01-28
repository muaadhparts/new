<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Merchant\Models\MerchantCommission;

/**
 * CatalogItemPricingService
 *
 * Unified pricing service for CatalogItem.
 * Handles all price calculations, commissions, currency conversion, and discounts.
 */
class CatalogItemPricingService
{
    public function __construct(
        private CatalogItemMerchantService $merchantService,
    ) {}

    /**
     * Get base price (merchant price without commission)
     */
    public function getBasePrice(CatalogItem $item, ?int $userId = null): ?float
    {
        $merchantItem = $this->merchantService->getActiveMerchantItem($item, $userId);
        
        if (!$merchantItem) {
            return null;
        }

        return (float) $merchantItem->price;
    }

    /**
     * Get price with commission
     */
    public function getPriceWithCommission(CatalogItem $item, ?int $userId = null): ?float
    {
        $basePrice = $this->getBasePrice($item, $userId);
        
        if ($basePrice === null) {
            return null;
        }

        $merchantItem = $this->merchantService->getActiveMerchantItem($item, $userId);
        $commission = $this->getCommissionFor($merchantItem->user_id);

        if (!$commission || !$commission->is_active) {
            return $basePrice;
        }

        $price = $basePrice;
        $price += (float) ($commission->fixed_commission ?? 0);
        $price += $basePrice * ((float) ($commission->percentage_commission ?? 0) / 100);

        return $price;
    }

    /**
     * Get previous price with commission
     */
    public function getPreviousPrice(CatalogItem $item, ?int $userId = null): ?float
    {
        $merchantItem = $this->merchantService->getActiveMerchantItem($item, $userId);
        
        if (!$merchantItem || !$merchantItem->previous_price) {
            return null;
        }

        $price = (float) $merchantItem->previous_price;
        $commission = $this->getCommissionFor($merchantItem->user_id);

        if ($commission && $commission->is_active) {
            $price += (float) ($commission->fixed_commission ?? 0);
            $price += $price * ((float) ($commission->percentage_commission ?? 0) / 100);
        }

        return $price;
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentage(CatalogItem $item, ?int $userId = null): float
    {
        $currentPrice = $this->getPriceWithCommission($item, $userId);
        $previousPrice = $this->getPreviousPrice($item, $userId);

        if (!$currentPrice || !$previousPrice || $previousPrice <= 0) {
            return 0;
        }

        $percentage = (($previousPrice - $currentPrice) * 100) / $previousPrice;
        return round($percentage, 2);
    }

    /**
     * Get formatted price (with currency conversion)
     */
    public function getFormattedPrice(CatalogItem $item, ?int $userId = null): string
    {
        $price = $this->getPriceWithCommission($item, $userId);
        
        if ($price === null) {
            return '0';
        }

        return monetaryUnit()->convertAndFormat($price);
    }

    /**
     * Get formatted previous price
     */
    public function getFormattedPreviousPrice(CatalogItem $item, ?int $userId = null): string
    {
        $price = $this->getPreviousPrice($item, $userId);
        
        if ($price === null) {
            return '0';
        }

        return monetaryUnit()->convertAndFormat($price);
    }

    /**
     * Get admin formatted price (base currency, no conversion)
     */
    public function getAdminFormattedPrice(CatalogItem $item, ?int $userId = null): string
    {
        $price = $this->getPriceWithCommission($item, $userId);
        
        if ($price === null) {
            return '0';
        }

        return monetaryUnit()->formatBase($price);
    }

    /**
     * Get API formatted price (converted but not formatted)
     */
    public function getApiFormattedPrice(CatalogItem $item, ?int $userId = null): float
    {
        $price = $this->getPriceWithCommission($item, $userId);
        
        if ($price === null) {
            return 0;
        }

        $converted = monetaryUnit()->convert($price);
        return \PriceHelper::apishowPrice($converted);
    }

    /**
     * Get API formatted previous price
     */
    public function getApiFormattedPreviousPrice(CatalogItem $item, ?int $userId = null): float
    {
        $price = $this->getPreviousPrice($item, $userId);
        
        if ($price === null) {
            return 0;
        }

        $converted = monetaryUnit()->convert($price);
        return \PriceHelper::apishowPrice($converted);
    }

    /**
     * Get all formatted prices
     */
    public function getFormattedPrices(CatalogItem $item, ?int $userId = null): array
    {
        return [
            'base_price' => $this->getBasePrice($item, $userId),
            'final_price' => $this->getPriceWithCommission($item, $userId),
            'previous_price' => $this->getPreviousPrice($item, $userId),
            'discount_percentage' => $this->getDiscountPercentage($item, $userId),
            'formatted_price' => $this->getFormattedPrice($item, $userId),
            'formatted_previous_price' => $this->getFormattedPreviousPrice($item, $userId),
            'has_discount' => $this->getPreviousPrice($item, $userId) !== null,
        ];
    }

    /**
     * Get lowest price for catalog item (across all merchants)
     */
    public function getLowestPrice(CatalogItem $item): ?float
    {
        $bestMerchant = $this->merchantService->getBestMerchantItem($item);
        
        if (!$bestMerchant) {
            return null;
        }

        // Create temporary catalog item with best merchant
        $tempItem = clone $item;
        $tempItem->setRelation('merchantItems', collect([$bestMerchant]));

        return $this->getPriceWithCommission($tempItem, $bestMerchant->user_id);
    }

    /**
     * Convert price to current currency
     */
    public function convertPrice(float $price): string
    {
        return monetaryUnit()->convertAndFormat($price);
    }

    /**
     * Convert price without formatting
     */
    public function convertPriceWithoutFormat(float $price): float
    {
        return monetaryUnit()->convert($price);
    }

    /**
     * Get commission for a merchant
     */
    protected function getCommissionFor(?int $userId): ?MerchantCommission
    {
        if (!$userId) {
            return null;
        }

        return cache()->remember(
            "merchant_commission_{$userId}",
            now()->addHours(1),
            fn () => MerchantCommission::where('user_id', $userId)->first()
        );
    }
}
