<?php

namespace App\Http\Controllers\Front;

use App\Models\CatalogItem;
use App\Services\AlternativeService;
use App\Services\CatalogItemOffersService;
use Illuminate\Http\Request;

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
        private AlternativeService $alternativeService
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

        // Get sort parameter
        $sort = $request->input('sort', 'price_asc');

        // Get grouped offers using existing service with sort
        $offersData = $this->offersService->getGroupedOffers($catalogItem->id, $sort);

        // Extract fitment brands for breadcrumb
        $fitmentBrands = [];
        if ($catalogItem->fitments && $catalogItem->fitments->count() > 0) {
            $fitmentBrands = $catalogItem->fitments
                ->map(fn($f) => $f->brand)
                ->filter()
                ->unique('id')
                ->values()
                ->map(fn($brand) => [
                    'id' => $brand->id,
                    'name' => $brand->localized_name,
                    'logo' => $brand->photo_url,
                    'slug' => $brand->slug,
                ])
                ->toArray();
        }

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
            'fitmentBrands' => $fitmentBrands,
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
