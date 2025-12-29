<?php

namespace App\Models;

use App\Models\Currency;
use App\Models\MerchantProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class Product extends Model
{
    /**
     * Product model now stores only catalogue-level attributes;
     * vendor-specific data is stored on MerchantProduct.
     */
    protected $fillable = [
        'brand_id', 'sku', 'category_id', 'subcategory_id', 'childcategory_id',
        'label_en', 'label_ar', 'attributes', 'name', 'slug', 'photo', 'thumbnail', 'file', 'weight',
        'length', 'width', 'height', 'status',
        'policy', 'views', 'tags', 'features', 'is_meta', 'meta_tag', 'meta_description',
        'youtube', 'type', 'platform', 'region', 'measure', 'featured', 'best', 'top', 'hot', 'latest', 'big',
        'trending', 'sale', 'is_catalog', 'catalog_id', 'cross_products'
    ];

    /**
     * Selectable columns for listing products (catalog-level only).
     */
    public $selectable = [
        'id', 'name', 'slug', 'features', 'thumbnail', 'attributes',
        'category_id', 'type', 'weight'
    ];

    /* =========================================================================
     |  Scopes
     | ========================================================================= */

    /**
     * Home products: only products that have at least one active merchant listing.
     */
    public function scopeHome(Builder $query): Builder
    {
        return $query
            ->whereHas('merchantProducts', fn ($q) => $q->where('status', 1))
            ->select($this->selectable)
            ->latest('id');
    }

    /**
     * Scope for filtering products by merchant listing status.
     * Redirects status check to merchant_products.status.
     */
    public function scopeStatus(Builder $query, int $status = 1): Builder
    {
        return $query->whereHas('merchantProducts', function ($q) use ($status) {
            $q->where('status', $status);
        });
    }

    /* =========================================================================
     |  Relations
     | ========================================================================= */

    public function category()
    {
        return $this->belongsTo('App\Models\Category')->withDefault();
    }

    public function brand()
    {
        return $this->belongsTo('App\Models\Brand')->withDefault();
    }

    public function subcategory()
    {
        return $this->belongsTo('App\Models\Subcategory')->withDefault();
    }

    public function childcategory()
    {
        return $this->belongsTo('App\Models\Childcategory')->withDefault();
    }

    /**
     * Vendor listings for this product (each row is one seller).
     */
    public function merchantProducts()
    {
        return $this->hasMany(MerchantProduct::class, 'product_id');
    }

    /**
     * Product fitments (vehicle tree assignments).
     */
    public function fitments()
    {
        return $this->hasMany(ProductFitment::class, 'product_id');
    }

    public function favorite()
    {
        return $this->belongsTo('App\Models\Favorite')->withDefault();
    }

    public function galleries()
    {
        return $this->hasMany('App\Models\Gallery');
    }

    /**
     * Get galleries filtered by vendor user_id.
     * Use this for vendor-specific gallery display.
     *
     * @param int|null $userId Vendor user ID
     * @param int $limit Max number of galleries
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function galleriesForVendor(?int $userId, int $limit = 3)
    {
        if (!$userId) {
            return collect();
        }

        return Gallery::where('product_id', $this->id)
            ->where('user_id', $userId)
            ->take($limit)
            ->get();
    }

    public function ratings()
    {
        return $this->hasMany('App\Models\Rating');
    }

    public function favorites()
    {
        return $this->hasMany('App\Models\Favorite');
    }

    public function comments()
    {
        return $this->hasMany('App\Models\Comment');
    }

    public function clicks()
    {
        return $this->hasMany('App\Models\ProductClick');
    }

    public function reports()
    {
        return $this->hasMany('App\Models\Report', 'product_id');
    }

    /* =========================================================================
     |  Helpers (Vendor-aware)
     | ========================================================================= */

    /**
     * UI helper (admin): show first active vendor badge/link.
     */
    public function checkVendor(): string
    {
        $mp = $this->merchantProducts()->where('status', 1)->first();
        return $mp
            ? '<small class="ml-2"> ' . __("VENDOR") . ': <a href="' . route('admin-vendor-show', $mp->user_id) . '" target="_blank">' . optional($mp->user)->shop_name . '</a></small>'
            : '';
    }

    /**
     * Resolve active merchant listing for this product.
     * If $userId passed, resolve that vendor's listing; otherwise first active listing.
     *
     * // dd(['product_id' => $this->id, 'userId' => $userId, 'hit' => optional($mp)->id]); // debug
     */
    public function activeMerchant(?int $userId = null): ?MerchantProduct
    {
        return $this->merchantProducts()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->where('status', 1)
            ->first();
    }

    /**
     * Get best merchant product from EAGER LOADED collection (avoids N+1).
     * Returns the first active merchant with stock, sorted by price.
     *
     * IMPORTANT: merchantProducts MUST be eager loaded before accessing this!
     * In local environment, throws LogicException if not eager loaded.
     * In production, falls back to query (with performance penalty).
     *
     * @return MerchantProduct|null
     * @throws \LogicException In local environment when relation not eager loaded
     */
    public function getBestMerchantProductAttribute(): ?MerchantProduct
    {
        // Enforce eager loading in local environment
        if (!$this->relationLoaded('merchantProducts')) {
            if (app()->environment('local')) {
                throw new \LogicException(
                    "N+1 Query Detected: Product #{$this->id} accessed 'best_merchant_product' without eager loading. " .
                    "Use ->withBestMerchant() or ->with('merchantProducts') in your query."
                );
            }

            // Production fallback (silent query - log warning)
            \Log::warning("N+1 Query: Product #{$this->id} best_merchant_product accessed without eager loading");

            return $this->merchantProducts()
                ->where('status', 1)
                ->whereHas('user', fn($u) => $u->where('is_vendor', 2))
                ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
                ->orderBy('price')
                ->first();
        }

        // Filter and sort from eager loaded collection
        return $this->merchantProducts
            ->filter(function ($mp) {
                // Only active merchants with active vendor
                if ($mp->status != 1) return false;
                // Check if user relation is loaded
                if ($mp->relationLoaded('user') && $mp->user) {
                    return $mp->user->is_vendor == 2;
                }
                return true; // If user not loaded, include it
            })
            ->sortBy([
                // Stock priority: items with stock first
                fn ($mp) => ($mp->stock ?? 0) > 0 ? 0 : 1,
                // Then by price
                fn ($mp) => (float) $mp->price,
            ])
            ->first();
    }

    /**
     * Scope to eager load merchant products with optimal relations.
     * Use this for listing pages to avoid N+1.
     */
    public function scopeWithBestMerchant(Builder $query): Builder
    {
        return $query->with(['merchantProducts' => function ($q) {
            $q->where('status', 1)
              ->with(['user:id,is_vendor,shop_name,shop_name_ar', 'qualityBrand:id,name_en,name_ar'])
              ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
              ->orderBy('price');
        }]);
    }

    /**
     * Legacy compatibility (base price incl. commission) for first active merchant.
     * Prefer vendorSizePrice() which accounts for size/options via MerchantProduct logic.
     */
    public function vendorPrice(?int $userId = null)
    {
        $merchant = $this->activeMerchant($userId);
        if (!$merchant) {
            return null;
        }

        $gs = cache()->remember('generalsettings', now()->addDay(), fn () => DB::table('generalsettings')->first());

        $price = (float) $merchant->price;
        $price = $price + (float) $gs->fixed_commission + ($price * (float) $gs->percentage_commission / 100);

        return $price;
    }

    /**
     * Vendor-aware size/options/commission price.
     * Delegates to MerchantProduct::vendorSizePrice() to avoid duplication.
     *
     * // dd(['product_id' => $this->id, 'userId' => $userId, 'price' => $mp ? $mp->vendorSizePrice() : null]); // debug
     */
    public function vendorSizePrice(?int $userId = null)
    {
        $mp = $this->activeMerchant($userId);
        return $mp ? $mp->vendorSizePrice() : null;
    }

    /**
     * Convert vendor-aware price to session currency and format.
     */
    public function setCurrency()
    {
        $rawPrice = $this->vendorSizePrice();
        if ($rawPrice === null) {
            return 0;
        }

        $gs = cache()->remember('generalsettings', now()->addDay(), fn () => DB::table('generalsettings')->first());
        $curr = Session::has('currency')
            ? cache()->remember('session_currency', now()->addDay(), fn () => Currency::find(Session::get('currency')))
            : cache()->remember('default_currency', now()->addDay(), fn () => Currency::where('is_default', 1)->first());

        $converted = \PriceHelper::showPrice($rawPrice * $curr->value);
        return $gs->currency_format == 0 ? $curr->sign . ' ' . $converted : $converted . ' ' . $curr->sign;
    }

    /**
     * Show formatted price (session currency) for a vendor-aware price.
     */
    public function showPrice(?int $userId = null)
    {
        $rawPrice = $this->vendorSizePrice($userId);
        if ($rawPrice === null) {
            return 0;
        }

        $gs = cache()->remember('generalsettings', now()->addDay(), fn () => DB::table('generalsettings')->first());
        $curr = Session::has('currency')
            ? cache()->remember('session_currency', now()->addDay(), fn () => Currency::find(Session::get('currency')))
            : cache()->remember('default_currency', now()->addDay(), fn () => Currency::where('is_default', 1)->first());

        $converted = \PriceHelper::showPrice($rawPrice * $curr->value);
        return $gs->currency_format == 0 ? $curr->sign . ' ' . $converted : $converted . ' ' . $curr->sign;
    }

    /**
     * Show admin-formatted price (default currency).
     */
    public function adminShowPrice(?int $userId = null)
    {
        $rawPrice = $this->vendorSizePrice($userId);
        if ($rawPrice === null) {
            return 0;
        }

        $gs = cache()->remember('generalsettings', now()->addDay(), fn () => DB::table('generalsettings')->first());
        $curr = Currency::where('is_default', 1)->first();

        $converted = \PriceHelper::showPrice($rawPrice * $curr->value);
        return $gs->currency_format == 0 ? $curr->sign . ' ' . $converted : $converted . ' ' . $curr->sign;
    }

    /**
     * Show previous price (vendor previous_price + adjustments + commission).
     * If none, return 0.
     */
    public function showPreviousPrice(?int $userId = null)
    {
        $mp = $this->activeMerchant($userId);
        if (!$mp || !$mp->previous_price) {
            return 0;
        }

        // Base previous price
        $price = (float) $mp->previous_price;

        // Size adjustment (first bucket if provided)
        if (!empty($mp->size_price)) {
            $raw = $mp->size_price;
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $first = array_values($decoded)[0] ?? 0;
                    $price += (float) $first;
                } else {
                    $parts = explode(',', $raw);
                    $price += isset($parts[0]) && $parts[0] !== '' ? (float) $parts[0] : 0.0;
                }
            } elseif (is_array($raw)) {
                $first = array_values($raw)[0] ?? 0;
                $price += (float) $first;
            }
        }

        // Commission
        $gs = cache()->remember('generalsettings', now()->addDay(), fn () => DB::table('generalsettings')->first());
        $price = $price + (float) $gs->fixed_commission + ($price * (float) $gs->percentage_commission / 100);

        // Currency formatting
        $curr = Session::has('currency')
            ? cache()->remember('session_currency', now()->addDay(), fn () => Currency::find(Session::get('currency')))
            : cache()->remember('default_currency', now()->addDay(), fn () => Currency::where('is_default', 1)->first());

        $converted = \PriceHelper::showPrice($price * $curr->value);
        return $gs->currency_format == 0 ? $curr->sign . ' ' . $converted : $converted . ' ' . $curr->sign;
    }

    /**
     * Convert a raw price (base currency) into current session currency, formatted.
     */
    public static function convertPrice($price)
    {
        $gs = cache()->remember('generalsettings', now()->addDay(), fn () => DB::table('generalsettings')->first());

        $curr = Session::has('currency')
            ? cache()->remember('session_currency', now()->addDay(), fn () => Currency::find(Session::get('currency')))
            : cache()->remember('default_currency', now()->addDay(), fn () => Currency::where('is_default', 1)->first());

        $price = \PriceHelper::showPrice($price * $curr->value);
        return $gs->currency_format == 0 ? $curr->sign . ' ' . $price : $price . ' ' . $curr->sign;
    }

    public static function vendorConvertPrice($price)
    {
        $gs = cache()->remember('generalsettings', now()->addDay(), fn () => DB::table('generalsettings')->first());

        $curr = Session::has('currency')
            ? cache()->remember('session_currency', now()->addDay(), fn () => Currency::find(Session::get('currency')))
            : cache()->remember('default_currency', now()->addDay(), fn () => Currency::where('is_default', 1)->first());

        $price = \PriceHelper::showPrice($price * $curr->value);
        return $gs->currency_format == 0 ? $curr->sign . ' ' . $price : $price . ' ' . $curr->sign;
    }

    public static function vendorConvertWithoutCurrencyPrice($price)
    {
        $curr = Session::has('currency')
            ? Currency::find(Session::get('currency'))
            : Currency::where('is_default', 1)->first();

        $price = \PriceHelper::showPrice($price * $curr->value);
        return $price;
    }

    /**
     * Get localized product name based on current locale.
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
            $mp = $this->activeMerchant($userId);
            return !$mp || (string) $mp->stock === "0";
        }

        $hasStock = $this->merchantProducts()
            ->where('status', 1)
            ->where('stock', '>', 0)
            ->exists();

        return !$hasStock;
    }

    /**
     * Get vendor-aware stock quantity.
     * Returns stock from the active merchant listing.
     */
    public function vendorSizeStock(?int $userId = null)
    {
        $mp = $this->activeMerchant($userId);
        return $mp ? $mp->stock : 0;
    }

    /**
     * Get vendor-specific product condition.
     */
    public function getProductCondition(?int $userId = null)
    {
        $mp = $this->activeMerchant($userId);
        return $mp ? $mp->product_condition : 0;
    }

    /**
     * Get vendor-specific minimum quantity.
     */
    public function getMinimumQty(?int $userId = null)
    {
        $mp = $this->activeMerchant($userId);
        return $mp ? $mp->minimum_qty : null;
    }

    /**
     * Get vendor-specific stock check setting.
     */
    public function getStockCheck(?int $userId = null)
    {
        $mp = $this->activeMerchant($userId);
        return $mp ? $mp->stock_check : 0;
    }

    /**
     * Get vendor-specific colors from merchant product.
     */
    public function getVendorColors(?int $userId = null)
    {
        $mp = $this->activeMerchant($userId);
        if (!$mp || empty($mp->color_all)) {
            return [];
        }
        return is_array($mp->color_all) ? $mp->color_all : explode(',', $mp->color_all);
    }

    /**
     * Get vendor-specific color prices from merchant product.
     */
    public function getVendorColorPrices(?int $userId = null)
    {
        $mp = $this->activeMerchant($userId);
        if (!$mp || empty($mp->color_price)) {
            return [];
        }
        return is_array($mp->color_price) ? $mp->color_price : explode(',', $mp->color_price);
    }

    /**
     * Get vendor-specific previous price from merchant product.
     */
    public function getVendorPreviousPrice(?int $userId = null)
    {
        $mp = $this->activeMerchant($userId);
        return $mp ? $mp->previous_price : null;
    }

    /**
     * Get vendor-specific ship from merchant product.
     */
    public function getVendorShip(?int $userId = null)
    {
        $mp = $this->activeMerchant($userId);
        return $mp ? $mp->ship : null;
    }

    /**
     * Get vendor-specific details from merchant product, fallback to product policy.
     */
    public function getVendorDetails(?int $userId = null)
    {
        $mp = $this->activeMerchant($userId);
        if ($mp && !empty($mp->details)) {
            return $mp->details;
        }
        return $this->policy; // Fallback to product policy
    }

    /**
     * Get vendor-specific policy from merchant product, fallback to product policy.
     */
    public function getVendorPolicy(?int $userId = null)
    {
        $mp = $this->activeMerchant($userId);
        if ($mp && !empty($mp->policy)) {
            return $mp->policy;
        }
        return $this->policy; // Fallback to product policy
    }

    /**
     * Get vendor-specific features from merchant product, fallback to product features.
     */
    public function getVendorFeatures(?int $userId = null)
    {
        $mp = $this->activeMerchant($userId);
        if ($mp && !empty($mp->features)) {
            return $mp->features;
        }
        return $this->features; // Fallback to product features
    }

    /**
     * Get vendor-specific size from merchant product.
     */
    public function getVendorSize(?int $userId = null)
    {
        $mp = $this->activeMerchant($userId);
        return $mp ? $mp->size : null;
    }

    /**
     * Get vendor-specific size qty from merchant product.
     */
    public function getVendorSizeQty(?int $userId = null)
    {
        $mp = $this->activeMerchant($userId);
        return $mp ? $mp->size_qty : null;
    }

    /**
     * Get vendor-specific size price from merchant product.
     */
    public function getVendorSizePrice(?int $userId = null)
    {
        $mp = $this->activeMerchant($userId);
        return $mp ? $mp->size_price : null;
    }


    /**
     * Build tag cloud from products that have at least one active merchant listing.
     */
    public static function showTags()
    {
        $raw = Product::whereHas('merchantProducts', fn ($q) => $q->where('status', 1))
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
     |  Accessors (legacy)
     |  NOTE: These accessors now redirect vendor-specific columns to merchant_products.
     | ========================================================================= */

    /**
     * Legacy accessors for vendor-specific columns.
     * These now redirect to the active merchant product according to final schema.
     */
    public function __get($key)
    {
        // CRITICAL FIX: Check if attribute exists in $attributes array FIRST
        // This allows manually injected values (e.g., in cart context) to take precedence
        // over computed vendor methods. Without this check, cart items would always
        // get the price from activeMerchant() (first merchant), not the specific merchant.
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        // Handle vendor-specific columns that were moved to merchant_products
        $vendorColumns = [
            'product_condition' => 'getProductCondition',
            'minimum_qty' => 'getMinimumQty',
            'stock_check' => 'getStockCheck',
            'stock' => 'vendorSizeStock',
            'price' => 'vendorPrice',
            'previous_price' => 'getVendorPreviousPrice',
            'ship' => 'getVendorShip',
            'size' => 'getVendorSize',
            'size_qty' => 'getVendorSizeQty',
            'size_price' => 'getVendorSizePrice'
        ];

        if (array_key_exists($key, $vendorColumns)) {
            $method = $vendorColumns[$key];
            return $this->$method();
        }

        // Colors: Always redirect to vendor-specific colors from merchant_products
        if ($key === 'color' || $key === 'colors' || $key === 'color_all') {
            return $this->getVendorColors();
        }

        if ($key === 'color_price') {
            return $this->getVendorColorPrices();
        }

        // Policy/Features: Try merchant first, fallback to product
        if ($key === 'details') {
            return $this->getVendorDetails();
        }

        // Fall back to parent implementation
        return parent::__get($key);
    }




    public function getTagsAttribute($value)
    {
        return $value === null ? '' : explode(',', $value);
    }

    public function getMetaTagAttribute($value)
    {
        return $value === null ? '' : explode(',', $value);
    }

    public function getFeaturesAttribute($value)
    {
        return $value === null ? '' : explode(',', $value);
    }


    public function getLicenseAttribute($value)
    {
        return $value === null ? '' : explode(',,', $value);
    }

    public function getLicenseQtyAttribute($value)
    {
        return $value === null ? '' : explode(',', $value);
    }

    public function getWholeSellQtyAttribute($value)
    {
        return $value === null ? '' : explode(',', $value);
    }

    public function getWholeSellDiscountAttribute($value)
    {
        return $value === null ? '' : explode(',', $value);
    }

    /* =========================================================================
     |  Discount helpers (vendor-aware)
     | ========================================================================= */

    /**
     * Off percentage based on vendor current vs previous price (both vendor-aware).
     * Returns numeric percentage (not formatted).
     */
    public function offPercentage(?int $userId = null)
    {
        $mp = $this->activeMerchant($userId);
        if (!$mp || !$mp->previous_price) {
            return 0;
        }

        $current = $this->vendorSizePrice($userId);
        if ($current === null) {
            return 0;
        }

        // Build previous final price similar to showPreviousPrice() but in raw value.
        $prev = (float) $mp->previous_price;

        if (!empty($mp->size_price)) {
            $raw = $mp->size_price;
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $first = array_values($decoded)[0] ?? 0;
                    $prev += (float) $first;
                } else {
                    $parts = explode(',', $raw);
                    $prev += isset($parts[0]) && $parts[0] !== '' ? (float) $parts[0] : 0.0;
                }
            } elseif (is_array($raw)) {
                $first = array_values($raw)[0] ?? 0;
                $prev += (float) $first;
            }
        }

        $gs = cache()->remember('generalsettings', now()->addDay(), fn () => DB::table('generalsettings')->first());
        $prev = $prev + (float) $gs->fixed_commission + ($prev * (float) $gs->percentage_commission / 100);

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
     * Filter a product collection to those having an active merchant listing,
     * and stamp a transient price property equal to vendorSizePrice().
     */
    public static function filterProducts($collection)
    {
        foreach ($collection as $key => $product) {
            $vendorPrice = $product->vendorSizePrice();
            if ($vendorPrice === null) {
                unset($collection[$key]);
                continue;
            }
            if (isset($_GET['max']) && $vendorPrice >= (float) $_GET['max']) {
                unset($collection[$key]);
                continue;
            }
            // Stamp computed price (transient) for sorting/UI
            $product->price = $vendorPrice;
        }
        return $collection;
    }

    /* =========================================================================
     |  MOBILE API SECTION (vendor-aware)
     | ========================================================================= */

    public function ApishowPrice(?int $userId = null)
    {
        $rawPrice = $this->vendorSizePrice($userId);
        if ($rawPrice === null) {
            return 0;
        }

        $curr = Session::has('currency')
            ? cache()->remember('session_currency', now()->addDay(), fn () => Currency::find(Session::get('currency')))
            : cache()->remember('default_currency', now()->addDay(), fn () => Currency::where('is_default', 1)->first());

        $converted = $rawPrice * $curr->value;
        return \PriceHelper::apishowPrice($converted);
    }

    public function ApishowDetailsPrice(?int $userId = null)
    {
        $rawPrice = $this->vendorSizePrice($userId);
        if ($rawPrice === null) {
            return 0;
        }

        $curr = Session::has('currency')
            ? cache()->remember('session_currency', now()->addDay(), fn () => Currency::find(Session::get('currency')))
            : cache()->remember('default_currency', now()->addDay(), fn () => Currency::where('is_default', 1)->first());

        $converted = $rawPrice * $curr->value;
        return \PriceHelper::apishowPrice($converted);
    }

    public function ApishowPreviousPrice(?int $userId = null)
    {
        $mp = $this->activeMerchant($userId);
        if (!$mp || !$mp->previous_price) {
            return 0;
        }

        $price = (float) $mp->previous_price;

        if (!empty($mp->size_price)) {
            $raw = $mp->size_price;
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $first = array_values($decoded)[0] ?? 0;
                    $price += (float) $first;
                } else {
                    $parts = explode(',', $raw);
                    $price += isset($parts[0]) && $parts[0] !== '' ? (float) $parts[0] : 0.0;
                }
            } elseif (is_array($raw)) {
                $first = array_values($raw)[0] ?? 0;
                $price += (float) $first;
            }
        }

        $gs = cache()->remember('generalsettings', now()->addDay(), fn () => DB::table('generalsettings')->first());
        $price = $price + (float) $gs->fixed_commission + ($price * (float) $gs->percentage_commission / 100);

        $curr = Session::has('currency')
            ? cache()->remember('session_currency', now()->addDay(), fn () => Currency::find(Session::get('currency')))
            : cache()->remember('default_currency', now()->addDay(), fn () => Currency::where('is_default', 1)->first());

        $converted = $price * $curr->value;
        return \PriceHelper::apishowPrice($converted);
    }

    /* =========================================================================
     |  Misc
     | ========================================================================= */

    public function IsSizeColor($value)
    {
        $sizes = array_unique($this->size);
        return in_array($value, $sizes);
    }

    /**
     * Get the best vendor merchant for this product.
     *
     * Returns the active merchant with:
     * - status = 1
     * - user is a vendor (is_vendor = 2)
     * - prioritized by: has stock > cheapest price
     *
     * This method centralizes the logic previously duplicated in Blade views.
     *
     * @return \App\Models\MerchantProduct|null
     */
    public function bestVendorMerchant(): ?MerchantProduct
    {
        return $this->merchantProducts()
            ->where('status', 1)
            ->whereHas('user', fn($q) => $q->where('is_vendor', 2))
            ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
            ->orderBy('price')
            ->first();
    }

    /**
     * Get the product URL with all required route parameters.
     *
     * Uses bestVendorMerchant() to determine vendor_id and merchant_product_id.
     * Falls back to legacy route if no active merchant found.
     *
     * @return string
     */
    public function getProductUrl(): string
    {
        $merchant = $this->bestVendorMerchant();

        if ($merchant && $this->slug) {
            return route('front.product', [
                'slug' => $this->slug,
                'vendor_id' => $merchant->user_id,
                'merchant_product_id' => $merchant->id
            ]);
        }

        return $this->slug ? route('front.product.legacy', $this->slug) : '#';
    }

    /**
     * Get merchant product data for a specific vendor
     *
     * AVOIDS CODE DUPLICATION - This helper eliminates repeated logic
     * for fetching merchant product in cart and checkout views
     *
     * @param int $vendorId The vendor user ID
     * @return \App\Models\MerchantProduct|null
     */
    public function getMerchantProduct(int $vendorId)
    {
        return $this->merchantProducts()
            ->with('user')
            ->where('user_id', $vendorId)
            ->where('status', 1)
            ->first();
    }

    /**
     * Get merchant product ID for a specific vendor
     *
     * Shorthand for getMerchantProduct()->id
     *
     * @param int $vendorId The vendor user ID
     * @return int|null Merchant product ID or null if not found
     */
    public function getMerchantProductId(int $vendorId): ?int
    {
        $merchant = $this->getMerchantProduct($vendorId);
        return $merchant ? $merchant->id : null;
    }
}
