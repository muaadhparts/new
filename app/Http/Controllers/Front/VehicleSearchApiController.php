<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Domain\Catalog\Services\VehicleSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VehicleSearchApiController extends Controller
{
    public function __construct(
        protected VehicleSearchService $searchService
    ) {}

    /**
     * البحث بالاسم - اقتراحات
     */
    public function searchSuggestions(Request $request)
    {
        try {
            $query = trim($request->input('query', ''));
            $catalogCode = $request->input('catalog');

            if (mb_strlen($query, 'UTF-8') < 2) {
                return response()->json(['results' => []]);
            }

            $catalog = $this->searchService->findCatalogByCode($catalogCode);
            if (!$catalog) {
                return response()->json(['error' => __('Catalog not found')], 404);
            }

            $allowedCodes = array_values(array_filter(
                array_map('strval', (array) session('preloaded_full_code', []))
            ));

            if (empty($allowedCodes)) {
                return response()->json(['results' => []]);
            }

            $results = $this->searchService->getLabelSuggestions($catalogCode, $query, $allowedCodes);

            return response()->json([
                'success' => true,
                'results' => $results
            ]);
        } catch (\Exception $e) {
            Log::error('Vehicle search suggestions error', ['error' => $e->getMessage()]);
            return response()->json(['error' => __('Search failed')], 500);
        }
    }

    /**
     * البحث الكامل - جلب النتائج
     */
    public function search(Request $request)
    {
        try {
            $query = trim($request->input('query', ''));
            $catalogCode = $request->input('catalog');
            $searchType = $request->input('type', 'number');

            $catalog = $this->searchService->findCatalogByCode($catalogCode, withBrand: true);
            if (!$catalog) {
                return response()->json(['error' => __('Catalog not found')], 404);
            }

            // Validate query length
            if ($searchType === 'number') {
                $cleanQuery = preg_replace('/[^0-9A-Za-z]+/', '', $query);
                if (strlen($cleanQuery) < 5) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Part number must be at least 5 characters')
                    ]);
                }
            } else {
                if (mb_strlen($query, 'UTF-8') < 2) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Part name must be at least 2 characters')
                    ]);
                }
            }

            $allowedCodes = array_values(array_filter(
                array_map('strval', (array) session('preloaded_full_code', []))
            ));

            if (empty($allowedCodes)) {
                return response()->json([
                    'success' => false,
                    'message' => __('No allowed sections found')
                ]);
            }

            // Fetch callouts using service
            $rows = $searchType === 'number'
                ? $this->searchService->fetchCalloutsByNumber($catalogCode, $query, $allowedCodes)
                : $this->searchService->fetchCalloutsByLabel($catalogCode, $query, $allowedCodes);

            // Build callout options using service
            $calloutOptions = $this->searchService->buildCalloutOptions($rows, $allowedCodes);

            // Enrich with keys using service
            $calloutOptions = $this->searchService->enrichCalloutOptionsWithKeys($calloutOptions, $catalog);

            $count = count($calloutOptions);

            if ($count === 0) {
                return response()->json([
                    'success' => false,
                    'message' => $searchType === 'number'
                        ? __('No matching callout found for this part number')
                        : __('No matching callout found for this part name')
                ]);
            }

            // Single result - return redirect URL
            if ($count === 1) {
                $opt = $calloutOptions[0];
                if (!empty($opt['key1']) && !empty($opt['key2']) && !empty($opt['key3'])) {
                    $cleanUrl = route('illustrations', [
                        'brand'   => $catalog->brand->name,
                        'catalog' => $catalog->code,
                        'key1'    => $opt['key1'],
                        'key2'    => $opt['key2'],
                        'key3'    => $opt['key3'],
                        'vin'     => session('vin'),
                    ]);

                    return response()->json([
                        'success' => true,
                        'single' => true,
                        'redirect_url' => $cleanUrl,
                        'callout_info' => [
                            'callout'       => $opt['callout'],
                            'section_id'    => $opt['section_id'],
                            'category_id'   => $opt['category_id'] ?? null,
                            'category_code' => $opt['category_code'],
                        ]
                    ]);
                }
            }

            // Multiple results - return for display
            $resultsWithUrls = array_map(function($opt) use ($catalog) {
                $url = null;
                if (!empty($opt['key1']) && !empty($opt['key2']) && !empty($opt['key3'])) {
                    $url = route('illustrations', [
                        'brand'         => $catalog->brand->name,
                        'catalog'       => $catalog->code,
                        'key1'          => $opt['key1'],
                        'key2'          => $opt['key2'],
                        'key3'          => $opt['key3'],
                        'vin'           => session('vin'),
                        'callout'       => $opt['callout'],
                        'auto_open'     => 1,
                        'section_id'    => $opt['section_id'],
                        'category_code' => $opt['category_code'],
                        'catalog_code'  => $catalog->code,
                        'category_id'   => $opt['category_id'] ?? null,
                    ]);
                }
                return array_merge($opt, ['url' => $url]);
            }, $calloutOptions);

            return response()->json([
                'success' => true,
                'single' => false,
                'count' => $count,
                'results' => $resultsWithUrls
            ]);

        } catch (\Exception $e) {
            Log::error('Vehicle search error', ['error' => $e->getMessage()]);
            return response()->json(['error' => __('Search failed')], 500);
        }
    }
}
