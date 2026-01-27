<?php

namespace App\Domain\Catalog\Models;

use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Merchant\Models\MerchantPhoto;
use App\Domain\Merchant\Models\MerchantCommission;
use App\Domain\Commerce\Models\FavoriteSeller;
use App\Domain\Commerce\Models\BuyerNote;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * CatalogItem Model - Main product catalog
 *
 * Domain: Catalog
 * Table: catalog_items
 *
 * CatalogItem stores only catalogue-level attributes.
 * Merchant-specific data (price, stock, policy, status, etc.) is in MerchantItem.
 *
 * @property int $id
 * @property string $part_number
 * @property string|null $label_en
 * @property string|null $label_ar
 * @property string|null $attributes
 * @property string|null $name
 * @property string|null $slug
 * @property string|null $photo
 * @property string|null $thumbnail
 * @property float|null $weight
 * @property int $views
 */
class CatalogItem extends Model
{
    use HasFactory;

    protected $table = 'catalog_items';

    protected $fillable = [
        'part_number',
        'label_en',
        'label_ar',
        'attributes',
        'name',
        'slug',
        'photo',
        'thumbnail',
        'weight',
        'views',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'views' => 'integer',
    ];

    /**
     * Selectable columns for listing catalog items (catalog-level only).
     */
    public $selectable = [
        'id', 'name', 'slug', 'thumbnail', 'attributes', 'weight'
    ];

    /* =========================================================================
     |  RELATIONSHIPS
     | ========================================================================= */

    /**
     * Vehicle fitments - which brands/vehicles this part fits.
     */
    public function fitments(): HasMany
    {
        return $this->hasMany(CatalogItemFitment::class, 'catalog_item_id');
    }

    /**
     * Merchant listings for this catalog item (each row is one seller).
     */
    public function merchantItems(): HasMany
    {
        return $this->hasMany(MerchantItem::class, 'catalog_item_id');
    }

    /**
     * Active merchant listings only (status=1, approved merchant).
     */
    public function activeMerchantItems(): HasMany
    {
        return $this->hasMany(MerchantItem::class, 'catalog_item_id')
            ->where('status', 1)
            ->whereHas('user', fn($q) => $q->where('is_merchant', 2));
    }

