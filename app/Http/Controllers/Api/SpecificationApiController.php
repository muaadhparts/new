<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Catalog;
use App\Domain\Catalog\Services\CatalogSessionManager;
use App\Domain\Catalog\Services\CategoryFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SpecificationApiController extends Controller
{
    protected CatalogSessionManager $sessionManager;
    protected CategoryFilterService $filterService;

    public function __construct(CatalogSessionManager $sessionManager, CategoryFilterService $filterService)
    {
        $this->sessionManager = $sessionManager;
        $this->filterService = $filterService;
    }

    /**
     * Save specification filters
     * POST /api/specs/save
     */
    public function save(Request $request)
    {
        // Don't save in VIN mode
        if (Session::has('vin') && !empty(Session::get('vin'))) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify filters in VIN mode',
            ]);
        }

        $catalogCode = $request->input('catalog_code');
        $filters = $request->input('filters', []);

        if (!$catalogCode) {
            return response()->json([
                'success' => false,
                'message' => 'Catalog code is required',
            ], 400);
        }

        // Build data to save
        $dataToSave = [];
        foreach ($filters as $key => $value) {
            if (!empty($value) && $value !== '') {
                $dataToSave[$key] = [
                    'value_id' => $value,
                    'source' => 'manual',
                ];
            }
        }

        // Save to session
        $this->sessionManager->setSelectedFilters($dataToSave);

        // Update allowed codes
        $this->updateAllowedCodes($catalogCode);

        return response()->json([
            'success' => true,
            'message' => 'Filters saved successfully',
            'filters' => $dataToSave,
        ]);
    }

    /**
     * Clear all specification filters
     * POST /api/specs/clear
     */
    public function clear(Request $request)
    {
        // Don't clear in VIN mode
        if (Session::has('vin') && !empty(Session::get('vin'))) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot clear filters in VIN mode',
            ]);
        }

        $catalogCode = $request->input('catalog_code');

        // Clear filters from session
        $this->sessionManager->clearFilters();

        // Update allowed codes (all codes when no filters)
        if ($catalogCode) {
            $this->updateAllowedCodes($catalogCode, true);
        }

        return response()->json([
            'success' => true,
            'message' => 'Filters cleared successfully',
        ]);
    }

    /**
     * Get current filters
     * GET /api/specs/current
     */
    public function current(Request $request)
    {
        $filters = $this->sessionManager->getSelectedFilters();
        $vin = Session::get('vin');

        return response()->json([
            'success' => true,
            'filters' => $filters,
            'is_vin_mode' => !empty($vin),
            'vin' => $vin,
        ]);
    }

    /**
     * Update allowed Level3 codes based on filters
     */
    protected function updateAllowedCodes(string $catalogCode, bool $clearAll = false): void
    {
        $catalog = Catalog::where('code', $catalogCode)->first();
        if (!$catalog) return;

        $brand = Brand::find($catalog->brand_id);
        if (!$brand) return;

        if ($clearAll) {
            $allowedCodes = $this->filterService->getFilteredLevel3FullCodes(
                $catalog,
                $brand,
                null,
                []
            );
        } else {
            $specItemIds = $this->sessionManager->getSpecItemIds($catalog);
            $filterDate = $this->sessionManager->getFilterDate();

            $allowedCodes = $this->filterService->getFilteredLevel3FullCodes(
                $catalog,
                $brand,
                $filterDate,
                $specItemIds
            );
        }

        $this->sessionManager->setAllowedLevel3Codes($allowedCodes);
    }
}
