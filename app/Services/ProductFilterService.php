<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Childcategory;
use App\Models\MerchantProduct;
use App\Models\QualityBrand;
use App\Models\Subcategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Centralized service for product filtering logic
 * Handles all product filtering for /category routes (Products page)
 */
class ProductFilterService
{
    public function __construct(
        private ProductCardDataBuilder $cardBuilder
    ) {}

    /**
     * Get filter sidebar data (vendors, brand qualities, categories)
     */
    public function getFilterSidebarData(): array
    {
        return [
            'categories' => Category::with('subs.childs')->where('status', 1)->get(),
            'vendors' => $this->getActiveVendors(),
            'brand_qualities' => QualityBrand::active()->orderBy('name_en', 'asc')->get(),
        ];
    }

    /**
     * Get active vendors with products
     */
    public function getActiveVendors()
    {
        return MerchantProduct::select('merchant_products.user_id')
            ->join('users', 'users.id', '=', 'merchant_products.user_id')
            ->where('merchant_products.status', 1)
            ->where('users.is_vendor', 2)
            ->groupBy('merchant_products.user_id')
            ->selectRaw('merchant_products.user_id, users.shop_name')
            ->orderBy('users.shop_name', 'asc')
            ->get();
    }

    /**
     * Resolve category hierarchy from slugs
     */
    public function resolveCategoryHierarchy(?string $catSlug, ?string $subcatSlug, ?string $childcatSlug): array
    {
        $cat = null;
        $subcat = null;
        $childcat = null;

        if (!empty($catSlug)) {
            $cat = Category::where('slug', $catSlug)->first();
        }

        if (!empty($subcatSlug) && $cat) {
            $subcat = Subcategory::where('slug', $subcatSlug)
                ->where('category_id', $cat->id)
                ->first();
        }

        if (!empty($childcatSlug) && $subcat) {
            $childcat = Childcategory::where('slug', $childcatSlug)
                ->where('subcategory_id', $subcat->id)
                ->first();
        }

        return compact('cat', 'subcat', 'childcat');
    }

    /**
     * Build the base query for merchant products
     */
    public function buildBaseQuery(): Builder
    {
        $query = MerchantProduct::query()
            ->leftJoin('products', 'products.id', '=', 'merchant_products.product_id')
            ->select('merchant_products.*')
            ->where('merchant_products.status', 1)
            ->where('merchant_products.stock', '>=', 1)
            ->whereHas('user', fn($u) => $u->where('is_vendor', 2));

        $this->cardBuilder->applyMerchantProductEagerLoading($query);

        return $query;
    }

    /**
     * Apply product_fitments filter based on selected category hierarchy
     * Only applies when at least one category level is selected
     */
    public function applyFitmentFilters(Builder $query, $cat, $subcat, $childcat): void
    {
        // Don't apply fitment filters if no category is selected (base /category route)
        if (!$cat && !$subcat && !$childcat) {
            return;
        }

        // Build a single efficient fitment query that checks all selected levels
        $query->whereHas('product', function ($productQuery) use ($cat, $subcat, $childcat) {
            $productQuery->whereExists(function ($exists) use ($cat, $subcat, $childcat) {
                $exists->selectRaw(1)
                    ->from('product_fitments')
                    ->whereColumn('product_fitments.product_id', 'products.id');

                // Apply ALL selected category levels together (AND logic)
                if ($cat) {
                    $exists->where('product_fitments.category_id', $cat->id);
                }
                if ($subcat) {
                    $exists->where('product_fitments.subcategory_id', $subcat->id);
                }
                if ($childcat) {
                    $exists->where('product_fitments.childcategory_id', $childcat->id);
                }
            });
        });
    }

    /**
     * Apply vendor filter (supports multiple selection)
     */
    public function applyVendorFilter(Builder $query, Request $request): void
    {
        $vendorFilter = $this->normalizeArrayInput($request->vendor);

        // Also check 'user' and 'store' params for backward compatibility
        if (empty($vendorFilter) && $request->filled('user')) {
            $vendorFilter = [(int) $request->user];
        }
        if (empty($vendorFilter) && $request->filled('store')) {
            $vendorFilter = [(int) $request->store];
        }

        // Only apply filter if vendors are selected (otherwise = ALL)
        if (!empty($vendorFilter)) {
            $query->whereIn('merchant_products.user_id', $vendorFilter);
        }
    }

