<?php

namespace App\Http\Controllers\Api\Front;

use App\Domain\Catalog\Services\CatalogSearchService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        protected CatalogSearchService $searchService
    ) {}

    /**
     * Basic search by name/price only
     */
    public function search(Request $request)
    {
        try {
            $filters = [
                'search' => $request->search,
                'min' => $request->min,
                'max' => $request->max,
                'sort' => $request->sort ?? 'price_asc',
            ];

            // Get DTOs from service (Clean Architecture)
            $catalogItemCards = $this->searchService->searchWithFilters($filters);

            return response()->json([
                'status' => true,
                'data' => $catalogItemCards->toArray(),
                'error' => []
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status' => false,
                'data' => [],
                'error' => ['message' => $e->getMessage()]
            ]);
        }
    }
}
