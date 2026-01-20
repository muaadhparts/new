<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\MerchantItem;
use App\Models\MerchantPhoto;
use Illuminate\Support\Collection;

/**
 * CatalogItemOffersService - Fetch and group merchant offers for a catalog item
 *
 * Groups offers by: Quality Brand → Merchant → Branch
 * Includes merchant-specific photos for each offer
 */
class CatalogItemOffersService
{
    /**
     * Get all offers for a catalog item, grouped by Quality → Merchant → Branch
     *
     * @param int $catalogItemId
     * @param string $sort Sort order: 'price_asc' (default), 'price_desc'
     * @return array{
     *   catalog_item: array,
     *   offers_count: int,
     *   lowest_price: float,
     *   grouped_offers: array,
     *   flat_offers: array,
     *   current_sort: string
     * }
     */
    public function getGroupedOffers(int $catalogItemId, string $sort = 'price_asc'): array
    {
        $catalogItem = CatalogItem::with(['fitments.brand'])->find($catalogItemId);

        if (!$catalogItem) {
            return [
                'catalog_item' => null,
                'offers_count' => 0,
                'lowest_price' => 0,
                'grouped_offers' => [],
                'flat_offers' => [],
                'current_sort' => $sort,
            ];
        }

        // Determine sort direction
        $sortDirection = $sort === 'price_desc' ? 'desc' : 'asc';

        // Fetch all active merchant items with relations
        $merchantItems = MerchantItem::with([
            'qualityBrand',
            'user:id,shop_name,shop_name_ar,photo,is_merchant',
            'merchantBranch:id,branch_name,warehouse_name,location,city_id,latitude,longitude',
            'photos',
        ])
            ->where('catalog_item_id', $catalogItemId)
            ->where('status', 1)
            ->whereHas('user', fn($q) => $q->where('is_merchant', 2)) // Active merchants only
            ->orderBy('price', $sortDirection)
            ->get();

        if ($merchantItems->isEmpty()) {
            return [
                'catalog_item' => $this->formatCatalogItem($catalogItem),
                'offers_count' => 0,
                'lowest_price' => 0,
                'grouped_offers' => [],
                'flat_offers' => [],
                'current_sort' => $sort,
            ];
        }

        // Format flat offers
        $flatOffers = $merchantItems->map(fn($mi) => $this->formatOffer($mi))->values()->toArray();

        // Group by Quality Brand → Merchant → Branch
        $groupedOffers = $this->groupOffers($merchantItems, $sort);

        // Get lowest and highest prices
        $lowestPrice = $merchantItems->min('price') ?? 0;
        $highestPrice = $merchantItems->max('price') ?? 0;

        return [
            'catalog_item' => $this->formatCatalogItem($catalogItem),
            'offers_count' => $merchantItems->count(),
            'lowest_price' => (float) $lowestPrice,
            'lowest_price_formatted' => \App\Models\CatalogItem::convertPrice($lowestPrice),
            'highest_price' => (float) $highestPrice,
            'highest_price_formatted' => \App\Models\CatalogItem::convertPrice($highestPrice),
            'grouped_offers' => $groupedOffers,
            'flat_offers' => $flatOffers,
            'current_sort' => $sort,
        ];
    }

    /**
     * Format catalog item data for API response
     */
    protected function formatCatalogItem(CatalogItem $item): array
    {
        // Get unique vehicle brands from fitments
        $fitmentBrands = [];
        if ($item->relationLoaded('fitments')) {
            $fitmentBrands = $item->fitments
                ->map(fn($f) => $f->brand)
                ->filter()
                ->unique('id')
                ->map(fn($brand) => [
                    'id' => $brand->id,
                    'name' => $brand->localized_name,
                    'logo' => $brand->photo_url,
                    'slug' => $brand->slug,
                ])
                ->values()
                ->toArray();
        }

        // Build photo URL (same logic as CatalogItemCardDTO::resolvePhoto)
        $photoUrl = asset('assets/images/noimage.png');
        if ($item->photo) {
            if (filter_var($item->photo, FILTER_VALIDATE_URL)) {
                $photoUrl = $item->photo;
            } else {
                $photoUrl = \Illuminate\Support\Facades\Storage::url($item->photo);
            }
        }

        return [
            'id' => $item->id,
            'part_number' => $item->part_number,
            'name' => $item->localized_name,
            'slug' => $item->slug,
            'photo' => $photoUrl,
            'thumbnail' => $item->thumbnail ? asset('assets/images/' . $item->thumbnail) : null,
            'fitment_brands' => $fitmentBrands,
            'fitment_count' => count($fitmentBrands),
        ];
    }

