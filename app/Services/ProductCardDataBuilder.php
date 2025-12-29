<?php

namespace App\Services;

use App\DataTransferObjects\ProductCardDTO;
use App\Models\MerchantProduct;
use App\Models\Product;
use App\Models\Favorite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * ProductCardDataBuilder
 *
 * Centralized service for building product card data with:
 * - Consistent eager loading for all product views (list/home/search)
 * - Pre-loaded favorite data (one query, cached)
 * - DTOs with all pre-computed values (Blade only prints)
 *
 * Usage in Controller:
 *   $builder = app(ProductCardDataBuilder::class);
 *   $cards = $builder->buildCardsFromMerchants($merchants);
 *   return view('...', ['cards' => $cards]);
 */
class ProductCardDataBuilder
{
    private ?Collection $userFavoriteProductIds = null;
    private ?Collection $userFavoriteMerchantIds = null;
    private ?object $generalSettings = null;
    private bool $initialized = false;

    /**
     * Standard eager loading for MerchantProduct queries
     */
    public const MERCHANT_PRODUCT_RELATIONS = [
        'user:id,is_vendor,name,shop_name,shop_name_ar,email',
        'qualityBrand:id,name_en,name_ar,logo',
        'product' => [
            'brand:id,name,name_ar,photo',
        ],
    ];

    /**
     * Standard eager loading for Product queries
     */
    public const PRODUCT_RELATIONS = [
        'brand:id,name,name_ar,photo',
    ];

    /**
     * Initialize the builder - loads favorites and settings
     */
    public function initialize(): self
    {
        if ($this->initialized) {
            return $this;
        }

        $this->loadFavoriteData();
        $this->loadGeneralSettings();
        $this->initialized = true;

        return $this;
    }

    /**
     * Apply standard eager loading to a MerchantProduct query
     */
    public function applyMerchantProductEagerLoading(Builder $query): Builder
    {
        return $query->with([
            'user:id,is_vendor,name,shop_name,shop_name_ar,email',
            'qualityBrand:id,name_en,name_ar,logo',
            'product' => function ($q) {
                $q->with('brand:id,name,name_ar,photo')
                    ->withCount('ratings')
                    ->withAvg('ratings', 'rating');
            },
        ]);
    }

    /**
     * Apply standard eager loading to a Product query
     */
    public function applyProductEagerLoading(Builder $query): Builder
    {
        return $query->with([
            'brand:id,name,name_ar,photo',
            'merchantProducts' => function ($q) {
                $q->where('status', 1)
                    ->with([
                        'user:id,is_vendor,name,shop_name,shop_name_ar,email',
                        'qualityBrand:id,name_en,name_ar,logo',
                    ])
                    ->orderBy('price');
            },
        ])->withCount('ratings')->withAvg('ratings', 'rating');
    }

    /**
     * Build view data with all pre-loaded shared data
     *
     * @param array $additionalData Controller-specific data
     * @return array Complete view data
     */
    public function getViewData(array $additionalData = []): array
    {
        $this->initialize();

        return array_merge([
            'favoriteProductIds' => $this->userFavoriteProductIds,
            'favoriteMerchantIds' => $this->userFavoriteMerchantIds,
            'favoriteCount' => $this->userFavoriteProductIds->count(),
        ], $additionalData);
    }

    /**
     * Check if a product is in favorites (use in Blade)
     */
    public function isInFavorites(int $productId): bool
    {
        $this->initialize();
        return $this->userFavoriteProductIds->contains($productId);
    }

    /**
     * Check if a merchant product is in favorites (use in Blade)
     */
    public function isMerchantInFavorites(int $merchantProductId): bool
    {
        $this->initialize();
        return $this->userFavoriteMerchantIds->contains($merchantProductId);
    }

    /**
     * Get favorite product IDs
     */
    public function getFavoriteProductIds(): Collection
    {
        $this->initialize();
        return $this->userFavoriteProductIds;
    }

    /**
     * Get favorite merchant product IDs
     */
    public function getFavoriteMerchantIds(): Collection
    {
        $this->initialize();
        return $this->userFavoriteMerchantIds;
    }

    /**
     * Get cached general settings
     */
    public function getGeneralSettings(): object
    {
        $this->initialize();
        return $this->generalSettings;
    }

    /**
     * Load user favorite data (one query, cached 5 minutes)
     */
    private function loadFavoriteData(): void
    {
        if (!Auth::check()) {
            $this->userFavoriteProductIds = collect();
            $this->userFavoriteMerchantIds = collect();
            return;
        }

        $userId = Auth::id();

        $favorites = Cache::remember(
            "user_favorites_{$userId}",
            300,
            fn() => Favorite::where('user_id', $userId)
                ->select(['product_id', 'merchant_product_id'])
                ->get()
        );

        $this->userFavoriteProductIds = $favorites->pluck('product_id')->filter()->unique();
        $this->userFavoriteMerchantIds = $favorites->pluck('merchant_product_id')->filter()->unique();
    }

    /**
     * Load general settings (cached 24 hours)
     */
    private function loadGeneralSettings(): void
    {
        $this->generalSettings = Cache::remember(
            'generalsettings',
            86400,
            fn() => DB::table('generalsettings')->first()
        );
    }

    /**
     * Build ProductCardDTOs from MerchantProduct collection
     *
     * @param Collection|array $merchants MerchantProduct collection (must have eager-loaded relations)
     * @return Collection<ProductCardDTO>
     */
    public function buildCardsFromMerchants($merchants): Collection
    {
        $this->initialize();

        return collect($merchants)->map(
            fn($merchant) => ProductCardDTO::fromMerchantProduct(
                $merchant,
                $this->userFavoriteProductIds,
                $this->userFavoriteMerchantIds
            )
        );
    }

    /**
     * Build ProductCardDTOs from paginated MerchantProduct results
     * Returns a LengthAwarePaginator with DTOs instead of models
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function buildCardsFromPaginator($paginator): \Illuminate\Pagination\LengthAwarePaginator
    {
        $this->initialize();

        $dtos = $paginator->getCollection()->map(
            fn($merchant) => ProductCardDTO::fromMerchantProduct(
                $merchant,
                $this->userFavoriteProductIds,
                $this->userFavoriteMerchantIds
            )
        );

        return $paginator->setCollection($dtos);
    }

    /**
     * Build ProductCardDTOs from Product collection
     *
     * @param Collection|array $products Product collection (must have eager-loaded relations)
     * @return Collection<ProductCardDTO>
     */
    public function buildCardsFromProducts($products): Collection
    {
        $this->initialize();

        return collect($products)->map(function ($product) {
            $merchant = $product->merchantProducts?->first();
            return ProductCardDTO::fromProduct(
                $product,
                $merchant,
                $this->userFavoriteProductIds,
                $this->userFavoriteMerchantIds
            );
        });
    }

    /**
     * Build a single ProductCardDTO from MerchantProduct
     */
    public function buildCardFromMerchant(MerchantProduct $merchant): ProductCardDTO
    {
        $this->initialize();

        return ProductCardDTO::fromMerchantProduct(
            $merchant,
            $this->userFavoriteProductIds,
            $this->userFavoriteMerchantIds
        );
    }

    /**
     * Invalidate favorite cache for a user
     */
    public static function invalidateFavoriteCache(int $userId): void
    {
        Cache::forget("user_favorites_{$userId}");
    }
}