    /**
     * Apply brand quality filter (supports multiple selection)
     * IMPORTANT: Only uses whereIn, no duplicate single where clause
     */
    public function applyBrandQualityFilter(Builder $query, Request $request): void
    {
        $brandQuality = $this->normalizeArrayInput($request->brand_quality);

        // Only apply filter if brand qualities are selected (otherwise = ALL)
        if (!empty($brandQuality)) {
            $query->whereIn('merchant_products.brand_quality_id', $brandQuality);
        }
    }

    /**
     * Apply price range filter
     */
    public function applyPriceFilter(Builder $query, ?float $minPrice, ?float $maxPrice, float $currencyValue = 1): void
    {
        if ($minPrice) {
            $query->where('merchant_products.price', '>=', $minPrice / $currencyValue);
        }
        if ($maxPrice) {
            $query->where('merchant_products.price', '<=', $maxPrice / $currencyValue);
        }
    }

    /**
     * Apply search filter
     */
    public function applySearchFilter(Builder $query, ?string $search): void
    {
        if (!empty($search)) {
            $query->whereHas('product', fn($pq) =>
                $pq->where('name', 'like', '%' . $search . '%')
                   ->orWhere('name', 'like', $search . '%')
            );
        }
    }

    /**
     * Apply discount filter
     */
    public function applyDiscountFilter(Builder $query, bool $hasDiscount): void
    {
        if ($hasDiscount) {
            $query->where('merchant_products.is_discount', 1)
                  ->where('merchant_products.discount_date', '>=', date('Y-m-d'));
        }
    }

    /**
     * Apply attribute filters
     */
    public function applyAttributeFilters(Builder $query, $cat, $subcat, $childcat, Request $request): void
    {
        if (!$cat && !$subcat && !$childcat) {
            return;
        }

        $attributeFilters = [];

        $collectFilters = function ($category) use ($request, &$attributeFilters) {
            if ($category && $category->attributes) {
                foreach ($category->attributes as $attribute) {
                    $inputName = $attribute->input_name;
                    $values = $request[$inputName] ?? null;
                    if (!empty($values) && is_array($values)) {
                        foreach ($values as $value) {
                            $attributeFilters[] = $value;
                        }
                    }
                }
            }
        };

        $collectFilters($cat);
        $collectFilters($subcat);
        $collectFilters($childcat);

        if (!empty($attributeFilters)) {
            $query->whereHas('product', function ($productQuery) use ($attributeFilters) {
                $productQuery->where(function ($q) use ($attributeFilters) {
                    foreach ($attributeFilters as $index => $filter) {
                        if ($index === 0) {
                            $q->where('attributes', 'like', '%"' . $filter . '"%');
                        } else {
                            $q->orWhere('attributes', 'like', '%"' . $filter . '"%');
                        }
                    }
                });
            });
        }
    }

    /**
     * Apply sorting to query
     */
    public function applySorting(Builder $query, ?string $sort): void
    {
        match ($sort) {
            'date_desc' => $query->latest('merchant_products.id'),
            'date_asc' => $query->oldest('merchant_products.id'),
            'price_asc' => $query->orderBy('merchant_products.price', 'asc'),
            'price_desc' => $query->orderBy('merchant_products.price', 'desc'),
            'sku_asc' => $query->orderBy('products.sku', 'asc'),
            'sku_desc' => $query->orderBy('products.sku', 'desc'),
            'latest_product' => $query->leftJoin(
                DB::raw('(SELECT product_id, MAX(beginYear) AS max_year FROM product_fitments GROUP BY product_id) AS pf_max'),
                'pf_max.product_id', '=', 'merchant_products.product_id'
            )->orderBy('pf_max.max_year', 'desc'),
            'oldest_product' => $query->leftJoin(
                DB::raw('(SELECT product_id, MAX(beginYear) AS max_year FROM product_fitments GROUP BY product_id) AS pf_max'),
                'pf_max.product_id', '=', 'merchant_products.product_id'
            )->orderBy('pf_max.max_year', 'asc'),
            default => $query->latest('merchant_products.id'),
        };
    }

