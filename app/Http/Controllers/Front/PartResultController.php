<?php

namespace App\Http\Controllers\Front;

use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Services\AlternativeService;
use App\Domain\Catalog\Services\CatalogDisplayService;
use App\Domain\Catalog\Services\CatalogItemOffersService;
use App\Domain\Catalog\Events\ProductViewedEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PartResultController
 *
 * Handles the part result page - shows all offers for a given part number.
 * This is the NEW approach: CatalogItem-first (one page per part_number).
 *
 * Route: GET /result/{part_number}
 */
class PartResultController extends FrontBaseController
{
    public function __construct(
        private CatalogItemOffersService $offersService,
        private AlternativeService $alternativeService,
        private CatalogDisplayService $displayService
    ) {
        parent::__construct();
    }

    /**
     * Show all offers for a part number
     *
     * @param Request $request
     * @param string $part_number The part number to look up
     * @return \Illuminate\View\View
     */
    public function show(Request $request, string $part_number)
    {
        // Find catalog item by part number
        $catalogItem = CatalogItem::where('part_number', $part_number)
            ->with(['fitments.brand'])
            ->withCount('catalogReviews')
            ->withAvg('catalogReviews', 'rating')
            ->first();

        if (!$catalogItem) {
            abort(404, __('Part not found'));
        }

        // ═══════════════════════════════════════════════════════════════════
        // EVENT-DRIVEN: Dispatch ProductViewedEvent
        // All channels (Web, Mobile, API, WhatsApp) get same event
        // ═══════════════════════════════════════════════════════════════════
        event(new ProductViewedEvent(
            catalogItemId: $catalogItem->id,
            customerId: Auth::id(),
            sessionId: $request->session()->getId(),
            source: $request->query('ref', 'direct')
        ));

        // Get sort parameter
        $sort = $request->input('sort', 'price_asc');

        // Get grouped offers using existing service with sort
        $offersData = $this->offersService->getGroupedOffers($catalogItem->id, $sort);

        // Get display data from DisplayService (API-ready)
        $catalogDisplayData = $this->displayService->forPartResult($catalogItem);

        // Get alternatives using group_id
        $alternatives = $this->alternativeService->getAlternatives(
            $part_number,
            includeSelf: false,
            returnSelfIfNoAlternatives: false
        );

        $viewData = [
            'catalogItem' => $catalogItem,
            'part_number' => $part_number,
            'offersData' => $offersData,
            'fitmentBrands' => $catalogDisplayData['fitment_brands'],
            'catalogDisplayData' => $catalogDisplayData,
            'alternatives' => $alternatives,
            'currentSort' => $sort,
            'gs' => $this->gs,
        ];

        // Return only offers section for AJAX requests
        if ($request->ajax() || $request->has('ajax')) {
            return view('frontend.partials.part-result-offers', $viewData);
        }

        return view('frontend.part-result', $viewData);
    }
}
