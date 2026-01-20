<?php

namespace App\Models;

use App\Models\MonetaryUnit;
use App\Models\MerchantItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CatalogItem extends Model
{
    protected $table = 'catalog_items';

    /**
     * CatalogItem model stores only catalogue-level attributes.
     * Merchant-specific data (price, stock, policy, features, status, etc.) is in MerchantItem.
     *
     * Actual catalog_items columns:
     * id, part_number, weight, label_en, label_ar, attributes, name, slug,
     * photo, thumbnail, views, tags, is_meta, meta_tag, meta_description, youtube,
     * measure, hot, latest, sale, cross_items
     *
     * Vehicle fitment (brand compatibility) is stored in catalog_item_fitments table.
     * One catalog_item can fit multiple vehicle brands (Toyota, Nissan, etc.)
     */
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
        'tags',
        'is_meta',
        'meta_tag',
        'meta_description',
        'youtube',
        'measure',
        'hot',
        'latest',
        'sale',
        'cross_items',
    ];

    /**
     * Selectable columns for listing catalog items (catalog-level only).
     */
    public $selectable = [
        'id', 'name', 'slug', 'thumbnail', 'attributes', 'weight'
    ];

    /* =========================================================================
     |  Scopes
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
     * Redirects status check to merchant_items.status.
     */
    public function scopeStatus(Builder $query, int $status = 1): Builder
    {
        return $query->whereHas('merchantItems', function ($q) use ($status) {
            $q->where('status', $status);
        });
    }

    /* =========================================================================
     |  Relations
     | ========================================================================= */

    /**
     * Vehicle fitments - which brands/vehicles this part fits.
     * One catalog_item can have multiple fitments (fits Toyota AND Lexus)
     */
    public function fitments()
    {
        return $this->hasMany(CatalogItemFitment::class, 'catalog_item_id');
    }

    /**
     * Get all brands this catalog item fits (via fitments)
     */
    public function brands()
    {
        return $this->belongsToMany(Brand::class, 'catalog_item_fitments', 'catalog_item_id', 'brand_id');
    }

    /**
     * Merchant listings for this catalog item (each row is one seller).
     */
    public function merchantItems()
    {
        return $this->hasMany(MerchantItem::class, 'catalog_item_id');
    }

    public function favorite()
    {
        return $this->belongsTo('App\Models\FavoriteSeller')->withDefault();
    }

    /**
     * Get all merchant photos for this catalog item (via merchant_items).
     * Photos are linked to merchant_items, not directly to catalog_items.
     */
    public function merchantPhotos()
    {
        return $this->hasManyThrough(
            MerchantPhoto::class,
            MerchantItem::class,
            'catalog_item_id',    // FK on merchant_items
            'merchant_item_id',   // FK on merchant_photos
            'id',                 // Local key on catalog_items
            'id'                  // Local key on merchant_items
        )->where('merchant_photos.status', 1)
         ->orderBy('merchant_photos.sort_order');
    }

    /**
     * Get merchant photos filtered by merchant user_id.
     * Use this for merchant-specific photo display.
     *
     * Since merchant_photos links to merchant_items (not directly to catalog_items),
     * we find merchant_items for this catalog_item + user, then get their photos.
     *
     * @param int|null $userId Merchant user ID
     * @param int $limit Max number of photos
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function merchantPhotosForMerchant(?int $userId, int $limit = 3)
    {
        if (!$userId) {
            return collect();
        }

        // Get merchant_item IDs for this catalog_item + user
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

    public function catalogReviews()
    {
        return $this->hasMany('App\Models\CatalogReview', 'catalog_item_id');
    }

    public function favorites()
    {
        return $this->hasMany('App\Models\FavoriteSeller', 'catalog_item_id');
    }

    public function buyerNotes()
    {
        return $this->hasMany('App\Models\BuyerNote', 'catalog_item_id');
    }

    public function clicks()
    {
        return $this->hasMany('App\Models\CatalogItemClick', 'catalog_item_id');
    }

    public function abuseFlags()
    {
        return $this->hasMany('App\Models\AbuseFlag', 'catalog_item_id');
    }

    /* =========================================================================
     |  Helpers (Merchant-aware)
     | ========================================================================= */

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
     * Resolve active merchant listing for this catalog item.
     * If $userId passed, resolve that merchant's listing; otherwise first active listing.
     */
    public function activeMerchant(?int $userId = null): ?MerchantItem
    {
        return $this->merchantItems()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->where('status', 1)
            ->first();
    }

    /**
     * Get best merchant item from EAGER LOADED collection (avoids N+1).
     * Returns the first active merchant with stock, sorted by price.
     *
     * IMPORTANT: merchantItems MUST be eager loaded before accessing this!
     * In local environment, throws LogicException if not eager loaded.
     * In production, falls back to query (with performance penalty).
     *
     * @return MerchantItem|null
     * @throws \LogicException In local environment when relation not eager loaded
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

            // Production fallback (silent query - log warning)
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
                // Only active merchants with active merchant status
                if ($mi->status != 1) return false;
                // Check if user relation is loaded
                if ($mi->relationLoaded('user') && $mi->user) {
                    return $mi->user->is_merchant == 2;
                }
                return true; // If user not loaded, include it
            })
            ->sortBy([
                // Stock priority: items with stock first
                fn ($mi) => ($mi->stock ?? 0) > 0 ? 0 : 1,
                // Then by price
                fn ($mi) => (float) $mi->price,
            ])
            ->first();
    }

    /**
     * Scope to eager load merchant items with optimal relations.
     * Use this for listing pages to avoid N+1.
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

    /**
     * Legacy compatibility (base price incl. commission) for first active merchant.
     * Prefer merchantSizePrice() which accounts for size/options via MerchantItem logic.
     */
    public function merchantPrice(?int $userId = null)
    {
        $merchant = $this->activeMerchant($userId);
        if (!$merchant) {
            return null;
        }

        $price = (float) $merchant->price;

        // Get per-merchant commission
        $commission = $this->getMerchantCommissionFor($merchant->user_id);
        if ($commission && $commission->is_active) {
            $price = $price + (float) ($commission->fixed_commission ?? 0) + ($price * (float) ($commission->percentage_commission ?? 0) / 100);
        }

        return $price;
    }

    /**
     * Get commission settings for a specific merchant user.
     * Uses cache for performance.
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
     * Merchant-aware size/options/commission price.
     * Delegates to MerchantItem::merchantSizePrice() to avoid duplication.
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

        // Use centralized MonetaryUnitService (SINGLE SOURCE OF TRUTH)
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

        // Use centralized MonetaryUnitService (SINGLE SOURCE OF TRUTH)
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

        // Use centralized MonetaryUnitService (SINGLE SOURCE OF TRUTH)
        return monetaryUnit()->formatBase($rawPrice);
    }

    /**
     * Show previous price (merchant previous_price + commission).
     * If none, return 0.
     */
    public function showPreviousPrice(?int $userId = null)
    {
        $mi = $this->activeMerchant($userId);
        if (!$mi || !$mi->previous_price) {
            return 0;
        }

        // Base previous price
        $price = (float) $mi->previous_price;

        // Commission (per-merchant)
        $commission = $this->getMerchantCommissionFor($mi->user_id);
        if ($commission && $commission->is_active) {
            $price = $price + (float) ($commission->fixed_commission ?? 0) + ($price * (float) ($commission->percentage_commission ?? 0) / 100);
        }

        // Use centralized MonetaryUnitService (SINGLE SOURCE OF TRUTH)
        return monetaryUnit()->convertAndFormat($price);
    }

    /**
     * Convert a raw price (base currency) into current session currency, formatted.
     */
    public static function convertPrice($price)
    {
        // Use centralized MonetaryUnitService (SINGLE SOURCE OF TRUTH)
        return monetaryUnit()->convertAndFormat((float) ($price ?? 0));
    }

    public static function merchantConvertPrice($price)
    {
        // Use centralized MonetaryUnitService (SINGLE SOURCE OF TRUTH)
        return monetaryUnit()->convertAndFormat((float) ($price ?? 0));
    }

    public static function merchantConvertWithoutCurrencyPrice($price)
    {
        // Use centralized MonetaryUnitService (SINGLE SOURCE OF TRUTH)
        return monetaryUnit()->convert((float) ($price ?? 0));
    }

    /**
     * Get localized catalog item name based on current locale.
     * Arabic: label_ar (fallback to label_en, then name)
     * English: label_en (fallback to label_ar, then name)
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
     * Show truncated localized name for display.
     */
    public function showName()
    {
        $displayName = $this->localized_name;
        return mb_strlen($displayName, 'UTF-8') > 50 ? mb_substr($displayName, 0, 50, 'UTF-8') . '...' : $displayName;
    }

    /**
     * Out of stock if no active merchant listing OR (when $userId provided) that listing has zero stock.
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

    /**
     * Get merchant-aware stock quantity.
     * Returns stock from the active merchant listing.
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
     * (details column exists only in merchant_items, not in catalog_items)
     */
    public function getMerchantDetails(?int $userId = null): ?string
    {
        $mi = $this->activeMerchant($userId);
        return $mi?->details;
    }

    /**
     * Get merchant-specific policy from merchant item.
     * (policy column exists only in merchant_items, not in catalog_items)
     */
    public function getMerchantPolicy(?int $userId = null): ?string
    {
        $mi = $this->activeMerchant($userId);
        return $mi?->policy;
    }

    /**
     * Get merchant-specific features from merchant item.
     * (features column exists only in merchant_items, not in catalog_items)
     */
    public function getMerchantFeatures(?int $userId = null): ?string
    {
        $mi = $this->activeMerchant($userId);
        return $mi?->features;
    }

    /**
     * Build tag cloud from catalog items that have at least one active merchant listing.
     */
    public static function showTags()
    {
        $raw = CatalogItem::whereHas('merchantItems', fn ($q) => $q->where('status', 1))
            ->pluck('tags')
            ->toArray();

        $joined = [];
        foreach ($raw as $tagsStr) {
            if (is_array($tagsStr)) {
                $joined = array_merge($joined, $tagsStr);
            } elseif (is_string($tagsStr) && $tagsStr !== '') {
                $joined = array_merge($joined, explode(',', $tagsStr));
            }
        }

        $joined = array_filter(array_map('trim', $joined), fn ($t) => $t !== '');
        return array_values(array_unique($joined));
    }

    public function is_decimal($val)
    {
        return is_numeric($val) && floor($val) != $val;
    }

    /* =========================================================================
     |  Accessors
     | ========================================================================= */

    public function getTagsAttribute($value)
    {
        return $value === null ? '' : explode(',', $value);
    }

    public function getMetaTagAttribute($value)
    {
        return $value === null ? '' : explode(',', $value);
    }

    /* =========================================================================
     |  Discount helpers (merchant-aware)
     | ========================================================================= */

    /**
     * Off percentage based on current vs previous price (both merchant-aware).
     * Returns numeric percentage (not formatted).
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

        // Build previous final price
        $prev = (float) $mi->previous_price;

        // Commission (per-merchant)
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

    /* =========================================================================
     |  Collections filters (legacy)
     | ========================================================================= */

    /**
     * Filter a catalog item collection to those having an active merchant listing,
     * and stamp a transient price property equal to merchantSizePrice().
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
            // Stamp computed price (transient) for sorting/UI
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

        // Use centralized MonetaryUnitService (SINGLE SOURCE OF TRUTH)
        $converted = monetaryUnit()->convert($rawPrice);
        return \PriceHelper::apishowPrice($converted);
    }

    public function ApishowDetailsPrice(?int $userId = null)
    {
        $rawPrice = $this->merchantSizePrice($userId);
        if ($rawPrice === null) {
            return 0;
        }

        // Use centralized MonetaryUnitService (SINGLE SOURCE OF TRUTH)
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

        // Commission (per-merchant)
        $commission = $this->getMerchantCommissionFor($mi->user_id);
        if ($commission && $commission->is_active) {
            $price = $price + (float) ($commission->fixed_commission ?? 0) + ($price * (float) ($commission->percentage_commission ?? 0) / 100);
        }

        // Use centralized MonetaryUnitService (SINGLE SOURCE OF TRUTH)
        $converted = monetaryUnit()->convert($price);
        return \PriceHelper::apishowPrice($converted);
    }

    /* =========================================================================
     |  Misc
     | ========================================================================= */

    /**
     * Get the best merchant for this catalog item.
     *
     * Returns the active merchant with:
     * - status = 1
     * - user is a merchant (is_merchant = 2)
     * - prioritized by: has stock > cheapest price
     *
     * This method centralizes the logic previously duplicated in Blade views.
     *
     * @return \App\Models\MerchantItem|null
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
     * Get the catalog item URL with all required route parameters.
     *
     * Uses bestMerchant() to determine merchant_id and merchant_item_id.
     * Falls back to legacy route if no active merchant found.
     *
     * @return string
     */
    public function getCatalogItemUrl(): string
    {
        $merchant = $this->bestMerchant();

        if ($merchant && $this->slug) {
            return route('front.catalog-item', [
                'slug' => $this->slug,
                'merchant_id' => $merchant->user_id,
                'merchant_item_id' => $merchant->id
            ]);
        }

        return '#';
    }

    /**
     * Get merchant item data for a specific merchant
     *
     * AVOIDS CODE DUPLICATION - This helper eliminates repeated logic
     * for fetching merchant item in cart and checkout views
     *
     * @param int $merchantId The merchant user ID
     * @return \App\Models\MerchantItem|null
     */
    public function getMerchantItem(int $merchantId)
    {
        return $this->merchantItems()
            ->with('user')
            ->where('user_id', $merchantId)
            ->where('status', 1)
            ->first();
    }

    /**
     * Get merchant item ID for a specific merchant
     *
     * Shorthand for getMerchantItem()->id
     *
     * @param int $merchantId The merchant user ID
     * @return int|null Merchant item ID or null if not found
     */
    public function getMerchantItemId(int $merchantId): ?int
    {
        $merchant = $this->getMerchantItem($merchantId);
        return $merchant ? $merchant->id : null;
    }
}
