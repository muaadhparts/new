<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Catalog;
use App\Models\NewCategory;
use App\Models\Section;
use App\Models\Illustration;
use App\Models\Specification;
use App\Services\CatalogSessionManager;
use App\Services\CategoryFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class VehicleCatalogController extends Controller
{
    protected CatalogSessionManager $sessionManager;
    protected CategoryFilterService $filterService;

    public function __construct(CatalogSessionManager $sessionManager, CategoryFilterService $filterService)
    {
        $this->sessionManager = $sessionManager;
        $this->filterService = $filterService;
    }

    // ========================================
    // 1. CATALOGS LIST PAGE
    // ========================================

    public function index(Request $request, string $brandName)
    {
        // Clear previous session data when entering from brand selection
        $this->sessionManager->clearAll();

        $brand = Brand::with('regions')
            ->whereRaw('LOWER(name) = ?', [strtolower($brandName)])
            ->first();

        if (!$brand) {
            abort(404, __('Brand not found'));
        }

        // Get filter parameters
        $region = $request->get('region', $brand->regions->first()?->code);
        $searchName = $request->get('search', '');
        $searchYear = $request->get('year');

        // Get region ID
        $brandRegionId = $brand->regions->where('code', $region)->first()?->id;

        // Query catalogs
        $catalogs = Catalog::with('brand:id,name')
            ->where('brand_id', $brand->id)
            ->when($brandRegionId, fn($q) => $q->where('brand_region_id', $brandRegionId))
            ->when($searchName, fn($q) =>
                $q->where(function ($q) use ($searchName) {
                    $q->where('label_ar', 'like', "%{$searchName}%")
                      ->orWhere('label_en', 'like', "%{$searchName}%")
                      ->orWhere('code', 'like', "%{$searchName}%");
                })
            )
            ->when($searchYear, fn($q) =>
                $q->where('beginYear', '<=', $searchYear)
                  ->where(fn($q2) =>
                      $q2->where('endYear', '>=', $searchYear)
                         ->orWhere('endYear', 0)
                  )
            )
            ->orderBy('new_id', 'ASC')
            ->paginate(12);

        // Year range for filter
        $years = range(date('Y') + 1, 1975);

        // Region options
        $regionOptions = $brand->regions->mapWithKeys(fn($r) => [
            $r->code => getLocalizedLabel($r)
        ])->toArray();

        return view('catalog.index', compact(
            'brand',
            'catalogs',
            'years',
            'regionOptions',
            'region',
            'searchName',
            'searchYear'
        ));
    }

    // ========================================
    // 2. LEVEL 1 CATEGORIES PAGE
    // ========================================

    public function level1(Request $request, string $brandName, string $catalogCode)
    {
        $brand = Brand::with('regions')
            ->whereRaw('LOWER(name) = ?', [strtolower($brandName)])
            ->first();

        if (!$brand) {
            abort(404, __('Brand not found'));
        }

        $catalog = Catalog::with('brand')
            ->where('code', $catalogCode)
            ->where('brand_id', $brand->id)
            ->first();

        if (!$catalog) {
            abort(404, __('Catalog not found'));
        }

        // Handle VIN from query parameter
        $vin = $request->get('vin');
        if ($vin) {
            $this->sessionManager->setVin($vin);
        } else {
            $vin = Session::get('vin');
        }

        // Get filters and compute allowed codes
        $specItemIds = $this->sessionManager->getSpecItemIds($catalog);
        $filterDate = $this->sessionManager->getFilterDate();

        $allowedLevel3Codes = $this->filterService->getFilteredLevel3FullCodes(
            $catalog,
            $brand,
            $filterDate,
            $specItemIds
        );

        $this->sessionManager->setAllowedLevel3Codes($allowedLevel3Codes);

        // Load Level 1 categories
        $labelField = app()->getLocale() === 'ar' ? 'label_ar' : 'label_en';

        $cacheKey = sprintf(
            'catalog_level1_%d_%d_%s_%s_%s',
            $catalog->id,
            $brand->id,
            $labelField,
            $filterDate ?? 'no_date',
            md5(serialize($allowedLevel3Codes))
        );

        $categories = Cache::remember($cacheKey, 3600, function() use ($catalog, $brand, $labelField, $filterDate, $allowedLevel3Codes) {
            return $this->filterService->loadLevel1Categories(
                $catalog,
                $brand,
                $labelField,
                $filterDate,
                $allowedLevel3Codes
            );
        });

        // Get filters for display
        $filters = $this->getFiltersData($catalog);
        $selectedFilters = $this->sessionManager->getSelectedFilters();
        $chips = $this->buildChips($selectedFilters);
        $isVinMode = !empty($vin);

        return view('catalog.level1', compact(
            'brand',
            'catalog',
            'categories',
            'filters',
            'selectedFilters',
            'chips',
            'vin',
            'isVinMode'
        ));
    }

    // ========================================
    // 3. LEVEL 2 CATEGORIES PAGE
    // ========================================

    public function level2(Request $request, string $brandName, string $catalogCode, string $key1)
    {
        $brand = Brand::whereRaw('LOWER(name) = ?', [strtolower($brandName)])->first();
        if (!$brand) abort(404, __('Brand not found'));

        $catalog = Catalog::where('code', $catalogCode)->where('brand_id', $brand->id)->first();
        if (!$catalog) abort(404, __('Catalog not found'));

        // Parent category (Level 1)
        $parentCategory = NewCategory::where('catalog_id', $catalog->id)
            ->where('brand_id', $brand->id)
            ->where('full_code', $key1)
            ->where('level', 1)
            ->first();

        if (!$parentCategory) abort(404, __('Category not found'));

        // VIN
        $vin = $request->get('vin') ?? Session::get('vin');

        // Get spec item IDs and filter date for filtering
        $specItemIds = $this->sessionManager->getSpecItemIds($catalog);
        $filterDate = $this->sessionManager->getFilterDate();

        // Load Level 2 categories
        $categories = $this->filterService->loadLevel2Categories(
            $catalog,
            $brand,
            $parentCategory,
            $filterDate,
            $specItemIds
        );

        // Get filters for display
        $filters = $this->getFiltersData($catalog);
        $selectedFilters = $this->sessionManager->getSelectedFilters();
        $chips = $this->buildChips($selectedFilters);
        $isVinMode = !empty($vin);

        return view('catalog.level2', compact(
            'brand',
            'catalog',
            'parentCategory',
            'categories',
            'filters',
            'selectedFilters',
            'chips',
            'vin',
            'isVinMode',
            'key1'
        ));
    }

    // ========================================
    // 4. LEVEL 3 CATEGORIES PAGE
    // ========================================

    public function level3(Request $request, string $brandName, string $catalogCode, string $key1, string $key2)
    {
        $brand = Brand::whereRaw('LOWER(name) = ?', [strtolower($brandName)])->first();
        if (!$brand) abort(404, __('Brand not found'));

        $catalog = Catalog::where('code', $catalogCode)->where('brand_id', $brand->id)->first();
        if (!$catalog) abort(404, __('Catalog not found'));

        // Parent categories
        $level1Category = NewCategory::where('catalog_id', $catalog->id)
            ->where('brand_id', $brand->id)
            ->where('full_code', $key1)
            ->where('level', 1)
            ->first();

        $level2Category = NewCategory::where('catalog_id', $catalog->id)
            ->where('brand_id', $brand->id)
            ->where('full_code', $key2)
            ->where('level', 2)
            ->first();

        if (!$level1Category || !$level2Category) abort(404, __('Category not found'));

        // VIN
        $vin = $request->get('vin') ?? Session::get('vin');

        // Get spec item IDs and filter date for filtering
        $specItemIds = $this->sessionManager->getSpecItemIds($catalog);
        $filterDate = $this->sessionManager->getFilterDate();

        // Load Level 3 categories
        $categories = $this->filterService->loadLevel3Categories(
            $catalog,
            $brand,
            $level2Category,
            $filterDate,
            $specItemIds
        );

        // Get allowed codes for display marking
        $allowedCodes = $this->sessionManager->getAllowedLevel3Codes();

        // Auto-redirect if only one category
        if ($categories->count() === 1) {
            $single = $categories->first();
            return redirect()->route('catalog.illustrations', [
                'brand' => $brandName,
                'catalog' => $catalogCode,
                'key1' => $key1,
                'key2' => $key2,
                'key3' => $single->full_code,
                'vin' => $vin,
            ]);
        }

        // Get filters for display
        $filters = $this->getFiltersData($catalog);
        $selectedFilters = $this->sessionManager->getSelectedFilters();
        $chips = $this->buildChips($selectedFilters);
        $isVinMode = !empty($vin);

        return view('catalog.level3', compact(
            'brand',
            'catalog',
            'level1Category',
            'level2Category',
            'categories',
            'filters',
            'selectedFilters',
            'chips',
            'vin',
            'isVinMode',
            'key1',
            'key2',
            'allowedCodes'
        ));
    }

    // ========================================
    // 5. ILLUSTRATIONS PAGE
    // ========================================

    public function illustrations(Request $request, string $brandName, string $catalogCode, string $key1, string $key2, string $key3)
    {
        $brand = Brand::whereRaw('LOWER(name) = ?', [strtolower($brandName)])->first();
        if (!$brand) abort(404, __('Brand not found'));

        $catalog = Catalog::where('code', $catalogCode)->where('brand_id', $brand->id)->first();
        if (!$catalog) abort(404, __('Catalog not found'));

        // Load category hierarchy
        $level1Category = NewCategory::where('catalog_id', $catalog->id)
            ->where('full_code', $key1)->where('level', 1)->first();

        $level2Category = NewCategory::where('catalog_id', $catalog->id)
            ->where('full_code', $key2)->where('level', 2)->first();

        $level3Category = NewCategory::where('catalog_id', $catalog->id)
            ->where('full_code', $key3)->where('level', 3)->first();

        if (!$level1Category || !$level2Category || !$level3Category) {
            abort(404, __('Category not found'));
        }

        // VIN
        $vin = $request->get('vin') ?? Session::get('vin');

        // Load section and illustration
        $section = Section::where('full_code', $key3)
            ->where('catalog_id', $catalog->id)
            ->first();

        $illustration = null;
        $callouts = collect();

        if ($section) {
            $illustration = Illustration::with('callouts')
                ->where('section_id', $section->id)
                ->first();

            if ($illustration) {
                $callouts = $illustration->callouts;
            }
        }

        // Get filters for display
        $filters = $this->getFiltersData($catalog);
        $selectedFilters = $this->sessionManager->getSelectedFilters();
        $chips = $this->buildChips($selectedFilters);
        $isVinMode = !empty($vin);

        // Query params for callout modal
        $autoOpen = $request->get('auto_open');
        $highlightCallout = $request->get('callout');
        $sectionId = $request->get('section_id');
        $categoryId = $request->get('category_id');

        return view('catalog.illustrations', compact(
            'brand',
            'catalog',
            'level1Category',
            'level2Category',
            'level3Category',
            'section',
            'illustration',
            'callouts',
            'filters',
            'selectedFilters',
            'chips',
            'vin',
            'isVinMode',
            'key1',
            'key2',
            'key3',
            'autoOpen',
            'highlightCallout',
            'sectionId',
            'categoryId'
        ));
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Get available filters for a catalog
     */
    protected function getFiltersData(Catalog $catalog): array
    {
        $savedFilters = $this->sessionManager->getSelectedFilters();

        $filters = [];

        // Load specifications
        $specs = Specification::with(['items' => fn($q) => $q->where('catalog_id', $catalog->id)])->get();

        foreach ($specs as $spec) {
            if ($spec->items->isEmpty()) continue;

            $isFromVin = isset($savedFilters[$spec->name])
                && is_array($savedFilters[$spec->name])
                && ($savedFilters[$spec->name]['source'] ?? '') === 'vin';

            $filters[$spec->name] = [
                'label' => $spec->label ?? $spec->name,
                'items' => $spec->items->map(fn($item) => [
                    'value_id' => $item->value_id,
                    'label' => $item->label ?? $item->value_id,
                ])->toArray(),
                'readonly' => $isFromVin,
                'selected' => $savedFilters[$spec->name]['value_id'] ?? null,
            ];
        }

        // Add year and month filters
        $this->addDateFilters($filters, $catalog, $savedFilters);

        return $filters;
    }

    /**
     * Add year and month filters
     */
    protected function addDateFilters(array &$filters, Catalog $catalog, array $savedFilters): void
    {
        $start = $catalog->beginDate;
        $end = $catalog->endDate;

        $startYear = ($start && strlen($start) >= 4) ? (int)substr($start, 0, 4) : 1980;
        $endYear = ($end && strlen($end) >= 4 && $end !== '000000') ? (int)substr($end, 0, 4) : (int)date('Y');

        $years = range($endYear, $startYear);
        $months = range(1, 12);

        $yearFromVin = isset($savedFilters['year'])
            && is_array($savedFilters['year'])
            && ($savedFilters['year']['source'] ?? '') === 'vin';

        $monthFromVin = isset($savedFilters['month'])
            && is_array($savedFilters['month'])
            && ($savedFilters['month']['source'] ?? '') === 'vin';

        $filters['year'] = [
            'label' => __('Production Year'),
            'items' => collect($years)->map(fn($y) => [
                'value_id' => (string)$y,
                'label' => (string)$y,
            ])->toArray(),
            'readonly' => $yearFromVin,
            'selected' => $savedFilters['year']['value_id'] ?? null,
        ];

        $filters['month'] = [
            'label' => __('Production Month'),
            'items' => collect($months)->map(fn($m) => [
                'value_id' => str_pad($m, 2, '0', STR_PAD_LEFT),
                'label' => str_pad($m, 2, '0', STR_PAD_LEFT),
            ])->toArray(),
            'readonly' => $monthFromVin,
            'selected' => $savedFilters['month']['value_id'] ?? null,
        ];
    }

    /**
     * Build chips for display
     */
    protected function buildChips(array $filters): array
    {
        $pretty = [
            'BODY' => __('ui.body'),
            'ENGINE' => __('ui.engine'),
            'GRADE' => __('ui.grade'),
            'TRANS' => __('ui.transmission'),
            'TRIM_COLOUR' => __('ui.trim_colour'),
            'BODY_COLOR' => __('ui.body_color'),
            'DRIVE' => __('ui.drive'),
            'DESTINATION' => __('ui.destination'),
            'BUILD_DATE' => __('ui.build_date'),
            'year' => __('Production Year'),
            'month' => __('Production Month'),
        ];

        $chips = [];

        // Combine year and month into BUILD_DATE if both exist
        if (isset($filters['year']['value_id']) && isset($filters['month']['value_id'])) {
            $yyyy = (string) $filters['year']['value_id'];
            $mm = str_pad((string) $filters['month']['value_id'], 2, '0', STR_PAD_LEFT);
            $chips[] = [
                'key' => 'BUILD_DATE',
                'label' => $pretty['BUILD_DATE'] ?? 'Build Date',
                'value' => "{$yyyy}-{$mm}",
                'source' => $filters['year']['source'] ?? 'manual',
            ];
        }

        foreach ($filters as $key => $data) {
            if (in_array($key, ['year', 'month'])) continue;

            $valueId = is_array($data) ? ($data['value_id'] ?? null) : $data;
            if (empty($valueId)) continue;

            $chips[] = [
                'key' => $key,
                'label' => $pretty[$key] ?? ucfirst(str_replace('_', ' ', strtolower($key))),
                'value' => $valueId,
                'source' => is_array($data) ? ($data['source'] ?? 'manual') : 'manual',
            ];
        }

        return $chips;
    }
}
