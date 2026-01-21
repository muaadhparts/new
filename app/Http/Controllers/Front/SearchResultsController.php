<?php

namespace App\Http\Controllers\Front;

use App\DataTransferObjects\CatalogItemCardDTO;
use App\Models\CatalogItem;
use App\Models\MerchantItem;
use App\Services\AlternativeService;
use App\Traits\NormalizesInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * SearchResultsController
 *
 * Handles the search results page for desktop search bar.
 * Displays catalog items with offers button and alternatives (using unified card).
 */
class SearchResultsController extends FrontBaseController
{
    use NormalizesInput;

    protected AlternativeService $alternativeService;

    public function __construct(AlternativeService $alternativeService)
    {
        parent::__construct();
        $this->alternativeService = $alternativeService;
    }

    /**
     * Display search results page
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = trim($request->input('q', ''));
        $cards = collect();
        $alternativeCards = collect();

        if (strlen($query) >= 2) {
            // Get favorites for logged-in user
            $favoriteCatalogItemIds = collect();
            $favoriteMerchantIds = collect();
            if (Auth::check()) {
                $favoriteCatalogItemIds = Auth::user()->favorites()->pluck('catalog_item_id');
                $favoriteMerchantIds = Auth::user()->favorites()->whereNotNull('merchant_item_id')->pluck('merchant_item_id');
            }

            // Search catalog items
            $catalogItems = $this->searchCatalogItems($query);

            // Build cards using DTO
            $cards = $catalogItems->map(function ($catalogItem) use ($favoriteCatalogItemIds, $favoriteMerchantIds) {
                // Get best merchant (lowest price with stock)
                $bestMerchant = $this->getBestMerchant($catalogItem->id);

                return CatalogItemCardDTO::fromCatalogItemFirst(
                    $catalogItem,
                    $bestMerchant,
                    $favoriteCatalogItemIds,
                    $favoriteMerchantIds
                );
            });

            // Get alternatives for the first result using AlternativeService
            if ($catalogItems->isNotEmpty()) {
                $firstItem = $catalogItems->first();

                // جمع part_numbers الموجودة في Results لاستثنائها من Alternatives
                $resultPartNumbers = $catalogItems->pluck('part_number')->toArray();

                // جلب البدائل (بدون الصنف نفسه)
                $alternativeItems = $this->alternativeService->getAlternatives(
                    $firstItem->part_number,
                    includeSelf: false,
                    returnSelfIfNoAlternatives: false
                );

                // استثناء الأصناف الموجودة مسبقاً في Results
                $alternativeItems = $alternativeItems->filter(function ($item) use ($resultPartNumbers) {
                    return !in_array($item->part_number, $resultPartNumbers);
                });

                $alternativeCards = $alternativeItems->map(function ($catalogItem) use ($favoriteCatalogItemIds, $favoriteMerchantIds) {
                    $bestMerchant = $this->getBestMerchant($catalogItem->id);
                    return CatalogItemCardDTO::fromCatalogItemFirst(
                        $catalogItem,
                        $bestMerchant,
                        $favoriteCatalogItemIds,
                        $favoriteMerchantIds
                    );
                });
            }
        }

        return view('frontend.search-results', [
            'query' => $query,
            'cards' => $cards,
            'alternativeCards' => $alternativeCards,
            'count' => $cards->count(),
        ]);
    }

    /**
     * Search catalog items by part number or name
     */
    protected function searchCatalogItems(string $query)
    {
        $part_number = $this->cleanInput($query);

        // Search by part number (prefix)
        $results = CatalogItem::where('part_number', 'like', "{$part_number}%")
            ->with(['fitments.brand'])
            ->withCount(['merchantItems as offers_count' => function ($q) {
                $q->where('status', 1)
                  ->whereHas('user', fn($u) => $u->where('is_merchant', 2));
            }])
            ->limit(50)
            ->get();

        // Fallback to name search
        if ($results->isEmpty()) {
            $results = $this->searchByName($query);
        }

        return $results;
    }

    /**
     * Search by name (Arabic or English)
     */
    private function searchByName(string $query)
    {
        $normalized = $this->normalizeArabic($query);
        $words = array_filter(preg_split('/\s+/', trim($normalized)));

        if (empty($words)) {
            return collect();
        }

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
                ->limit(50)
                ->get();

            if ($results->isNotEmpty()) {
                return $results;
            }
        }

        return collect();
    }

    /**
     * Get best merchant for a catalog item (lowest price with stock)
     */
    private function getBestMerchant(int $catalogItemId): ?MerchantItem
    {
        return MerchantItem::where('catalog_item_id', $catalogItemId)
            ->where('status', 1)
            ->whereHas('user', fn($q) => $q->where('is_merchant', 2))
            ->with(['user', 'qualityBrand', 'merchantBranch'])
            ->orderByRaw('CASE WHEN stock > 0 THEN 0 ELSE 1 END')
            ->orderBy('price', 'asc')
            ->first();
    }
}