    /**
     * Format a single offer (merchant item)
     */
    protected function formatOffer(MerchantItem $mi): array
    {
        $qualityBrand = $mi->qualityBrand;
        $merchant = $mi->user;
        $branch = $mi->merchantBranch;
        $photos = $mi->photos ?? collect();

        // Calculate price with commission
        $finalPrice = $mi->merchantSizePrice();

        return [
            'merchant_item_id' => $mi->id,
            'catalog_item_id' => $mi->catalog_item_id,

            // Quality Brand
            'quality_brand' => $qualityBrand ? [
                'id' => $qualityBrand->id,
                'code' => $qualityBrand->code,
                'name' => $qualityBrand->localized_name,
                'logo' => $qualityBrand->logo_url,
            ] : null,

            // Merchant
            'merchant' => $merchant ? [
                'id' => $merchant->id,
                'name' => getLocalizedShopName($merchant),
                'photo' => $merchant->photo ? asset('assets/images/' . $merchant->photo) : null,
            ] : null,

            // Branch
            'branch' => $branch ? [
                'id' => $branch->id,
                'name' => $branch->warehouse_name ?: $branch->branch_name,
                'location' => $branch->location,
                'latitude' => $branch->latitude,
                'longitude' => $branch->longitude,
            ] : null,

            // Pricing
            'price' => (float) $mi->price,
            'final_price' => $finalPrice,
            'final_price_formatted' => \App\Models\CatalogItem::convertPrice($finalPrice),
            'previous_price' => $mi->previous_price ? (float) $mi->previous_price : null,
            'previous_price_formatted' => $mi->previous_price ? \App\Models\CatalogItem::convertPrice($mi->previous_price) : null,
            'discount_percentage' => $mi->offPercentage(),

            // Stock
            'stock' => (int) ($mi->stock ?? 0),
            'preordered' => (bool) $mi->preordered,
            'in_stock' => ($mi->stock ?? 0) > 0,
            'can_buy' => (($mi->stock ?? 0) > 0 || $mi->preordered),
            'minimum_qty' => max(1, (int) ($mi->minimum_qty ?? 1)),

            // Photos (merchant-specific)
            'photos' => $photos->map(fn($p) => [
                'id' => $p->id,
                'url' => $p->photo_url,
                'is_primary' => (bool) $p->is_primary,
                'sort_order' => (int) $p->sort_order,
            ])->values()->toArray(),

            // For cart
            'user_id' => $mi->user_id,
            'branch_id' => $mi->merchant_branch_id,
        ];
    }

    /**
     * Group offers by Quality Brand → Merchant → Branch
     *
     * @param Collection $merchantItems
     * @param string $sort Sort order for quality groups
     * @return array
     */
    protected function groupOffers(Collection $merchantItems, string $sort = 'price_asc'): array
    {
        $grouped = [];

        foreach ($merchantItems as $mi) {
            $qualityKey = $mi->qualityBrand?->code ?? 'unknown';
            $qualityName = $mi->qualityBrand?->localized_name ?? __('Unknown Quality');
            $qualityLogo = $mi->qualityBrand?->logo_url;

            $merchantKey = $mi->user_id ?? 0;
            $merchantName = $mi->user ? getLocalizedShopName($mi->user) : __('Unknown Merchant');
            $merchantPhoto = $mi->user?->photo ? asset('assets/images/' . $mi->user->photo) : null;

            $branchKey = $mi->merchant_branch_id ?? 0;
            $branchName = $mi->merchantBranch?->warehouse_name ?? $mi->merchantBranch?->branch_name ?? __('Main');

            // Initialize quality group
            if (!isset($grouped[$qualityKey])) {
                $grouped[$qualityKey] = [
                    'quality_code' => $qualityKey,
                    'quality_name' => $qualityName,
                    'quality_logo' => $qualityLogo,
                    'merchants' => [],
                    'offers_count' => 0,
                    'lowest_price' => null,
                    'highest_price' => null,
                ];
            }

            // Initialize merchant group
            if (!isset($grouped[$qualityKey]['merchants'][$merchantKey])) {
                $grouped[$qualityKey]['merchants'][$merchantKey] = [
                    'merchant_id' => $merchantKey,
                    'merchant_name' => $merchantName,
                    'merchant_photo' => $merchantPhoto,
                    'branches' => [],
                    'offers_count' => 0,
                ];
            }

            // Add branch offer
            $offer = $this->formatOffer($mi);
            $grouped[$qualityKey]['merchants'][$merchantKey]['branches'][$branchKey] = [
                'branch_id' => $branchKey,
                'branch_name' => $branchName,
                'offer' => $offer,
            ];
            $grouped[$qualityKey]['merchants'][$merchantKey]['offers_count']++;
            $grouped[$qualityKey]['offers_count']++;

            // Track lowest and highest price per quality
            if ($grouped[$qualityKey]['lowest_price'] === null || $mi->price < $grouped[$qualityKey]['lowest_price']) {
                $grouped[$qualityKey]['lowest_price'] = (float) $mi->price;
            }
            if ($grouped[$qualityKey]['highest_price'] === null || $mi->price > $grouped[$qualityKey]['highest_price']) {
                $grouped[$qualityKey]['highest_price'] = (float) $mi->price;
            }
        }

        // Convert to indexed arrays for JSON
        $result = [];
        foreach ($grouped as $qualityData) {
            $qualityData['merchants'] = array_values($qualityData['merchants']);
            foreach ($qualityData['merchants'] as &$merchantData) {
                $merchantData['branches'] = array_values($merchantData['branches']);
            }
            $qualityData['lowest_price_formatted'] = \App\Models\CatalogItem::convertPrice($qualityData['lowest_price']);
            $qualityData['highest_price_formatted'] = \App\Models\CatalogItem::convertPrice($qualityData['highest_price']);
            $result[] = $qualityData;
        }

        // Sort quality groups by price
        if ($sort === 'price_desc') {
            usort($result, fn($a, $b) => ($b['lowest_price'] ?? 0) <=> ($a['lowest_price'] ?? 0));
        } else {
            usort($result, fn($a, $b) => ($a['lowest_price'] ?? PHP_INT_MAX) <=> ($b['lowest_price'] ?? PHP_INT_MAX));
        }

        return $result;
    }
}
