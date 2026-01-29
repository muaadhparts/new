<?php

namespace App\Domain\Merchant\Services;

use App\Domain\Merchant\Models\MerchantItem;
use Illuminate\Support\Collection;

/**
 * MerchantItemDisplayService - Centralized formatting for merchant item display
 *
 * Single source of truth for displaying merchant items across Web/API/Mobile.
 * DATA FLOW POLICY: Controller → Service → DTO → View/API
 *
 * NAMING CONVENTION:
 * - Use clear, descriptive names
 * - NO aliases or duplicate keys
 * - Consistent naming across all methods
 */
class MerchantItemDisplayService
{
    public function __construct(
        private MerchantItemPricingService $pricingService,
        private MerchantItemStockService $stockService,
    ) {}

    /**
     * Format merchant item for dashboard display
     * Returns simplified data optimized for dashboard tables
     */
    public function formatForDashboard(MerchantItem $item): array
    {
        return [
            'id' => $item->id,
            'partNumber' => $item->catalogItem?->part_number ?? __('N/A'),
            'name' => $item->catalogItem?->name ?? __('Unknown'),
            'brandName' => $item->catalogItem?->brand?->name ?? __('N/A'),
            'qualityBrandName' => $item->qualityBrand?->name ?? __('N/A'),
            'branchName' => $item->branch?->name ?? __('Main Branch'),
            'price' => $item->merchantSizePrice(),
            'photoUrl' => $this->getPrimaryPhotoUrl($item),
            'viewUrl' => route('merchant-item-edit', $item->id),
        ];
    }

    /**
     * Format merchant item for full display (details page, API)
     */
    public function format(MerchantItem $item): array
    {
        $pricing = $this->pricingService->getPricingSummary($item);

        return [
            'id' => $item->id,
            'catalogItemId' => $item->catalog_item_id,
            'merchantId' => $item->user_id,
            'branchId' => $item->merchant_branch_id,
            'qualityBrandId' => $item->quality_brand_id,
            
            // Type & Condition
            'itemType' => $item->item_type,
            'isAffiliate' => $item->item_type === 'affiliate',
            'affiliateLink' => $item->affiliate_link,
            'condition' => $item->item_condition,
            'conditionLabel' => $this->getConditionLabel($item),
            
            // Stock
            'stock' => $item->stock,
            'isInStock' => $item->stock > 0 || $item->preordered,
            'isPreordered' => $item->preordered,
            'minimumQty' => $item->minimum_qty ?? 1,
            'wholeSellQty' => $item->whole_sell_qty,
            'wholeSellDiscount' => $item->whole_sell_discount,
            'stockStatusLabel' => $this->stockService->getStockStatusLabel($item),
            'stockStatusColor' => $this->stockService->getStockStatusColor($item),
            
            // Status
            'status' => $item->status,
            'isActive' => $item->status === 1,
            'isAvailable' => $this->stockService->isAvailable($item),
            
            // Pricing (from PricingService)
            'pricing' => $pricing,
            
            // Photos
            'primaryPhotoUrl' => $this->getPrimaryPhotoUrl($item),
            'photosCount' => $item->relationLoaded('photos') ? $item->photos->count() : 0,
            
            // Reviews
            'averageRating' => $this->getAverageRating($item),
            'reviewsCount' => $this->getReviewsCount($item),
            
            // Details
            'details' => $item->details,
            'policy' => $item->policy,
            'ship' => $item->ship,
            
            // Timestamps
            'createdAt' => $item->created_at?->toISOString(),
            'updatedAt' => $item->updated_at?->toISOString(),
            
            // Relations (if loaded)
            'catalogItem' => $item->relationLoaded('catalogItem') && $item->catalogItem
                ? $this->formatCatalogItem($item->catalogItem) 
                : null,
            'merchant' => $item->relationLoaded('user') && $item->user
                ? $this->formatMerchant($item->user) 
                : null,
            'branch' => $item->relationLoaded('branch') && $item->branch
                ? $this->formatBranch($item->branch) 
                : null,
            'qualityBrand' => $item->relationLoaded('qualityBrand') && $item->qualityBrand
                ? $this->formatQualityBrand($item->qualityBrand)
                : null,
        ];
    }

    /**
     * Get condition label
     */
    public function getConditionLabel(MerchantItem $item): string
    {
        return match($item->item_condition) {
            1 => __('New'),
            2 => __('Used - Like New'),
            3 => __('Used - Good'),
            4 => __('Used - Acceptable'),
            default => __('Unknown'),
        };
    }

    /**
     * Get primary photo URL
     */
    private function getPrimaryPhotoUrl(MerchantItem $item): string
    {
        // Try merchant item photo first
        if ($item->relationLoaded('photos') && $item->photos->isNotEmpty()) {
            $photo = $item->photos->first()->photo ?? null;
            if ($photo) {
                return asset('assets/images/merchant_items/' . $photo);
            }
        }

        // Fallback to catalog item photo
        if ($item->relationLoaded('catalogItem') && $item->catalogItem) {
            $catalogPhoto = $item->catalogItem->photo ?? null;
            if ($catalogPhoto) {
                return asset('assets/images/catalog_items/' . $catalogPhoto);
            }
        }

        // Default no image
        return asset('assets/images/noimage.png');
    }

    /**
     * Get average rating
     */
    private function getAverageRating(MerchantItem $item): float
    {
        if ($item->relationLoaded('reviews') && $item->reviews->isNotEmpty()) {
            return round($item->reviews->avg('rating'), 1);
        }
        return 0.0;
    }

    /**
     * Get reviews count
     */
    private function getReviewsCount(MerchantItem $item): int
    {
        if ($item->relationLoaded('reviews')) {
            return $item->reviews->count();
        }
        return 0;
    }

    /**
     * Format catalog item (simplified)
     */
    private function formatCatalogItem($catalogItem): array
    {
        return [
            'id' => $catalogItem->id,
            'partNumber' => $catalogItem->part_number,
            'name' => $catalogItem->name,
            'brandName' => $catalogItem->brand?->name ?? __('N/A'),
            'photoUrl' => $catalogItem->photo 
                ? asset('assets/images/catalog_items/' . $catalogItem->photo)
                : asset('assets/images/noimage.png'),
        ];
    }

    /**
     * Format merchant (simplified)
     */
    private function formatMerchant($merchant): array
    {
        return [
            'id' => $merchant->id,
            'name' => $merchant->name,
            'email' => $merchant->email,
            'phone' => $merchant->phone,
        ];
    }

    /**
     * Format branch (simplified)
     */
    private function formatBranch($branch): array
    {
        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'address' => $branch->address,
            'city' => $branch->city?->name ?? __('N/A'),
        ];
    }

    /**
     * Format quality brand (simplified)
     */
    private function formatQualityBrand($qualityBrand): array
    {
        return [
            'id' => $qualityBrand->id,
            'name' => $qualityBrand->name,
        ];
    }

    /**
     * Format collection of items for dashboard
     */
    public function formatCollectionForDashboard(Collection $items): array
    {
        return $items->map(fn($item) => $this->formatForDashboard($item))->toArray();
    }

    /**
     * Format collection of items (full format)
     */
    public function formatCollection(Collection $items): array
    {
        return $items->map(fn($item) => $this->format($item))->toArray();
    }
}
