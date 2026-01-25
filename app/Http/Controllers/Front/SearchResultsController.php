<?php

namespace App\Http\Controllers\Front;

use App\Domain\Catalog\DTOs\CatalogItemCardDTO;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Services\AlternativeService;
use App\Domain\Catalog\Services\CatalogSearchService;
use Illuminate\Http\Request;

/**
 * SearchResultsController
 *
 * Handles the search results page for desktop search bar.
 * Displays catalog items with offers button and alternatives (using unified card).
 */
class SearchResultsController extends FrontBaseController
{
    public function __construct(
        protected AlternativeService $alternativeService,
        protected CatalogSearchService $searchService
    ) {
        parent::__construct();
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
            $favorites = $this->searchService->getUserFavorites();
            $favoriteCatalogItemIds = $favorites['catalog_item_ids'];
            $favoriteMerchantIds = $favorites['merchant_ids'];

            // Search catalog items using service
            $catalogItems = $this->searchService->searchByQuery($query);

            // Build cards using service
            $cards = $this->searchService->buildCards($catalogItems, $favoriteCatalogItemIds, $favoriteMerchantIds);

            // Get alternatives for the first result using AlternativeService
            if ($catalogItems->isNotEmpty()) {
                $firstItem = $catalogItems->first();

                // Collect part_numbers from results to exclude from alternatives
                $resultPartNumbers = $catalogItems->pluck('part_number')->toArray();

                // Get alternatives (without self)
                $alternativeItems = $this->alternativeService->getAlternatives(
                    $firstItem->part_number,
                    includeSelf: false,
                    returnSelfIfNoAlternatives: false
                );

                // Exclude items already in results
                $alternativeItems = $alternativeItems->filter(function ($item) use ($resultPartNumbers) {
                    return !in_array($item->part_number, $resultPartNumbers);
                });

                $alternativeCards = $this->searchService->buildCards($alternativeItems, $favoriteCatalogItemIds, $favoriteMerchantIds);
            }
        }

        return view('frontend.search-results', [
            'query' => $query,
            'cards' => $cards,
            'alternativeCards' => $alternativeCards,
            'count' => $cards->count(),
        ]);
    }
}
