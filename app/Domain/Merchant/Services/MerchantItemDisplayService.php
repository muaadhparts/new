<?php

namespace App\Domain\Merchant\Services;

use App\Domain\Merchant\Models\MerchantItem;
use Illuminate\Support\Collection;

/**
 * MerchantItemDisplayService - Centralized formatting for merchant item display
 *
 * Single source of truth for displaying merchant items across Web/API/Mobile.
 * DATA FLOW POLICY: Controller → Service → DTO → View/API
 */
class MerchantItemDisplayService
{
    public function __construct(
        private MerchantItemPricingService $pricingService,
        private MerchantItemStockService $stockService,
    ) {}

    /**
     * Format merchant item for display
     */
    public function format(MerchantItem $item): array
    {
        $pricing = $this->pricingService->getPricingSummary($item);

        return [
            'id' => $item->id,
            'catalog_item_id' => $item->catalog_item_id,
            'merchant_id' => $item->user_id,
            'branch_id' => $item->merchant_branch_id,
            'quality_brand_id' => $item->quality_brand_id,
            
            // Type & Condition
            'item_type' => $item->item_type,
            'is_affiliate' => $item->item_type === 'affiliate',
            'affiliate_link' => $item->affiliate_link,
            'condition' => $item->item_condition,
            'condition_label' => $this->getConditionLabel($item),
            
            // Stock
            'stock' => $item->stock,
            'is_in_stock' => $item->stock > 0 || $item->preordered,
            'is_preordered' => $item->preordered,
            'minimum_qty' => $item->minimum_qty ?? 1,
            'whole_sell_qty' => $item->whole_sell_qty,
            'whole_sell_discount' => $item->whole_sell_discount,
            'stock_status_label' => $this->stockService->getStockStatusLabel($item),
            'stock_status_color' => $this->stockService->getStockStatusColor($item),
            
            // Status
            'status' => $item->status,
            'is_active' => $item->status === 1,
            'is_available' => $this->stockService->isAvailable($item),
            
            // Pricing (from PricingService)
            'pricing' => $pricing,
            
            // Photos
            'primary_photo_url' => $this->getPrimaryPhotoUrl($item),
            'photos_count' => $item->relationLoaded('photos') ? $item->photos->count() : 0,
            
            // Reviews
            'average_rating' => $this->getAverageRating($item),
            'reviews_count' => $this->getReviewsCount($item),
            
            // Details
            'details' => $item->details,
            'policy' => $item->policy,
            'ship' => $item->ship,
            
            // Timestamps
            'created_at' => $item->created_at?->toISOString(),
            'updated_at' => $item->updated_at?->toISOString(),
            
            // Relations (if loaded)
            'catalog_item' => $item->relationLoaded('catalogItem') && $item->catalogItem
                ? $this->formatCatalogItem($item->catalogItem) 
                : null,
            'merchant' => $item->relationLoaded('user') && $item->user
                ? $this->formatMerchant($item->user) 
                : null,
            'branch' => $item->relationLoaded('branch') && $item->branch
                ? $this->formatBranch($item->branch) 
                : null,
            'quality_brand' => $item->relationLoaded('qualityBrand') && $item->qualityBrand
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
    public function getPrimaryPhotoUrl(MerchantItem $item): ?string
    {
        // Try primary photo
        if ($item->relationLoaded('primaryPhoto') && $item->primaryPhoto) {
            return asset('assets/images/products/' . $item->primaryPhoto->photo);
        }

        // Try first photo
        if ($item->relationLoaded('photos') && $item->photos->isNotEmpty()) {
            return asset('assets/images/products/' . $item->photos->first()->photo);
        }

        // Fallback to catalog item photo
        if ($item->relationLoaded('catalogItem') && $item->catalogItem?->photo) {
            return asset('assets/images/products/' . $item->catalogItem->photo);
        }

        return null;
    }

    /**
     * Get average rating
     */
    public function getAverageRating(MerchantItem $item): float
    {
        if ($item->relationLoaded('reviews')) {
            return round($item->reviews->avg('rating') ?? 0, 1);
        }
        return 0;
    }

    /**
     * Get reviews count
     */
    public function getReviewsCount(MerchantItem $item): int
    {
        if ($item->relationLoaded('reviews')) {
            return $item->reviews->count();
        }
        return 0;
    }

    /**
     * Format catalog item (simplified)
     */
    private function formatCatalogItem($catalogItem): ?array
    {
        if (!$catalogItem) return null;

        return [
            'id' => $catalogItem->id,
            'name' => $catalogItem->name,
            'part_number' => $catalogItem->part_number,
            'slug' => $catalogItem->slug,
            'photo_url' => $catalogItem->photo 
                ? asset('assets/images/products/' . $catalogItem->photo)
                : null,
        ];
    }

    /**
     * Format merchant (simplified)
     */
    private function formatMerchant($merchant): ?array
    {
        if (!$merchant) return null;

        return [
            'id' => $merchant->id,
            'shop_name' => $merchant->shop_name,
            'is_approved' => $merchant->is_merchant === 2,
            'logo_url' => $merchant->logo 
                ? asset('assets/images/merchants/' . $merchant->logo)
                : null,
        ];
    }

    /**
     * Format branch (simplified)
     */
    private function formatBranch($branch): ?array
    {
        if (!$branch) return null;

        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'city' => $branch->city?->name,
        ];
    }

    /**
     * Format quality brand (simplified)
     */
    private function formatQualityBrand($qualityBrand): ?array
    {
        if (!$qualityBrand) return null;

        return [
            'id' => $qualityBrand->id,
            'name' => $qualityBrand->name,
        ];
    }

    /**
     * Format collection of merchant items
     */
    public function formatCollection($items): array
    {
        if ($items instanceof Collection) {
            return $items->map(fn($item) => $this->format($item))->toArray();
        }

        // For paginated results
        return [
            'data' => collect($items->items())->map(fn($item) => $this->format($item))->toArray(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ],
        ];
    }

    /**
     * Format price for display
     */
    public function formatPrice(MerchantItem $item): string
    {
        return $this->pricingService->getFormattedPrice($item);
    }

    /**
     * Format for cart display (minimal data)
     */
    public function formatForCart(MerchantItem $item): array
    {
        return [
            'id' => $item->id,
            'catalog_item_id' => $item->catalog_item_id,
            'name' => $item->catalogItem?->name ?? 'N/A',
            'part_number' => $item->catalogItem?->part_number ?? 'N/A',
            'price' => $this->pricingService->getPriceWithCommission($item),
            'price_formatted' => $this->pricingService->getFormattedPrice($item),
            'photo_url' => $this->getPrimaryPhotoUrl($item),
            'is_available' => $this->stockService->isAvailable($item),
            'stock' => $item->stock,
            'merchant_name' => $item->user?->shop_name ?? 'N/A',
        ];
    }
}
