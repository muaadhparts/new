<?php

namespace App\Http\Controllers\Api\Front;

use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Services\CatalogSearchService;
use App\Http\Controllers\Controller;
use App\Http\Resources\CatalogItemListResource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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

            $prods = $this->searchService->searchWithFilters($filters);

            $prods = (new Collection(CatalogItem::filterProducts($prods)));

            return response()->json([
                'status' => true,
                'data' => CatalogItemListResource::collection($prods->flatten(1)),
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