    /**
     * Get all brands this catalog item fits (via fitments).
     */
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'catalog_item_fitments', 'catalog_item_id', 'brand_id');
    }

    /**
     * Get all catalogs this catalog item fits (via fitments).
     */
    public function catalogs(): BelongsToMany
    {
        return $this->belongsToMany(Catalog::class, 'catalog_item_fitments', 'catalog_item_id', 'catalog_id');
    }

    /**
     * Reviews for this catalog item.
     */
    public function catalogReviews(): HasMany
    {
        return $this->hasMany(CatalogReview::class, 'catalog_item_id');
    }

    /**
     * Get all merchant photos for this catalog item (via merchant_items).
     */
    public function merchantPhotos(): HasManyThrough
    {
        return $this->hasManyThrough(
            MerchantPhoto::class,
            MerchantItem::class,
            'catalog_item_id',
            'merchant_item_id',
            'id',
            'id'
        )->where('merchant_photos.status', 1)
         ->orderBy('merchant_photos.sort_order');
    }

    /**
     * Favorites relation.
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(FavoriteSeller::class, 'catalog_item_id');
    }

    /**
     * Single favorite (legacy).
     */
    public function favorite()
    {
        return $this->belongsTo(FavoriteSeller::class)->withDefault();
    }

    /**
     * Buyer notes for this catalog item.
     */
    public function buyerNotes(): HasMany
    {
        return $this->hasMany(BuyerNote::class, 'catalog_item_id');
    }

    /**
     * Abuse flags for this catalog item.
     */
    public function abuseFlags(): HasMany
    {
        return $this->hasMany(AbuseFlag::class, 'catalog_item_id');
    }

    /* =========================================================================
     |  SCOPES
     | ========================================================================= */

    /**
     * Home catalog items: only items that have at least one active merchant listing.
     */
    public function scopeHome(Builder $query): Builder
    {
        return $query
            ->whereHas('merchantItems', fn ($q) => $q->where('status', 1))
            ->select($this->selectable)
            ->latest('id');
    }

    /**
     * Scope for filtering catalog items by merchant listing status.
     */
    public function scopeStatus(Builder $query, int $status = 1): Builder
    {
        return $query->whereHas('merchantItems', function ($q) use ($status) {
            $q->where('status', $status);
        });
    }

    /**
     * Scope: Only catalog items with at least one active merchant listing.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('merchantItems', fn($q) => $q->where('status', 1));
    }

    /**
     * Scope: Catalog items for a specific merchant.
     */
    public function scopeForMerchant(Builder $query, int $merchantId): Builder
    {
        return $query->whereHas('merchantItems', fn($q) => $q->where('user_id', $merchantId));
    }

    /**
     * Scope: Catalog items for a specific brand (via fitments).
     */
    public function scopeForBrand(Builder $query, int $brandId): Builder
    {
        return $query->whereHas('fitments', fn($q) => $q->where('brand_id', $brandId));
    }

    /**
     * Scope: Catalog items for a specific catalog (via fitments).
     */
    public function scopeForCatalog(Builder $query, int $catalogId): Builder
    {
        return $query->whereHas('fitments', fn($q) => $q->where('catalog_id', $catalogId));
    }

    /**
     * Scope: Search catalog items by name, part number, or attributes.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'LIKE', "%{$term}%")
              ->orWhere('part_number', 'LIKE', "%{$term}%")
              ->orWhere('attributes', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Scope: CatalogItem-first query with aggregated offer data.
     */
    public function scopeWithOffersData(Builder $query): Builder
    {
        // Subquery for counting active offers
        $offersCountSubquery = MerchantItem::selectRaw('COUNT(*)')
            ->whereColumn('merchant_items.catalog_item_id', 'catalog_items.id')
            ->where('merchant_items.status', 1)
            ->whereExists(function ($q) {
                $q->selectRaw(1)
                    ->from('users')
                    ->whereColumn('users.id', 'merchant_items.user_id')
                    ->where('users.is_merchant', 2);
            });

        // Subquery for lowest price
        $lowestPriceSubquery = MerchantItem::selectRaw('MIN(merchant_items.price)')
            ->whereColumn('merchant_items.catalog_item_id', 'catalog_items.id')
            ->where('merchant_items.status', 1)
            ->whereExists(function ($q) {
                $q->selectRaw(1)
                    ->from('users')
                    ->whereColumn('users.id', 'merchant_items.user_id')
                    ->where('users.is_merchant', 2);
            });

        return $query
            ->selectRaw('catalog_items.*')
            ->selectSub($offersCountSubquery, 'offers_count')
            ->selectSub($lowestPriceSubquery, 'lowest_price')
            ->whereExists(function ($q) {
                $q->selectRaw(1)
                    ->from('merchant_items')
                    ->whereColumn('merchant_items.catalog_item_id', 'catalog_items.id')
                    ->where('merchant_items.status', 1)
                    ->whereExists(function ($q2) {
                        $q2->selectRaw(1)
                            ->from('users')
                            ->whereColumn('users.id', 'merchant_items.user_id')
                            ->where('users.is_merchant', 2);
                    });
            });
    }

    /**
     * Scope: Load the best (lowest price) merchant item as eager-loaded relation.
     */
    public function scopeWithBestOffer(Builder $query): Builder
    {
        return $query->with(['merchantItems' => function ($q) {
            $q->where('status', 1)
                ->whereHas('user', fn($u) => $u->where('is_merchant', 2))
                ->with([
                    'user:id,is_merchant,name,shop_name,shop_name_ar,email,merchant_logo',
                    'qualityBrand:id,code,name_en,name_ar,logo',
                    'merchantBranch:id,warehouse_name,branch_name,city_id',
                    'merchantBranch.city:id,city_name',
                ])
                ->orderByRaw('CASE WHEN stock > 0 THEN 0 ELSE 1 END')
                ->orderBy('price', 'asc');
        }])
        ->with('fitments.brand')
        ->withCount('catalogReviews')
        ->withAvg('catalogReviews', 'rating');
    }

    /**
     * Scope to eager load merchant items with optimal relations.
     */
    public function scopeWithBestMerchant(Builder $query): Builder
    {
        return $query->with(['merchantItems' => function ($q) {
            $q->where('status', 1)
              ->with(['user:id,is_merchant,shop_name,shop_name_ar', 'qualityBrand:id,name_en,name_ar'])
              ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
              ->orderBy('price');
        }]);
    }

    /* =========================================================================
     |  ACCESSORS
     | ========================================================================= */

    /**
     * Get localized catalog item name based on current locale.
     */
    public function getLocalizedNameAttribute(): string
    {
        $isAr = app()->getLocale() === 'ar';
        $labelAr = trim((string)($this->label_ar ?? ''));
        $labelEn = trim((string)($this->label_en ?? ''));
        $name = trim((string)($this->name ?? ''));

        if ($isAr) {
            return $labelAr !== '' ? $labelAr : ($labelEn !== '' ? $labelEn : $name);
        }
        return $labelEn !== '' ? $labelEn : ($labelAr !== '' ? $labelAr : $name);
    }

    /**
     * Get best merchant item from EAGER LOADED collection (avoids N+1).
     */
    public function getBestMerchantItemAttribute(): ?MerchantItem
    {
        // Enforce eager loading in local environment
        if (!$this->relationLoaded('merchantItems')) {
            if (app()->environment('local')) {
                throw new \LogicException(
                    "N+1 Query Detected: CatalogItem #{$this->id} accessed 'best_merchant_item' without eager loading. " .
                    "Use ->withBestMerchant() or ->with('merchantItems') in your query."
                );
            }

            // Production fallback
            \Log::warning("N+1 Query: CatalogItem #{$this->id} best_merchant_item accessed without eager loading");

            return $this->merchantItems()
                ->where('status', 1)
                ->whereHas('user', fn($u) => $u->where('is_merchant', 2))
                ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
                ->orderBy('price')
                ->first();
        }

        // Filter and sort from eager loaded collection
        return $this->merchantItems
            ->filter(function ($mi) {
                if ($mi->status != 1) return false;
                if ($mi->relationLoaded('user') && $mi->user) {
                    return $mi->user->is_merchant == 2;
                }
                return true;
            })
            ->sortBy([
                fn ($mi) => ($mi->stock ?? 0) > 0 ? 0 : 1,
                fn ($mi) => (float) $mi->price,
            ])
            ->first();
    }

    /**
     * Get count of active offers.
     */
    public function getOffersCountAttribute(): int
    {
        // 1. If withOffersData scope was used, use the pre-computed value
        if (isset($this->attributes['offers_count'])) {
            return (int) $this->attributes['offers_count'];
        }

        // 2. If merchantItems relation is loaded, count from it
        if ($this->relationLoaded('merchantItems')) {
            return $this->merchantItems
                ->filter(fn($mi) => $mi->status == 1)
                ->count();
        }

        // 3. Fallback: query database
        return $this->activeMerchantItems()->count();
    }

    /**
     * Get lowest price from active offers.
     */
    public function getLowestPriceAttribute(): ?float
    {
        if (isset($this->attributes['lowest_price'])) {
            return (float) $this->attributes['lowest_price'];
        }
        return $this->best_merchant_item?->price;
    }

    /**
     * Get photo URL with fallback.
     */
    public function getPhotoUrlAttribute(): string
    {
        if (empty($this->photo)) {
            return asset('assets/images/noimage.png');
        }
        if (filter_var($this->photo, FILTER_VALIDATE_URL)) {
            return $this->photo;
        }
        return asset($this->photo);
    }

    /**
     * Get thumbnail URL with fallback.
     */
    public function getThumbnailUrlAttribute(): string
    {
        if (empty($this->thumbnail)) {
            return $this->photo_url;
        }
        if (filter_var($this->thumbnail, FILTER_VALIDATE_URL)) {
            return $this->thumbnail;
        }
        return asset($this->thumbnail);
    }

    /* =========================================================================
     |  HELPER METHODS
     | ========================================================================= */

    /**
     * Get active offers count (alias for accessor).
     */
    public function getActiveOffersCount(): int
    {
        return $this->offers_count;
    }

    /**
     * Check if this catalog item has any active offers.
     */
    public function hasActiveOffers(): bool
    {
        return $this->offers_count > 0;
    }

    /**
     * Get average rating for this catalog item.
     */
    public function getAverageRating(): float
    {
        if ($this->relationLoaded('catalogReviews')) {
            return round($this->catalogReviews->avg('rating') ?? 0, 1);
        }
        return round($this->catalogReviews()->avg('rating') ?? 0, 1);
    }

    /**
     * Get merchant item for a specific merchant.
     */
    public function getMerchantItem(int $merchantId): ?MerchantItem
    {
        if ($this->relationLoaded('merchantItems')) {
            return $this->merchantItems
                ->where('user_id', $merchantId)
                ->where('status', 1)
                ->first();
        }
        return $this->activeMerchantItems()
            ->where('user_id', $merchantId)
            ->first();
    }

    /**
     * Get merchant item ID for a specific merchant.
     */
    public function getMerchantItemId(int $merchantId): ?int
    {
        $merchant = $this->getMerchantItem($merchantId);
        return $merchant?->id;
    }

    /**
     * Get merchant photos filtered by merchant user_id.
     */
    public function merchantPhotosForMerchant(?int $userId, int $limit = 3)
    {
        if (!$userId) {
            return collect();
        }

        $merchantItemIds = MerchantItem::where('catalog_item_id', $this->id)
            ->where('user_id', $userId)
            ->where('status', 1)
            ->pluck('id');

        if ($merchantItemIds->isEmpty()) {
            return collect();
        }

        return MerchantPhoto::whereIn('merchant_item_id', $merchantItemIds)
            ->where('status', 1)
            ->orderBy('sort_order')
            ->take($limit)
            ->get();
    }

    /**
     * Resolve active merchant listing for this catalog item.
     */
    public function activeMerchant(?int $userId = null): ?MerchantItem
    {
        return $this->merchantItems()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->where('status', 1)
            ->first();
    }

    /**
     * Get best merchant for this catalog item.
     */
    public function bestMerchant(): ?MerchantItem
    {
        return $this->merchantItems()
            ->where('status', 1)
            ->whereHas('user', fn($q) => $q->where('is_merchant', 2))
            ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
            ->orderBy('price')
            ->first();
    }

    /**
     * Get the catalog item URL.
     */
    public function getCatalogItemUrl(): string
    {
        if ($this->part_number) {
            return route('front.part-result', $this->part_number);
        }
        return '#';
    }

    /**
     * UI helper (admin): show first active merchant badge/link.
     */
    public function checkMerchant(): string
    {
        $mi = $this->merchantItems()->where('status', 1)->first();
        return $mi
            ? '<small class="ml-2"> ' . __("MERCHANT") . ': <a href="' . route('operator-merchant-show', $mi->user_id) . '" target="_blank">' . optional($mi->user)->shop_name . '</a></small>'
            : '';
    }

    /**
     * Show truncated localized name for display.
     */
    public function showName()
    {
        $displayName = $this->localized_name;
        return mb_strlen($displayName, 'UTF-8') > 50 ? mb_substr($displayName, 0, 50, 'UTF-8') . '...' : $displayName;
    }

    /**
     * Out of stock if no active merchant listing OR that listing has zero stock.
     */
    public function emptyStock(?int $userId = null): bool
    {
        if ($userId) {
            $mi = $this->activeMerchant($userId);
            return !$mi || (string) $mi->stock === "0";
        }

        $hasStock = $this->merchantItems()
            ->where('status', 1)
            ->where('stock', '>', 0)
            ->exists();

        return !$hasStock;
    }

    /* =========================================================================
     |  PRICE METHODS (Merchant-aware)
     | ========================================================================= */

    /**
     * Legacy compatibility (base price incl. commission) for first active merchant.
     */
    public function merchantPrice(?int $userId = null)
    {
        $merchant = $this->activeMerchant($userId);
        if (!$merchant) {
            return null;
        }

        $price = (float) $merchant->price;

        $commission = $this->getMerchantCommissionFor($merchant->user_id);
        if ($commission && $commission->is_active) {
            $price = $price + (float) ($commission->fixed_commission ?? 0) + ($price * (float) ($commission->percentage_commission ?? 0) / 100);
        }

        return $price;
    }

    /**
     * Merchant-aware size/options/commission price.
     */
    public function merchantSizePrice(?int $userId = null)
    {
        $mi = $this->activeMerchant($userId);
        return $mi ? $mi->merchantSizePrice() : null;
    }

    /**
     * Convert merchant-aware price to session currency and format.
     */
    public function setCurrency()
    {
        $rawPrice = $this->merchantSizePrice();
        if ($rawPrice === null) {
            return 0;
        }
        return monetaryUnit()->convertAndFormat($rawPrice);
    }

    /**
     * Show formatted price (session currency) for a merchant-aware price.
     */
    public function showPrice(?int $userId = null)
    {
        $rawPrice = $this->merchantSizePrice($userId);
        if ($rawPrice === null) {
            return 0;
        }
        return monetaryUnit()->convertAndFormat($rawPrice);
    }

    /**
     * Show admin-formatted price (default currency).
     */
    public function adminShowPrice(?int $userId = null)
    {
        $rawPrice = $this->merchantSizePrice($userId);
        if ($rawPrice === null) {
            return 0;
        }
        return monetaryUnit()->formatBase($rawPrice);
    }

    /**
     * Show previous price (merchant previous_price + commission).
     */
    public function showPreviousPrice(?int $userId = null)
    {
        $mi = $this->activeMerchant($userId);
        if (!$mi || !$mi->previous_price) {
            return 0;
        }

        $price = (float) $mi->previous_price;

        $commission = $this->getMerchantCommissionFor($mi->user_id);
        if ($commission && $commission->is_active) {
            $price = $price + (float) ($commission->fixed_commission ?? 0) + ($price * (float) ($commission->percentage_commission ?? 0) / 100);
        }

        return monetaryUnit()->convertAndFormat($price);
    }

    /**
     * Off percentage based on current vs previous price (both merchant-aware).
     */
    public function offPercentage(?int $userId = null)
    {
        $mi = $this->activeMerchant($userId);
        if (!$mi || !$mi->previous_price) {
            return 0;
        }

        $current = $this->merchantSizePrice($userId);
        if ($current === null) {
            return 0;
        }

        $prev = (float) $mi->previous_price;

        $commission = $this->getMerchantCommissionFor($mi->user_id);
        if ($commission && $commission->is_active) {
            $prev = $prev + (float) ($commission->fixed_commission ?? 0) + ($prev * (float) ($commission->percentage_commission ?? 0) / 100);
        }

        if ($prev <= 0) {
            return 0;
        }

        $percentage = ((float) $prev - (float) $current) * 100 / (float) $prev;
        return $percentage;
    }

    /**
     * Get commission settings for a specific merchant user.
     */
    protected function getMerchantCommissionFor(?int $userId)
    {
        if (!$userId) {
            return null;
        }

        return cache()->remember(
            "merchant_commission_{$userId}",
            now()->addHours(1),
            fn () => MerchantCommission::where('user_id', $userId)->first()
        );
    }

    /**
     * Convert a raw price (base currency) into current session currency, formatted.
     */
    public static function convertPrice($price)
    {
        return monetaryUnit()->convertAndFormat((float) ($price ?? 0));
    }

    public static function merchantConvertPrice($price)
    {
        return monetaryUnit()->convertAndFormat((float) ($price ?? 0));
    }

    public static function merchantConvertWithoutCurrencyPrice($price)
    {
        return monetaryUnit()->convert((float) ($price ?? 0));
    }

    /* =========================================================================
     |  MERCHANT-AWARE HELPERS
     | ========================================================================= */

    /**
     * Get merchant-aware stock quantity.
     */
    public function merchantSizeStock(?int $userId = null)
    {
        $mi = $this->activeMerchant($userId);
        return $mi ? $mi->stock : 0;
    }

    /**
     * Get merchant-specific catalogItem condition.
     */
    public function getItemCondition(?int $userId = null)
    {
        $mi = $this->activeMerchant($userId);
        return $mi ? $mi->item_condition : 0;
    }

    /**
     * Get merchant-specific minimum quantity.
     */
    public function getMinimumQty(?int $userId = null)
    {
        $mi = $this->activeMerchant($userId);
        return $mi ? $mi->minimum_qty : null;
    }

    /**
     * Get merchant-specific stock check setting.
     */
    public function getStockCheck(?int $userId = null)
    {
        $mi = $this->activeMerchant($userId);
        return $mi ? $mi->stock_check : 0;
    }

    /**
     * Get merchant-specific previous price from merchant item.
     */
    public function getMerchantPreviousPrice(?int $userId = null)
    {
        $mi = $this->activeMerchant($userId);
        return $mi ? $mi->previous_price : null;
    }

    /**
     * Get merchant-specific ship from merchant item.
     */
    public function getMerchantShip(?int $userId = null)
    {
        $mi = $this->activeMerchant($userId);
        return $mi ? $mi->ship : null;
    }

    /**
     * Get merchant-specific details from merchant item.
     */
    public function getMerchantDetails(?int $userId = null): ?string
    {
        $mi = $this->activeMerchant($userId);
        return $mi?->details;
    }

    /**
     * Get merchant-specific policy from merchant item.
     */
    public function getMerchantPolicy(?int $userId = null): ?string
    {
        $mi = $this->activeMerchant($userId);
        return $mi?->policy;
    }

    public function is_decimal($val)
    {
        return is_numeric($val) && floor($val) != $val;
    }

    /* =========================================================================
     |  COLLECTION FILTERS (Legacy)
     | ========================================================================= */

    /**
     * Filter a catalog item collection to those having an active merchant listing.
     */
    public static function filterCatalogItems($collection)
    {
        foreach ($collection as $key => $catalogItem) {
            $merchantPrice = $catalogItem->merchantSizePrice();
            if ($merchantPrice === null) {
                unset($collection[$key]);
                continue;
            }
            if (isset($_GET['max']) && $merchantPrice >= (float) $_GET['max']) {
                unset($collection[$key]);
                continue;
            }
            $catalogItem->price = $merchantPrice;
        }
        return $collection;
    }

    /* =========================================================================
     |  MOBILE API SECTION (merchant-aware)
     | ========================================================================= */

    public function ApishowPrice(?int $userId = null)
    {
        $rawPrice = $this->merchantSizePrice($userId);
        if ($rawPrice === null) {
            return 0;
        }
        $converted = monetaryUnit()->convert($rawPrice);
        return \PriceHelper::apishowPrice($converted);
    }

    public function ApishowDetailsPrice(?int $userId = null)
    {
        $rawPrice = $this->merchantSizePrice($userId);
        if ($rawPrice === null) {
            return 0;
        }
        $converted = monetaryUnit()->convert($rawPrice);
        return \PriceHelper::apishowPrice($converted);
    }

    public function ApishowPreviousPrice(?int $userId = null)
    {
        $mi = $this->activeMerchant($userId);
        if (!$mi || !$mi->previous_price) {
            return 0;
        }

        $price = (float) $mi->previous_price;

        $commission = $this->getMerchantCommissionFor($mi->user_id);
        if ($commission && $commission->is_active) {
            $price = $price + (float) ($commission->fixed_commission ?? 0) + ($price * (float) ($commission->percentage_commission ?? 0) / 100);
        }

        $converted = monetaryUnit()->convert($price);
        return \PriceHelper::apishowPrice($converted);
    }
}
