<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\CatalogItem;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Service for catalog item operations in API context.
 * Handles catalog item listing, filtering, and sorting for API endpoints.
 */
class CatalogItemApiService
{
    public function __construct(
        private CatalogItemCardDTOBuilder $cardBuilder
    ) {}

    /**
     * Get merchant's catalog items (returns DTOs).
     *
     * @param int $merchantId
     * @param array $filters ['type' => 'normal'|'affiliate'|null]
     * @return Collection
     */
    public function getMerchantCatalogItems(int $merchantId, array $filters = []): Collection
    {
        $itemType = $filters['type'] ?? null;
        $itemTypeCheck = !empty($itemType) && in_array($itemType, ['normal', 'affiliate']);

        // Query catalog items that have merchant offers from this merchant
        $query = CatalogItem::whereHas('merchantItems', function($q) use ($merchantId, $itemType, $itemTypeCheck) {
                $q->where('user_id', $merchantId)->where('status', 1);
                if ($itemTypeCheck) {
                    $q->where('item_type', $itemType);
                }
            })
            ->with([
                'merchantItems' => function($q) use ($merchantId, $itemType, $itemTypeCheck) {
                    $q->where('user_id', $merchantId)->where('status', 1)
                      ->with(['user:id,is_merchant,shop_name,shop_name_ar', 'qualityBrand:id,name_en,name_ar,logo', 'merchantBranch:id,warehouse_name']);
                    if ($itemTypeCheck) {
                        $q->where('item_type', $itemType);
                    }
                },
                'fitments.brand',
                'catalogReviews'
            ])
            ->withCount('catalogReviews')
            ->withAvg('catalogReviews', 'rating');

        $catalogItems = $query->get();

        // Transform to DTOs
        return $catalogItems->map(function ($catalogItem) {
            $merchantItem = $catalogItem->merchantItems->first();

            if ($merchantItem) {
                return $this->cardBuilder->fromMerchantItem($merchantItem);
            } else {
                return $this->cardBuilder->fromCatalogItemFirst($catalogItem);
            }
        });
    }

    /**
     * Get catalog items with filters (returns DTOs).
     *
     * @param array $filters [
     *   'item_type' => 'normal'|'affiliate'|null,
     *   'highlight' => 'latest'|null,
     *   'limit' => int|null,
     *   'paginate' => int|null,
     * ]
     * @return Collection|LengthAwarePaginator
     */
    public function getCatalogItems(array $filters = []): Collection|LengthAwarePaginator
    {
        $itemType = $filters['item_type'] ?? null;
        $itemTypeCheck = !empty($itemType) && in_array($itemType, ['normal', 'affiliate']);
        $limit = $filters['limit'] ?? 0;
        $paginate = $filters['paginate'] ?? 0;
        $highlight = $filters['highlight'] ?? null;

        $query = CatalogItem::where('status', 1);

        // item_type filter via merchantItems
        if ($itemTypeCheck) {
            $query = $query->whereHas('merchantItems', fn($q) => $q->where('item_type', $itemType)->where('status', 1));
        }

        // Only 'latest' highlight is supported
        if ($highlight === 'latest') {
            $query = $query->orderBy('id', 'desc');
        }

        // Eager load relationships
        $query->with([
            'merchantItems' => function($q) {
                $q->where('status', 1)
                  ->with(['user:id,is_merchant,shop_name,shop_name_ar', 'qualityBrand:id,name_en,name_ar,logo', 'merchantBranch:id,warehouse_name']);
            },
            'fitments.brand',
            'catalogReviews'
        ])
        ->withCount('catalogReviews')
        ->withAvg('catalogReviews', 'rating');

        if ($limit > 0) {
            $query = $query->take($limit);
        }

        // Get results
        if ($paginate > 0) {
            $paginator = $query->paginate($paginate);
            
            // Transform to DTOs
            $paginator->getCollection()->transform(function ($catalogItem) {
                return $this->cardBuilder->fromCatalogItemFirst($catalogItem);
            });

            return $paginator;
        } else {
            $catalogItems = $query->get();

            // Transform to DTOs
            return $catalogItems->map(function ($catalogItem) {
                return $this->cardBuilder->fromCatalogItemFirst($catalogItem);
            });
        }
    }

    /**
     * Get user's favorite catalog items (returns DTOs).
     *
     * @param int $userId
     * @param string $sort
     * @return Collection
     */
    public function getUserFavorites(int $userId, string $sort = 'price_asc'): Collection
    {
        $catalogItemIds = \App\Domain\Commerce\Models\FavoriteSeller::where('user_id', $userId)
            ->pluck('catalog_item_id');

        if ($catalogItemIds->isEmpty()) {
            return collect();
        }

        $query = CatalogItem::status(1)->whereIn('id', $catalogItemIds);

        // Eager load relationships
        $query->with([
            'merchantItems' => function($q) {
                $q->where('status', 1)
                  ->with(['user:id,is_merchant,shop_name,shop_name_ar', 'qualityBrand:id,name_en,name_ar,logo', 'merchantBranch:id,warehouse_name']);
            },
            'fitments.brand',
            'catalogReviews'
        ])
        ->withCount('catalogReviews')
        ->withAvg('catalogReviews', 'rating');

        // Apply sorting
        $this->applySorting($query, $sort);

        $catalogItems = $query->get();

        // Transform to DTOs
        return $catalogItems->map(function ($catalogItem) {
            return $this->cardBuilder->fromCatalogItemFirst($catalogItem);
        });
    }

    /**
     * Apply sorting to query.
     */
    private function applySorting($query, string $sort): void
    {
        $isArabic = app()->getLocale() === 'ar';

        if ($sort === 'name_asc') {
            if ($isArabic) {
                $query->orderByRaw("CASE WHEN label_ar IS NOT NULL AND label_ar != '' THEN 0 ELSE 1 END ASC")
                      ->orderByRaw("COALESCE(NULLIF(label_ar, ''), NULLIF(label_en, ''), name) ASC");
            } else {
                $query->orderByRaw("CASE WHEN label_en IS NOT NULL AND label_en != '' THEN 0 ELSE 1 END ASC")
                      ->orderByRaw("COALESCE(NULLIF(label_en, ''), NULLIF(label_ar, ''), name) ASC");
            }
        } else {
            match ($sort) {
                'price_desc' => $query->orderBy('price', 'desc'),
                'part_number' => $query->orderBy('part_number', 'asc'),
                default => $query->orderBy('price', 'asc'),
            };
        }
    }
}
