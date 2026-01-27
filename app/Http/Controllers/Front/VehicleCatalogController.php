<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Catalog;
use App\Domain\Catalog\Models\NewCategory;
use App\Domain\Catalog\Models\Section;
use App\Domain\Catalog\Models\Illustration;
use App\Domain\Catalog\Models\Specification;
use App\Domain\Catalog\Services\CatalogSessionManager;
use App\Domain\Catalog\Services\CategoryFilterService;
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
            ->orderBy('sort', 'ASC')
            ->paginate(12);

        // Year range for filter
        $years = range(date('Y') + 1, 1975);

        // Region options
        $regionOptions = $brand->regions->mapWithKeys(fn($r) => [
            $r->code => getLocalizedLabel($r)
        ])->toArray();

        // PRE-COMPUTED: VIN from session for view (DATA_FLOW_POLICY)
        $vin = Session::get('vin');

        return view('catalog.index', [
            'brand' => $brand,
            'catalogs' => $catalogs,
            'years' => $years,
            'regionOptions' => $regionOptions,
            'region' => $region,
            'searchName' => $searchName,
            'searchYear' => $searchYear,
            'vin' => $vin, // DATA_FLOW_POLICY
        ]);
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

        // Load Level 1 nodes (ready-to-use with key1)
        $nodes = $this->filterService->loadLevel1Nodes(
            $catalog,
            $brand,
            $filterDate,
            $allowedLevel3Codes
        );

        // Get filters for display
        $filters = $this->getFiltersData($catalog);
        $selectedFilters = $this->sessionManager->getSelectedFilters();
        $chips = $this->buildChips($selectedFilters);
        $isVinMode = !empty($vin);

        return view('catalog.level1', [
            'brand' => $brand,
            'catalog' => $catalog,
            'nodes' => $nodes,
            'filters' => $filters,
            'selectedFilters' => $selectedFilters,
            'chips' => $chips,
            'hasVinSource' => $this->chipsHasVinSource($chips), // DATA_FLOW_POLICY
            'vin' => $vin,
            'isVinMode' => $isVinMode,
        ]);
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

        // VIN
        $vin = $request->get('vin') ?? Session::get('vin');

        // Get spec item IDs and filter date for filtering
        $specItemIds = $this->sessionManager->getSpecItemIds($catalog);
        $filterDate = $this->sessionManager->getFilterDate();

        // Load Level 2 nodes (ready-to-use with key1, key2)
        $nodes = $this->filterService->loadLevel2Nodes(
            $catalog,
            $brand,
            $key1,
            $filterDate,
            $specItemIds
        );

        // If no nodes found, the parent category doesn't exist or is invalid
        if ($nodes->isEmpty()) {
            // Verify parent exists for proper 404
            $parent = $this->filterService->findCategory($catalog, $brand, $key1, 1);
            if (!$parent) {
                abort(404, __('Category not found'));
            }
        }

        // Get parent info for breadcrumb (from first node or lookup)
        $parentInfo = $nodes->first() ?? $this->filterService->findCategory($catalog, $brand, $key1, 1);

        // Get filters for display
        $filters = $this->getFiltersData($catalog);
        $selectedFilters = $this->sessionManager->getSelectedFilters();
        $chips = $this->buildChips($selectedFilters);
        $isVinMode = !empty($vin);

        return view('catalog.level2', [
            'brand' => $brand,
            'catalog' => $catalog,
            'nodes' => $nodes,
            'parentInfo' => $parentInfo,
            'filters' => $filters,
            'selectedFilters' => $selectedFilters,
            'chips' => $chips,
            'hasVinSource' => $this->chipsHasVinSource($chips), // DATA_FLOW_POLICY
            'vin' => $vin,
            'isVinMode' => $isVinMode,
            'key1' => $key1,
        ]);
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

        // VIN
        $vin = $request->get('vin') ?? Session::get('vin');

        // Get spec item IDs and filter date for filtering
        $specItemIds = $this->sessionManager->getSpecItemIds($catalog);
        $filterDate = $this->sessionManager->getFilterDate();

        // Load Level 3 nodes (ready-to-use with key1, key2, key3)
        $nodes = $this->filterService->loadLevel3Nodes(
            $catalog,
            $brand,
            $key1,
            $key2,
            $filterDate,
            $specItemIds
        );

        // If no nodes found, verify parent categories exist for proper 404
        if ($nodes->isEmpty()) {
            $parent1 = $this->filterService->findCategory($catalog, $brand, $key1, 1);
            $parent2 = $this->filterService->findCategory($catalog, $brand, $key2, 2);
            if (!$parent1 || !$parent2) {
                abort(404, __('Category not found'));
            }
        }

        // Auto-redirect if only one node
        if ($nodes->count() === 1) {
            $single = $nodes->first();
            return redirect()->route('illustrations', [
                'brand' => $brandName,
                'catalog' => $catalogCode,
                'key1' => $single->key1,
                'key2' => $single->key2,
                'key3' => $single->key3,
                'vin' => $vin,
            ]);
        }

        // Get allowed codes for display marking
        $allowedCodes = $this->sessionManager->getAllowedLevel3Codes();

        // Get filters for display
        $filters = $this->getFiltersData($catalog);
        $selectedFilters = $this->sessionManager->getSelectedFilters();
        $chips = $this->buildChips($selectedFilters);
        $isVinMode = !empty($vin);

        return view('catalog.level3', [
            'brand' => $brand,
            'catalog' => $catalog,
            'nodes' => $nodes,
            'filters' => $filters,
            'selectedFilters' => $selectedFilters,
            'chips' => $chips,
            'hasVinSource' => $this->chipsHasVinSource($chips), // DATA_FLOW_POLICY
            'vin' => $vin,
            'isVinMode' => $isVinMode,
            'key1' => $key1,
            'key2' => $key2,
            'allowedCodes' => $allowedCodes,
        ]);
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

        // Load category hierarchy - must have valid full_code
        $level1Category = NewCategory::where('catalog_id', $catalog->id)
            ->where('full_code', $key1)
            ->where('level', 1)
            ->whereNotNull('full_code')
            ->where('full_code', '!=', '')
            ->first();

        $level2Category = NewCategory::where('catalog_id', $catalog->id)
            ->where('full_code', $key2)
            ->where('level', 2)
            ->whereNotNull('full_code')
            ->where('full_code', '!=', '')
            ->first();

        $level3Category = NewCategory::where('catalog_id', $catalog->id)
            ->where('full_code', $key3)
            ->where('level', 3)
            ->whereNotNull('full_code')
            ->where('full_code', '!=', '')
            ->first();

        if (!$level1Category || !$level2Category || !$level3Category ||
            empty($level1Category->full_code) || empty($level2Category->full_code) || empty($level3Category->full_code)) {
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

        // Rename for view compatibility
        $parentCategory1 = $level1Category;
        $parentCategory2 = $level2Category;
        $category = $level3Category;

        return view('catalog.illustrations', [
            'brand' => $brand,
            'catalog' => $catalog,
            'parentCategory1' => $parentCategory1,
            'parentCategory2' => $parentCategory2,
            'category' => $category,
            'section' => $section,
            'illustration' => $illustration,
            'callouts' => $callouts,
            'filters' => $filters,
            'selectedFilters' => $selectedFilters,
            'chips' => $chips,
            'hasVinSource' => $this->chipsHasVinSource($chips), // DATA_FLOW_POLICY
            'vin' => $vin,
            'isVinMode' => $isVinMode,
            'key1' => $key1,
            'key2' => $key2,
            'key3' => $key3,
            'autoOpen' => $autoOpen,
            'highlightCallout' => $highlightCallout,
            'sectionId' => $sectionId,
            'categoryId' => $categoryId,
        ]);
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

            $selectedValue = $savedFilters[$spec->name]['value_id'] ?? null;
            $items = $spec->items->map(fn($item) => [
                'value_id' => $item->value_id,
                'label' => $item->label ?? $item->value_id,
            ])->toArray();

            // PRE-COMPUTED: Find selected label (DATA_FLOW_POLICY - no @php in view)
            $selectedLabel = '-- ' . __('catalog.select') . ' --';
            if ($selectedValue) {
                foreach ($items as $item) {
                    if ($item['value_id'] == $selectedValue) {
                        $selectedLabel = $item['label'];
                        break;
                    }
                }
            }

            $filters[$spec->name] = [
                'label' => $spec->label ?? $spec->name,
                'items' => $items,
                'readonly' => $isFromVin,
                'selected' => $selectedValue,
                'selectedLabel' => $selectedLabel,
                'hasValue' => !empty($selectedValue),
            ];
        }

        // Add year and month filters
        $this->addDateFilters($filters, $catalog, $savedFilters);

        // PRE-COMPUTED: Count selected filters (DATA_FLOW_POLICY)
        $selectedCount = collect($filters)->filter(fn($f) => !empty($f['selected']))->count();

        // Store in filters array for view access
        $filters['_meta'] = [
            'selectedCount' => $selectedCount,
            'catalogCode' => $catalog->code ?? '',
            'catalogName' => $catalog->name ?? $catalog->shortName ?? $catalog->code ?? '',
        ];

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

        $yearSelected = $savedFilters['year']['value_id'] ?? null;
        $monthSelected = $savedFilters['month']['value_id'] ?? null;

        // PRE-COMPUTED: Selected labels (DATA_FLOW_POLICY)
        $yearSelectedLabel = __('catalog.year');
        if ($yearSelected) {
            $yearSelectedLabel = (string)$yearSelected;
        }

        $monthSelectedLabel = __('catalog.month');
        if ($monthSelected) {
            $monthSelectedLabel = str_pad($monthSelected, 2, '0', STR_PAD_LEFT);
        }

        $filters['year'] = [
            'label' => __('Production Year'),
            'items' => collect($years)->map(fn($y) => [
                'value_id' => (string)$y,
                'label' => (string)$y,
            ])->toArray(),
            'readonly' => $yearFromVin,
            'selected' => $yearSelected,
            'selectedLabel' => $yearSelectedLabel,
            'hasValue' => !empty($yearSelected),
        ];

        $filters['month'] = [
            'label' => __('Production Month'),
            'items' => collect($months)->map(fn($m) => [
                'value_id' => str_pad($m, 2, '0', STR_PAD_LEFT),
                'label' => str_pad($m, 2, '0', STR_PAD_LEFT),
            ])->toArray(),
            'readonly' => $monthFromVin,
            'selected' => $monthSelected,
            'selectedLabel' => $monthSelectedLabel,
            'hasValue' => !empty($monthSelected),
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

    /**
     * PRE-COMPUTED: Check if any chip has VIN source (DATA_FLOW_POLICY)
     * @param array $chips
     * @return bool
     */
    protected function chipsHasVinSource(array $chips): bool
    {
        return collect($chips)->contains(fn($c) => ($c['source'] ?? '') === 'vin');
    }
}
