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
        'category_id', 'product_type', 'affiliate_link', 'sku', 'subcategory_id', 'childcategory_id',
        'attributes', 'name', 'photo', 'details', 'policy', 'views', 'tags', 'featured', 'best', 'top',
        'hot', 'latest', 'big', 'trending', 'features', 'colors', 'meta_tag', 'meta_description', 'youtube',
        'type', 'file', 'link', 'platform', 'region', 'licence_type', 'measure', 'catalog_id', 'slug',
        'flash_count', 'hot_count', 'new_count', 'sale_count', 'best_seller_count', 'popular_count',
        'top_rated_count', 'big_save_count', 'trending_count', 'page_count', 'seller_product_count',
        'wishlist_count', 'vendor_page_count', 'min_price', 'max_price', 'product_page', 'post_count',
        'cross_products'
    ];

    /**
     * Selectable columns for listing products (catalog-level only).
     */
    public $selectable = [
        'id', 'name', 'slug', 'features', 'colors', 'thumbnail', 'attributes',
        'category_id', 'details', 'type'
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

    public function wishlist()
    {
        return $this->belongsTo('App\Models\Wishlist')->withDefault();
    }

    public function galleries()
    {
        return $this->hasMany('App\Models\Gallery');
    }

    public function ratings()
    {
        return $this->hasMany('App\Models\Rating');
    }

    public function wishlists()
    {
        return $this->hasMany('App\Models\Wishlist');
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

    public function showName()
    {
        return mb_strlen($this->name, 'UTF-8') > 50 ? mb_substr($this->name, 0, 50, 'UTF-8') . '...' : $this->name;
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
     |  NOTE: These accessors read catalog-level columns if present.
     | ========================================================================= */

    public function getColorallAttribute($value)
    {
        return $value === null ? '' : explode(",", $value);
    }

    public function getColorPriceAttribute($value)
    {
        return $value === null ? '' : explode(',', $value);
    }

    public function getSizeAttribute($value)
    {
        return $value === null ? '' : explode(',', $value);
    }

    public function getSizeQtyAttribute($value)
    {
        return $value === null ? '' : explode(',', $value);
    }

    public function getSizePriceAttribute($value)
    {
        return $value === null ? '' : explode(',', $value);
    }

    public function getColorAttribute($value)
    {
        return $value === null ? '' : explode(',', $value);
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

    public function getColorsAttribute($value)
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
}