    /**
     * Apply all filters to query
     */
    public function applyAllFilters(
        Builder $query,
        Request $request,
        $cat = null,
        $subcat = null,
        $childcat = null,
        float $currencyValue = 1
    ): void {
        $this->applyFitmentFilters($query, $cat, $subcat, $childcat);
        $this->applyVendorFilter($query, $request);
        $this->applyBrandQualityFilter($query, $request);
        $this->applyPriceFilter(
            $query,
            $request->filled('min') ? (float) $request->min : null,
            $request->filled('max') ? (float) $request->max : null,
            $currencyValue
        );
        $this->applySearchFilter($query, $request->search);
        $this->applyDiscountFilter($query, $request->has('type'));
        $this->applyAttributeFilters($query, $cat, $subcat, $childcat, $request);
        $this->applySorting($query, $request->sort);
    }

    /**
     * Execute the full category query with pagination
     */
    public function getProductResults(
        Request $request,
        ?string $catSlug = null,
        ?string $subcatSlug = null,
        ?string $childcatSlug = null,
        int $perPage = 12,
        float $currencyValue = 1
    ): array {
        $sidebarData = $this->getFilterSidebarData();
        $hierarchy = $this->resolveCategoryHierarchy($catSlug, $subcatSlug, $childcatSlug);
        $query = $this->buildBaseQuery();

        $this->applyAllFilters(
            $query,
            $request,
            $hierarchy['cat'],
            $hierarchy['subcat'],
            $hierarchy['childcat'],
            $currencyValue
        );

        $paginator = $query->paginate($perPage)->withQueryString();
        $cards = $this->cardBuilder->buildCardsFromPaginator($paginator);

        // Build filter summary for zero results display
        $filterSummary = $this->buildFilterSummary(
            $request,
            $hierarchy['cat'],
            $hierarchy['subcat'],
            $hierarchy['childcat'],
            $sidebarData['vendors'],
            $sidebarData['brand_qualities']
        );

        return array_merge($sidebarData, $hierarchy, [
            'cards' => $cards,
            'prods' => $cards,
            'filterSummary' => $filterSummary,
        ]);
    }

    /**
     * Build filter summary for display (especially for zero results)
     */
    private function buildFilterSummary(
        Request $request,
        $cat,
        $subcat,
        $childcat,
        $allVendors,
        $allBrandQualities
    ): array {
        $summary = [
            'hasFilters' => false,
            'category' => null,
            'subcategory' => null,
            'childcategory' => null,
            'vendors' => [],
            'brandQualities' => [],
        ];

        // Category hierarchy
        if ($cat) {
            $summary['category'] = $cat->localized_name ?? $cat->name;
            $summary['hasFilters'] = true;
        }
        if ($subcat) {
            $summary['subcategory'] = $subcat->localized_name ?? $subcat->name;
            $summary['hasFilters'] = true;
        }
        if ($childcat) {
            $summary['childcategory'] = $childcat->localized_name ?? $childcat->name;
            $summary['hasFilters'] = true;
        }

        // Selected vendors
        $vendorIds = $this->normalizeArrayInput($request->vendor);
        if (!empty($vendorIds)) {
            foreach ($allVendors as $vendor) {
                if (in_array($vendor->user_id, $vendorIds)) {
                    $summary['vendors'][] = $vendor->shop_name;
                }
            }
            $summary['hasFilters'] = true;
        }

        // Selected brand qualities
        $brandQualityIds = $this->normalizeArrayInput($request->brand_quality);
        if (!empty($brandQualityIds)) {
            foreach ($allBrandQualities as $bq) {
                if (in_array($bq->id, $brandQualityIds)) {
                    $summary['brandQualities'][] = $bq->localized_name ?? $bq->name_en;
                }
            }
            $summary['hasFilters'] = true;
        }

        return $summary;
    }

    /**
     * Normalize input to array
     */
    private function normalizeArrayInput($input): array
    {
        if (empty($input)) {
            return [];
        }
        if (is_array($input)) {
            return array_filter($input);
        }
        return [$input];
    }
}
