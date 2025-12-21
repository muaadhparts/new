<?php

namespace App\Services;

use App\DataTransferObjects\ProductCardDTO;
use App\Models\MerchantProduct;
use App\Models\Product;
use App\Models\Wishlist;
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
 * - Pre-loaded wishlist data (one query, cached)
 * - DTOs with all pre-computed values (Blade only prints)
 *
 * Usage in Controller:
 *   $builder = app(ProductCardDataBuilder::class);
 *   $cards = $builder->buildCardsFromMerchants($merchants);
 *   return view('...', ['cards' => $cards]);
 */
class ProductCardDataBuilder
{
    private ?Collection $userWishlistProductIds = null;
    private ?Collection $userWishlistMerchantIds = null;
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
     * Initialize the builder - loads wishlist and settings
     */
    public function initialize(): self
    {
        if ($this->initialized) {
            return $this;
        }

        $this->loadWishlistData();
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
            'wishlistProductIds' => $this->userWishlistProductIds,
            'wishlistMerchantIds' => $this->userWishlistMerchantIds,
            'wishlistCount' => $this->userWishlistProductIds->count(),
        ], $additionalData);
    }

    /**
     * Check if a product is in wishlist (use in Blade)
     */
    public function isInWishlist(int $productId): bool
    {
        $this->initialize();
        return $this->userWishlistProductIds->contains($productId);
    }

    /**
     * Check if a merchant product is in wishlist (use in Blade)
     */
    public function isMerchantInWishlist(int $merchantProductId): bool
    {
        $this->initialize();
        return $this->userWishlistMerchantIds->contains($merchantProductId);
    }

    /**
     * Get wishlist product IDs
     */
    public function getWishlistProductIds(): Collection
    {
        $this->initialize();
        return $this->userWishlistProductIds;
    }

    /**
     * Get wishlist merchant product IDs
     */
    public function getWishlistMerchantIds(): Collection
    {
        $this->initialize();
        return $this->userWishlistMerchantIds;
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
     * Load user wishlist data (one query, cached 5 minutes)
     */
    private function loadWishlistData(): void
    {
        if (!Auth::check()) {
            $this->userWishlistProductIds = collect();
            $this->userWishlistMerchantIds = collect();
            return;
        }

        $userId = Auth::id();

        $wishlists = Cache::remember(
            "user_wishlists_{$userId}",
            300,
            fn() => Wishlist::where('user_id', $userId)
                ->select(['product_id', 'merchant_product_id'])
                ->get()
        );

        $this->userWishlistProductIds = $wishlists->pluck('product_id')->filter()->unique();
        $this->userWishlistMerchantIds = $wishlists->pluck('merchant_product_id')->filter()->unique();
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
                $this->userWishlistProductIds,
                $this->userWishlistMerchantIds
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

        // Build DTOs only for the current page items (12 or less)
        $dtos = $paginator->getCollection()->map(
            fn($merchant) => ProductCardDTO::fromMerchantProduct(
                $merchant,
                $this->userWishlistProductIds,
                $this->userWishlistMerchantIds
            )
        );

        // Return new paginator with DTOs, preserving pagination metadata
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
                $this->userWishlistProductIds,
                $this->userWishlistMerchantIds
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
            $this->userWishlistProductIds,
            $this->userWishlistMerchantIds
        );
    }

    /**
     * Invalidate wishlist cache for a user
     */
    public static function invalidateWishlistCache(int $userId): void
    {
        Cache::forget("user_wishlists_{$userId}");
    }
}
