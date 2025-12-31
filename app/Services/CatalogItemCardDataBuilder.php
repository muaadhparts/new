<?php

namespace App\Services;

use App\DataTransferObjects\CatalogItemCardDTO;
use App\Models\MerchantItem;
use App\Models\CatalogItem;
use App\Models\Favorite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * CatalogItemCardDataBuilder
 *
 * Centralized service for building catalog item card data with:
 * - Consistent eager loading for all catalog item views (list/home/search)
 * - Pre-loaded favorite data (one query, cached)
 * - DTOs with all pre-computed values (Blade only prints)
 *
 * Usage in Controller:
 *   $builder = app(CatalogItemCardDataBuilder::class);
 *   $cards = $builder->buildCardsFromMerchants($merchants);
 *   return view('...', ['cards' => $cards]);
 */
class CatalogItemCardDataBuilder
{
    private ?Collection $userFavoriteCatalogItemIds = null;
    private ?Collection $userFavoriteMerchantIds = null;
    private ?object $muaadhSettings = null;
    private bool $initialized = false;

    /**
     * Standard eager loading for MerchantItem queries
     */
    public const MERCHANT_ITEM_RELATIONS = [
        'user:id,is_merchant,name,shop_name,shop_name_ar,email',
        'qualityBrand:id,name_en,name_ar,logo',
        'catalogItem' => [
            'brand:id,name,name_ar,photo',
        ],
    ];

    /**
     * Standard eager loading for CatalogItem queries
     */
    public const CATALOG_ITEM_RELATIONS = [
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
        $this->loadMuaadhSettings();
        $this->initialized = true;

        return $this;
    }

    /**
     * Apply standard eager loading to a MerchantItem query
     */
    public function applyMerchantItemEagerLoading(Builder $query): Builder
    {
        return $query->with([
            'user:id,is_merchant,name,shop_name,shop_name_ar,email',
            'qualityBrand:id,name_en,name_ar,logo',
            'catalogItem' => function ($q) {
                $q->with('brand:id,name,name_ar,photo')
                    ->withCount('catalogReviews')
                    ->withAvg('catalogReviews', 'rating');
            },
        ]);
    }

    /**
     * Apply standard eager loading to a CatalogItem query
     */
    public function applyCatalogItemEagerLoading(Builder $query): Builder
    {
        return $query->with([
            'brand:id,name,name_ar,photo',
            'merchantItems' => function ($q) {
                $q->where('status', 1)
                    ->with([
                        'user:id,is_merchant,name,shop_name,shop_name_ar,email',
                        'qualityBrand:id,name_en,name_ar,logo',
                    ])
                    ->orderBy('price');
            },
        ])->withCount('catalogReviews')->withAvg('catalogReviews', 'rating');
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
            'favoriteCatalogItemIds' => $this->userFavoriteCatalogItemIds,
            'favoriteMerchantIds' => $this->userFavoriteMerchantIds,
            'favoriteCount' => $this->userFavoriteCatalogItemIds->count(),
        ], $additionalData);
    }

    /**
     * Check if a catalog item is in favorites (use in Blade)
     */
    public function isInFavorites(int $catalogItemId): bool
    {
        $this->initialize();
        return $this->userFavoriteCatalogItemIds->contains($catalogItemId);
    }

    /**
     * Check if a merchant item is in favorites (use in Blade)
     */
    public function isMerchantInFavorites(int $merchantItemId): bool
    {
        $this->initialize();
        return $this->userFavoriteMerchantIds->contains($merchantItemId);
    }

    /**
     * Get favorite catalog item IDs
     */
    public function getFavoriteCatalogItemIds(): Collection
    {
        $this->initialize();
        return $this->userFavoriteCatalogItemIds;
    }

    /**
     * Get favorite merchant item IDs
     */
    public function getFavoriteMerchantIds(): Collection
    {
        $this->initialize();
        return $this->userFavoriteMerchantIds;
    }

    /**
     * Get cached general settings
     */
    public function getMuaadhSettings(): object
    {
        $this->initialize();
        return $this->muaadhSettings;
    }

    /**
     * Load user favorite data (one query, cached 5 minutes)
     */
    private function loadFavoriteData(): void
    {
        if (!Auth::check()) {
            $this->userFavoriteCatalogItemIds = collect();
            $this->userFavoriteMerchantIds = collect();
            return;
        }

        $userId = Auth::id();

        $favorites = Cache::remember(
            "user_favorites_{$userId}",
            300,
            fn() => Favorite::where('user_id', $userId)
                ->select(['catalog_item_id', 'merchant_item_id'])
                ->get()
        );

        $this->userFavoriteCatalogItemIds = $favorites->pluck('catalog_item_id')->filter()->unique();
        $this->userFavoriteMerchantIds = $favorites->pluck('merchant_item_id')->filter()->unique();
    }

    /**
     * Load muaadh settings (cached 24 hours)
     */
    private function loadMuaadhSettings(): void
    {
        $this->muaadhSettings = Cache::remember(
            'muaadhsettings',
            86400,
            fn() => DB::table('muaadhsettings')->first()
        );
    }

    /**
     * Build CatalogItemCardDTOs from MerchantItem collection
     *
     * @param Collection|array $merchants MerchantItem collection (must have eager-loaded relations)
     * @return Collection<CatalogItemCardDTO>
     */
    public function buildCardsFromMerchants($merchants): Collection
    {
        $this->initialize();

        return collect($merchants)->map(
            fn($merchant) => CatalogItemCardDTO::fromMerchantItem(
                $merchant,
                $this->userFavoriteCatalogItemIds,
                $this->userFavoriteMerchantIds
            )
        );
    }

    /**
     * Build CatalogItemCardDTOs from paginated MerchantItem results
     * Returns a LengthAwarePaginator with DTOs instead of models
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function buildCardsFromPaginator($paginator): \Illuminate\Pagination\LengthAwarePaginator
    {
        $this->initialize();

        $dtos = $paginator->getCollection()->map(
            fn($merchant) => CatalogItemCardDTO::fromMerchantItem(
                $merchant,
                $this->userFavoriteCatalogItemIds,
                $this->userFavoriteMerchantIds
            )
        );

        return $paginator->setCollection($dtos);
    }

    /**
     * Build CatalogItemCardDTOs from CatalogItem collection
     *
     * @param Collection|array $catalogItems CatalogItem collection (must have eager-loaded relations)
     * @return Collection<CatalogItemCardDTO>
     */
    public function buildCardsFromCatalogItems($catalogItems): Collection
    {
        $this->initialize();

        return collect($catalogItems)->map(function ($catalogItem) {
            $merchant = $catalogItem->merchantItems?->first();
            return CatalogItemCardDTO::fromCatalogItem(
                $catalogItem,
                $merchant,
                $this->userFavoriteCatalogItemIds,
                $this->userFavoriteMerchantIds
            );
        });
    }

    /**
     * Build a single CatalogItemCardDTO from MerchantItem
     */
    public function buildCardFromMerchant(MerchantItem $merchant): CatalogItemCardDTO
    {
        $this->initialize();

        return CatalogItemCardDTO::fromMerchantItem(
            $merchant,
            $this->userFavoriteCatalogItemIds,
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
