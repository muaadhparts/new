<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\DTOs\CatalogItemCardDTO;
use App\Domain\Merchant\Models\MerchantItem;
use App\Traits\NormalizesInput;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Service for catalog item search operations.
 * Centralizes search logic for both web and API.
 */
class CatalogSearchService
{
    use NormalizesInput;

    /**
     * Search catalog items by query string.
     * Searches part number first, then falls back to name search.
     *
     * @param string $query
     * @param int $limit
     * @return Collection<CatalogItem>
     */
    public function searchByQuery(string $query, int $limit = 50): Collection
    {
        $cleanQuery = $this->cleanInput($query);

        if (strlen($cleanQuery) < 2) {
            return collect();
        }

        // Try part number search first (prefix match)
        $results = $this->searchByPartNumber($cleanQuery, $limit);

        // Fallback to name search
        if ($results->isEmpty()) {
            $results = $this->searchByName($query, $limit);
        }

        return $results;
    }

    /**
     * Search by part number (prefix match).
     *
     * @param string $partNumber
     * @param int $limit
     * @return Collection<CatalogItem>
     */
    public function searchByPartNumber(string $partNumber, int $limit = 50): Collection
    {
        return CatalogItem::where('part_number', 'like', "{$partNumber}%")
            ->with(['fitments.brand'])
            ->withCount(['merchantItems as offers_count' => function ($q) {
                $q->where('status', 1)
                  ->whereHas('user', fn($u) => $u->where('is_merchant', 2));
            }])
            ->limit($limit)
            ->get();
    }

    /**
     * Search by name (Arabic or English).
     * Uses progressive word matching.
     *
     * @param string $query
     * @param int $limit
     * @return Collection<CatalogItem>
     */
    public function searchByName(string $query, int $limit = 50): Collection
    {
        $normalized = $this->normalizeArabic($query);
        $words = array_filter(preg_split('/\s+/', trim($normalized)));

        if (empty($words)) {
            return collect();
        }

        // Progressive word matching (try all words first, then fewer)
        for ($i = count($words); $i > 0; $i--) {
            $subset = array_slice($words, 0, $i);

            $results = CatalogItem::query()
                ->where(function ($q) use ($subset) {
                    foreach ($subset as $word) {
                        $word = trim($word);
                        if ($word === '') continue;

                        $q->where(function ($sub) use ($word) {
                            $sub->where('label_ar', 'like', "%{$word}%")
                                ->orWhere('label_en', 'like', "%{$word}%");
                        });
                    }
                })
                ->with(['fitments.brand'])
                ->withCount(['merchantItems as offers_count' => function ($q) {
                    $q->where('status', 1)
                      ->whereHas('user', fn($u) => $u->where('is_merchant', 2));
                }])
                ->limit($limit)
                ->get();

            if ($results->isNotEmpty()) {
                return $results;
            }
        }

        return collect();
    }

    /**
     * Get best merchant offer for a catalog item (lowest price with stock).
     *
     * @param int $catalogItemId
     * @return MerchantItem|null
     */
    public function getBestMerchant(int $catalogItemId): ?MerchantItem
    {
        return MerchantItem::where('catalog_item_id', $catalogItemId)
            ->where('status', 1)
            ->whereHas('user', fn($q) => $q->where('is_merchant', 2))
            ->with(['user', 'qualityBrand', 'merchantBranch'])
            ->orderByRaw('CASE WHEN stock > 0 THEN 0 ELSE 1 END')
            ->orderBy('price', 'asc')
            ->first();
    }

    /**
     * Build card DTOs from search results.
     *
     * @param Collection $catalogItems
     * @param Collection|null $favoriteCatalogItemIds
     * @param Collection|null $favoriteMerchantIds
     * @return Collection<CatalogItemCardDTO>
     */
    public function buildCards(
        Collection $catalogItems,
        ?Collection $favoriteCatalogItemIds = null,
        ?Collection $favoriteMerchantIds = null
    ): Collection {
        $favoriteCatalogItemIds = $favoriteCatalogItemIds ?? collect();
        $favoriteMerchantIds = $favoriteMerchantIds ?? collect();

        return $catalogItems->map(function ($catalogItem) use ($favoriteCatalogItemIds, $favoriteMerchantIds) {
            $bestMerchant = $this->getBestMerchant($catalogItem->id);

            return CatalogItemCardDTO::fromCatalogItemFirst(
                $catalogItem,
                $bestMerchant,
                $favoriteCatalogItemIds,
                $favoriteMerchantIds
            );
        });
    }

    /**
     * Get user favorites (for authenticated user).
     *
     * @return array ['catalog_item_ids' => Collection, 'merchant_ids' => Collection]
     */
    public function getUserFavorites(): array
    {
        if (!Auth::check()) {
            return [
                'catalog_item_ids' => collect(),
                'merchant_ids' => collect(),
            ];
        }

        $user = Auth::user();

        return [
            'catalog_item_ids' => $user->favorites()->pluck('catalog_item_id'),
            'merchant_ids' => $user->favorites()->whereNotNull('merchant_item_id')->pluck('merchant_item_id'),
        ];
    }

    /**
     * Search with filters (for API).
     *
     * @param array $filters ['search' => string, 'min' => float, 'max' => float, 'sort' => string]
     * @return Collection<CatalogItem>
     */
    public function searchWithFilters(array $filters): Collection
    {
        $query = CatalogItem::query()->where('status', 1);

        // Search filter
        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        // Price filters - Note: These assume price on CatalogItem exists
        // For proper multi-merchant, price should come from MerchantItem
        if (!empty($filters['min'])) {
            $query->whereHas('merchantItems', fn($q) => $q->where('price', '>=', $filters['min']));
        }

        if (!empty($filters['max'])) {
            $query->whereHas('merchantItems', fn($q) => $q->where('price', '<=', $filters['max']));
        }

        // Sorting
        $this->applySorting($query, $filters['sort'] ?? 'price_asc');

        return $query->get();
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
            return;
        }

        match ($sort) {
            'price_desc' => $query->orderBy('price', 'desc'),
            'part_number' => $query->orderBy('part_number', 'asc'),
            default => $query->orderBy('price', 'asc'),
        };
    }
}
