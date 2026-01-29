<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\CatalogItem;
use Illuminate\Support\Collection;

/**
 * CatalogItemDisplayService
 *
 * Unified display and formatting service for CatalogItem.
 * Handles all presentation logic for Web, API, and Mobile.
 */
class CatalogItemDisplayService
{
    public function __construct(
        private CatalogItemMerchantService $merchantService,
        private CatalogItemPricingService $pricingService,
    ) {}

    /**
     * Format single catalog item for display
     */
    public function format(CatalogItem $item, ?int $userId = null): array
    {
        $merchantItem = $this->merchantService->getActiveMerchantItem($item, $userId);

        return [
            'id' => $item->id,
            'part_number' => $item->part_number,
            'name' => $this->getLocalizedName($item),
            'slug' => $item->slug,
            'photo_url' => $this->getPhotoUrl($item),
            'thumbnail_url' => $this->getThumbnailUrl($item),
            'url' => $this->getUrl($item),
            'weight' => $item->weight,
            'attributes' => $item->attributes,
            
            // Pricing
            'pricing' => $this->pricingService->getFormattedPrices($item, $userId),
            
            // Stock & Merchant
            'stock' => $this->merchantService->getStock($item, $userId),
            'is_out_of_stock' => $this->merchantService->isOutOfStock($item, $userId),
            'condition' => $this->merchantService->getItemCondition($item, $userId),
            'minimum_qty' => $this->merchantService->getMinimumQty($item, $userId),
            
            // Merchant info
            'merchant' => $merchantItem ? [
                'id' => $merchantItem->user_id,
                'name' => $merchantItem->user->shop_name ?? $merchantItem->user->name,
                'quality_brand' => $merchantItem->qualityBrand?->name_en,
                'branch' => $merchantItem->merchantBranch?->branch_name,
            ] : null,
        ];
    }

    /**
     * Format collection for list display
     */
    public function formatForList(Collection $items, ?int $userId = null): Collection
    {
        return $items->map(fn($item) => $this->format($item, $userId));
    }

    /**
     * Format for card display (simplified)
     */
    public function formatForCard(CatalogItem $item, ?int $userId = null): array
    {
        return [
            'id' => $item->id,
            'part_number' => $item->part_number,
            'name' => $this->getLocalizedName($item),
            'thumbnail_url' => $this->getThumbnailUrl($item),
            'url' => $this->getUrl($item),
            'price' => $this->pricingService->getFormattedPrice($item, $userId),
            'previous_price' => $this->pricingService->getFormattedPreviousPrice($item, $userId),
            'discount_percentage' => $this->pricingService->getDiscountPercentage($item, $userId),
            'is_out_of_stock' => $this->merchantService->isOutOfStock($item, $userId),
        ];
    }

    /**
     * Format for API response
     */
    public function formatForApi(CatalogItem $item, ?int $userId = null): array
    {
        $data = $this->format($item, $userId);
        
        // API-specific formatting
        $data['pricing']['price'] = $this->pricingService->getApiFormattedPrice($item, $userId);
        $data['pricing']['previous_price'] = $this->pricingService->getApiFormattedPreviousPrice($item, $userId);
        
        return $data;
    }

    /**
     * Get localized name
     */
    public function getLocalizedName(CatalogItem $item): string
    {
        $locale = app()->getLocale();
        
        if ($locale === 'ar' && !empty($item->label_ar)) {
            return $item->label_ar;
        }
        
        if ($locale === 'en' && !empty($item->label_en)) {
            return $item->label_en;
        }
        
        return $item->name ?? $item->part_number;
    }

    /**
     * Get photo URL
     */
    public function getPhotoUrl(CatalogItem $item): string
    {
        if ($item->photo) {
            return asset('storage/' . $item->photo);
        }
        
        return asset('images/default-part.png');
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrl(CatalogItem $item): string
    {
        if ($item->thumbnail) {
            return asset('storage/' . $item->thumbnail);
        }
        
        if ($item->photo) {
            return asset('storage/' . $item->photo);
        }
        
        return asset('images/default-part-thumb.png');
    }

    /**
     * Get catalog item URL
     */
    public function getUrl(CatalogItem $item): string
    {
        if ($item->part_number) {
            return route('front.part-result', $item->part_number);
        }
        
        return '#';
    }

    /**
     * Show truncated name for display
     */
    public function getShortName(CatalogItem $item, int $maxLength = 50): string
    {
        $name = $this->getLocalizedName($item);
        
        if (mb_strlen($name, 'UTF-8') > $maxLength) {
            return mb_substr($name, 0, $maxLength, 'UTF-8') . '...';
        }
        
        return $name;
    }

    /**
     * Format price for display
     */
    public function formatPrice(CatalogItem $item, float $price): string
    {
        return CatalogItem::convertPrice($price);
    }

    /**
     * Get merchant badge/link for admin UI
     */
    public function getMerchantBadge(CatalogItem $item): string
    {
        $merchantItem = $this->merchantService->getActiveMerchantItem($item);
        
        if (!$merchantItem) {
            return '';
        }
        
        $merchantName = $merchantItem->user->shop_name ?? $merchantItem->user->name;
        $url = route('operator-merchant-show', $merchantItem->user_id);
        
        return '<small class="ml-2"> ' . __("MERCHANT") . ': <a href="' . $url . '" target="_blank">' . $merchantName . '</a></small>';
    }
}
