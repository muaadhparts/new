<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\MerchantItem;
use App\Models\CatalogItem;
use App\Models\SkuAlternative;
use App\Services\CatalogItemCardDataBuilder;
use Illuminate\Http\Request;

class SearchResultsController extends Controller
{
    private const PER_PAGE = 12;

    public function __construct(
        private CatalogItemCardDataBuilder $cardBuilder
    ) {}

    public function show(Request $request, $part_number)
    {
        // Get filters from request
        $storeFilter = $request->get('store', 'all');
        $qualityFilter = $request->get('quality', 'all');
        $sortBy = $request->get('sort', 'default');
        $page = $request->get('page', 1);

        // Get main catalog items and alternatives
        $prods = CatalogItem::where('part_number', $part_number)->get();
        $alternatives = $this->getAlternatives($part_number);

        // Merge all catalog items
        $allCatalogItems = $prods->merge($alternatives);
        $catalogItemIds = $allCatalogItems->pluck('id')->toArray();

        if (empty($catalogItemIds)) {
            return view('frontend.search-results', [
                'part_number' => $part_number,
                'cards' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, self::PER_PAGE),
                'alternativeCards' => collect(),
                'availableStores' => collect(),
                'availableQualities' => collect(),
                'storeFilter' => $storeFilter,
                'qualityFilter' => $qualityFilter,
                'sortBy' => $sortBy,
            ]);
        }

        $mainCatalogItemIds = $prods->pluck('id')->toArray();
        $altCatalogItemIds = $alternatives->pluck('id')->toArray();

        // Query 1: Get available filters (lightweight - no eager loading needed)
        $filtersQuery = MerchantItem::whereIn('catalog_item_id', $catalogItemIds)
            ->where('status', 1)
            ->with(['user:id,shop_name,shop_name_ar', 'qualityBrand:id,name_en,name_ar']);

        $allForFilters = $filtersQuery->get(['id', 'user_id', 'brand_quality_id']);
        $availableStores = $allForFilters->pluck('user')->filter()->unique('id')->keyBy('id');
        $availableQualities = $allForFilters->pluck('qualityBrand')->filter()->unique('id')->keyBy('id');

        // Query 2: Main catalogItems - PAGINATED (only 12 DTOs built)
        $mainPaginator = $this->loadMerchantItemsPaginated(
            $mainCatalogItemIds,
            $storeFilter,
            $qualityFilter,
            $sortBy,
            self::PER_PAGE
        );

        // Build DTOs only for the 12 items on current page
        $cards = $this->cardBuilder->buildCardsFromPaginator($mainPaginator);

        // Query 3: Alternative catalogItems - Limited to 12 (no pagination, just limit)
        $alternativeCards = collect();
        if (!empty($altCatalogItemIds)) {
            $altMerchants = $this->loadMerchantItemsLimited($altCatalogItemIds, $storeFilter, $qualityFilter, $sortBy, 12);
            $alternativeCards = $this->cardBuilder->buildCardsFromMerchants($altMerchants);
        }

        return view('frontend.search-results', [
            'part_number' => $part_number,
            'cards' => $cards, // LengthAwarePaginator with DTOs
            'alternativeCards' => $alternativeCards,
            'availableStores' => $availableStores,
            'availableQualities' => $availableQualities,
            'storeFilter' => $storeFilter,
            'qualityFilter' => $qualityFilter,
            'sortBy' => $sortBy,
        ]);
    }

    /**
     * Get alternative catalogItems for a PART_NUMBER
     */
    private function getAlternatives(string $part_number): \Illuminate\Support\Collection
    {
        $skuAlternative = SkuAlternative::where('part_number', $part_number)->first();

        if (!$skuAlternative || !$skuAlternative->group_id) {
            return collect();
        }

        $alternativeSkus = SkuAlternative::where('group_id', $skuAlternative->group_id)
            ->where('part_number', '<>', $part_number)
            ->pluck('part_number')
            ->toArray();

        if (empty($alternativeSkus)) {
            return collect();
        }

        return CatalogItem::whereIn('part_number', $alternativeSkus)->get();
    }

    /**
     * Load merchant catalogItems with PAGINATION at query level
     * Sorting is done in the query, not after
     */
    private function loadMerchantItemsPaginated(
        array $catalogItemIds,
        string $storeFilter,
        string $qualityFilter,
        string $sortBy,
        int $perPage
    ) {
        $query = MerchantItem::whereIn('catalog_item_id', $catalogItemIds)
            ->where('status', 1);

        // Apply eager loading
        $this->cardBuilder->applyMerchantItemEagerLoading($query);

        // Apply filters
        if ($storeFilter !== 'all') {
            $query->where('user_id', $storeFilter);
        }

        if ($qualityFilter !== 'all') {
            $query->where('brand_quality_id', $qualityFilter);
        }

        // Apply sorting at QUERY level (not collection level)
        $this->applySortingToQuery($query, $sortBy);

        // Return paginator - only fetches 12 rows from DB
        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Load merchant items with LIMIT (for alternatives)
     */
    private function loadMerchantItemsLimited(
        array $catalogItemIds,
        string $storeFilter,
        string $qualityFilter,
        string $sortBy,
        int $limit
    ) {
        $query = MerchantItem::whereIn('catalog_item_id', $catalogItemIds)
            ->where('status', 1);

        // Apply eager loading
        $this->cardBuilder->applyMerchantItemEagerLoading($query);

        // Apply filters
        if ($storeFilter !== 'all') {
            $query->where('user_id', $storeFilter);
        }

        if ($qualityFilter !== 'all') {
            $query->where('brand_quality_id', $qualityFilter);
        }

        // Apply sorting at QUERY level
        $this->applySortingToQuery($query, $sortBy);

        return $query->limit($limit)->get();
    }

    /**
     * Apply sorting directly to the query (ORDER BY in SQL)
     */
    private function applySortingToQuery($query, string $sortBy): void
    {
        match ($sortBy) {
            'sku_asc' => $query->join('catalog_items', 'merchant_items.catalog_item_id', '=', 'catalog_items.id')
                               ->orderBy('catalog_items.part_number', 'asc')
                               ->select('merchant_items.*'),
            'sku_desc' => $query->join('catalog_items', 'merchant_items.catalog_item_id', '=', 'catalog_items.id')
                                ->orderBy('catalog_items.part_number', 'desc')
                                ->select('merchant_items.*'),
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'stock_desc' => $query->orderBy('stock', 'desc'),
            'newest' => $query->orderBy('id', 'desc'),
            default => $query->orderByRaw('CASE WHEN stock > 0 THEN 0 ELSE 1 END')
                             ->orderBy('price', 'asc'),
        };
    }
}
