<?php

namespace App\Models;

use App\Models\Currency;
use App\Models\MerchantProduct;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class Product extends Model
{

    // Removed vendor specific columns (e.g. user_id, price, stock, status) from fillable.
    // Product model now stores only catalogue-level attributes; vendor specific data is stored on MerchantProduct.
    protected $fillable = ['category_id', 'product_type', 'affiliate_link', 'sku', 'subcategory_id', 'childcategory_id', 'attributes', 'name', 'photo', 'details', 'policy', 'views', 'tags', 'featured', 'best', 'top', 'hot', 'latest', 'big', 'trending', 'features', 'colors', 'meta_tag', 'meta_description', 'youtube', 'type', 'file', 'link', 'platform', 'region', 'licence_type', 'measure', 'catalog_id', 'slug', 'flash_count', 'hot_count', 'new_count', 'sale_count', 'best_seller_count', 'popular_count', 'top_rated_count', 'big_save_count', 'trending_count', 'page_count', 'seller_product_count', 'wishlist_count', 'vendor_page_count', 'min_price', 'max_price', 'product_page', 'post_count', 'cross_products'];

    // Selectable columns for listing products. Removed vendor specific fields (price, size, status).
    public $selectable = ['id', 'name', 'slug', 'features', 'colors', 'thumbnail', 'attributes', 'category_id', 'details', 'type'];

    public function scopeHome($query)
    {
        // Home products scope: only products that have at least one active merchant listing
        return $query->whereHas('merchantProducts', function($q){
                $q->where('status', 1);
            })
            ->select($this->selectable)
            ->latest('id');
    }

    /**
     * Scope for filtering products by merchant listing status.  This overrides
     * dynamic whereStatus() calls to redirect to merchant_products.status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus($query, $status)
    {
        return $query->whereHas('merchantProducts', function($q) use ($status) {
            $q->where('status', $status);
        });
    }

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

    public function user()
    {
        return $this->belongsTo('App\Models\User')->withDefault();
    }

    /**
     * Get all merchant product entries for this product.  Each vendor listing
     * stores vendor specific fields such as price, stock and status.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
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

    public function IsSizeColor($value)
    {
        $sizes = array_unique($this->size);
        return in_array($value, $sizes);
    }

    public function checkVendor()
    {
        return $this->user_id != 0 ? '<small class="ml-2"> ' . __("VENDOR") . ': <a href="' . route('admin-vendor-show', $this->user_id) . '" target="_blank">' . $this->user->shop_name . '</a></small>' : '';
    }
    public function vendorPrice()
    {
        // In the new architecture, price and status are stored per vendor in
        // merchant_products.  This helper returns the base price for the first
        // active merchant listing, including commission.  If no active listing
        // exists, null is returned.
        // Retrieve an active merchant listing for this product.  If there are
        // multiple, we simply use the first one.  Applications may refine
        // selection logic based on user context.
        $merchant = $this->merchantProducts()->where('status', 1)->first();
        if (!$merchant) {
            return null;
        }
        // Base price provided by the vendor for this listing
        $price = $merchant->price;
        // Apply fixed and percentage commission from general settings
        $gs = cache()->remember('generalsettings', now()->addDay(), function () {
            return DB::table('generalsettings')->first();
        });
        $priceWithCommission = $price + $gs->fixed_commission + ($price / 100) * $gs->percentage_commission;
        return $priceWithCommission;
    }

    public function vendorSizePrice()
    {
        // Compute the vendor price including size and attribute adjustments.
        $merchant = $this->merchantProducts()->where('status', 1)->first();
        if (!$merchant) {
            return null;
        }
        // Base price from vendor
        $price = $merchant->price;
        // Add first size price if provided on the merchant listing
        if (!empty($merchant->size_price)) {
            $sizePrices = is_array($merchant->size_price) ? $merchant->size_price : explode(',', $merchant->size_price);
            if (!empty($sizePrices) && isset($sizePrices[0]) && $sizePrices[0] !== '') {
                $price += (float) $sizePrices[0];
            }
        }

        // Attribute Section: apply the first price of each attribute that has details_status = 1
        $attrArr = [];
        $attributes = isset($this->attributes["attributes"]) ? $this->attributes["attributes"] : null;
        if (!empty($attributes)) {
            $attrArr = json_decode($attributes, true);
        }
        if (!empty($attrArr)) {
            foreach ($attrArr as $attrKey => $attrVal) {
                if (is_array($attrVal) && array_key_exists("details_status", $attrVal) && $attrVal['details_status'] == 1) {
                    // Find the first price and add it
                    if (isset($attrVal['prices']) && is_array($attrVal['prices']) && isset($attrVal['prices'][0])) {
                        $price += $attrVal['prices'][0];
                    }
                }
            }
        }
        // Apply commission after adding size and attribute prices
        $gs = cache()->remember('generalsettings', now()->addDay(), function () {
            return DB::table('generalsettings')->first();
        });
        $priceWithCommission = $price + $gs->fixed_commission + ($price / 100) * $gs->percentage_commission;
        return $priceWithCommission;
    }

    public function setCurrency()
    {
        $gs = cache()->remember('generalsettings', now()->addDay(), function () {
            return DB::table('generalsettings')->first();
        });
        $price = $this->price;
        if (Session::has('currency')) {
            $curr = cache()->remember('session_currency', now()->addDay(), function () {
                return Currency::find(Session::get('currency'));
            });
        } else {
            $curr = cache()->remember('default_currency', now()->addDay(), function () {
                return Currency::where('is_default', '=', 1)->first();
            });
        }
        $price = $price * $curr->value;
        $price = \PriceHelper::showPrice($price);
        if ($gs->currency_format == 0) {
            return $curr->sign . ' ' . $price;
        } else {
            return $price . ' ' . $curr->sign;
        }
    }

    public function showPrice()
    {
        // Present the product price converted to the current session currency using
        // vendor specific pricing.  This method uses vendorSizePrice() to
        // compute the base price including size and attribute adjustments and
        // commission.  It then converts to the session currency and returns
        // the formatted price string.
        $rawPrice = $this->vendorSizePrice();
        if ($rawPrice === null) {
            return 0;
        }
        // Retrieve session or default currency
        if (Session::has('currency')) {
            $curr = cache()->remember('session_currency', now()->addDay(), function () {
                return Currency::find(Session::get('currency'));
            });
        } else {
            $curr = cache()->remember('default_currency', now()->addDay(), function () {
                return Currency::where('is_default', '=', 1)->first();
            });
        }
        // Apply currency conversion
        $converted = $rawPrice * $curr->value;
        $converted = \PriceHelper::showPrice($converted);
        $gs = cache()->remember('generalsettings', now()->addDay(), function () {
            return DB::table('generalsettings')->first();
        });
        return $gs->currency_format == 0 ? $curr->sign . ' ' . $converted : $converted . ' ' . $curr->sign;
    }

    public function adminShowPrice()
    {
        // Return the vendor specific price converted to the default currency for
        // admin view.  Uses vendorSizePrice() to compute the base price with
        // commission and then converts to the default currency.
        $rawPrice = $this->vendorSizePrice();
        if ($rawPrice === null) {
            return 0;
        }
        $gs = cache()->remember('generalsettings', now()->addDay(), function () {
            return DB::table('generalsettings')->first();
        });
        // Default currency
        $curr = Currency::where('is_default', '=', 1)->first();
        $converted = $rawPrice * $curr->value;
        $converted = \PriceHelper::showPrice($converted);
        return $gs->currency_format == 0 ? $curr->sign . ' ' . $converted : $converted . ' ' . $curr->sign;
    }

    public function showPreviousPrice()
    {
        // Show the previous price (if any) converted to session currency.  This
        // uses the previous_price on the first active merchant listing and
        // includes size/attribute adjustments and commission.  Returns 0 if
        // there is no previous price.
        // Find an active merchant listing
        $merchant = $this->merchantProducts()->where('status', 1)->first();
        if (!$merchant || !$merchant->previous_price) {
            return 0;
        }
        // Start with the vendor's previous price
        $price = $merchant->previous_price;
        // Size adjustments
        if (!empty($merchant->size_price)) {
            $sizePrices = is_array($merchant->size_price) ? $merchant->size_price : explode(',', $merchant->size_price);
            if (!empty($sizePrices) && isset($sizePrices[0]) && $sizePrices[0] !== '') {
                $price += (float) $sizePrices[0];
            }
        }
        // Attribute adjustments
        $attrArr = [];
        $attributes = isset($this->attributes["attributes"]) ? $this->attributes["attributes"] : null;
        if (!empty($attributes)) {
            $attrArr = json_decode($attributes, true);
        }
        if (!empty($attrArr)) {
            foreach ($attrArr as $attrKey => $attrVal) {
                if (is_array($attrVal) && array_key_exists("details_status", $attrVal) && $attrVal['details_status'] == 1) {
                    if (isset($attrVal['prices']) && is_array($attrVal['prices']) && isset($attrVal['prices'][0])) {
                        $price += $attrVal['prices'][0];
                    }
                }
            }
        }
        // Apply commission
        $gs = cache()->remember('generalsettings', now()->addDay(), function () {
            return DB::table('generalsettings')->first();
        });
        $priceWithCommission = $price + $gs->fixed_commission + ($price / 100) * $gs->percentage_commission;
        // Convert to currency
        if (Session::has('currency')) {
            $curr = cache()->remember('session_currency', now()->addDay(), function () {
                return Currency::find(Session::get('currency'));
            });
        } else {
            $curr = cache()->remember('default_currency', now()->addDay(), function () {
                return Currency::where('is_default', '=', 1)->first();
            });
        }
        $converted = $priceWithCommission * $curr->value;
        $converted = \PriceHelper::showPrice($converted);
        return $gs->currency_format == 0 ? $curr->sign . ' ' . $converted : $converted . ' ' . $curr->sign;


    }

    public static function convertPrice($price)
    {
        $gs = cache()->remember('generalsettings', now()->addDay(), function () {
            return DB::table('generalsettings')->first();
        });
        if (Session::has('currency')) {
            $curr = cache()->remember('session_currency', now()->addDay(), function () {
                return Currency::find(Session::get('currency'));
            });
        } else {
            $curr = cache()->remember('default_currency', now()->addDay(), function () {
                return Currency::where('is_default', '=', 1)->first();
            });
        }
//        dd($price ,$curr->value);
        $price = $price * $curr->value;
//        dd($price);
        $price = \PriceHelper::showPrice($price);
//        dd($price);
        if ($gs->currency_format == 0) {
            return $curr->sign . ' ' . $price;
        } else {
            return $price . ' ' . $curr->sign;
        }
    }

    public static function vendorConvertPrice($price)
    {
        $gs = cache()->remember('generalsettings', now()->addDay(), function () {
            return DB::table('generalsettings')->first();
        });

        if (Session::has('currency')) {
            $curr = cache()->remember('session_currency', now()->addDay(), function () {
                return Currency::find(Session::get('currency'));
            });
        } else {
            $curr = cache()->remember('default_currency', now()->addDay(), function () {
                return Currency::where('is_default', '=', 1)->first();
            });
        }
        $price = $price * $curr->value;
        $price = \PriceHelper::showPrice($price);
        if ($gs->currency_format == 0) {
            return $curr->sign . ' ' . $price;
        } else {
            return $price . ' ' . $curr->sign;
        }
    }

    public static function vendorConvertWithoutCurrencyPrice($price)
    {

        if (Session::has('currency')) {
            $curr = Currency::find(Session::get('currency'));
        } else {
            $curr = Currency::where('is_default', '=', 1)->first();
        }
  
        $price = $price * $curr->value;
        $price = \PriceHelper::showPrice($price);
        return $price;
    }

    public function showName()
    {
        $name = mb_strlen($this->name, 'UTF-8') > 50 ? mb_substr($this->name, 0, 50, 'UTF-8') . '...' : $this->name;
        return $name;
    }

    public function emptyStock()
    {
        // Determine stock based on the first active merchant listing.  If no
        // active listing exists or stock is zero, consider the product out of stock.
        $merchant = $this->merchantProducts()->where('status', 1)->first();
        if (!$merchant) {
            return true;
        }
        $stck = (string) $merchant->stock;
        return $stck === "0";
    }

    public static function showTags()
    {
        // Retrieve tags only for products that have at least one active merchant listing
        $tags = null;
        $tagz = '';
        $name = Product::whereHas('merchantProducts', function($q){
                $q->where('status', 1);
            })->pluck('tags')->toArray();
        foreach ($name as $nm) {
            if (!empty($nm)) {
                foreach ($nm as $n) {
                    $tagz .= $n . ',';
                }
            }
        }
        $tags = array_unique(explode(',', $tagz));
        return $tags;
    }

    public function is_decimal($val)
    {
        return is_numeric($val) && floor($val) != $val;
    }

    public function getColorallAttribute($value)
    {
        if ($value == null) {
            return '';
        }
        return explode(",", $value);
    }

    public function getColorPriceAttribute($value)
    {
        if ($value == null) {
            return '';
        }
        return explode(',', $value);
    }

    public function getSizeAttribute($value)
    {
        if ($value == null) {
            return '';
        }
        return explode(',', $value);
    }

    public function getSizeQtyAttribute($value)
    {
        if ($value == null) {
            return '';
        }
        return explode(',', $value);
    }

    public function getSizePriceAttribute($value)
    {
        if ($value == null) {
            return '';
        }
        return explode(',', $value);
    }

    public function getColorAttribute($value)
    {
        if ($value == null) {
            return '';
        }
        return explode(',', $value);
    }

    public function getTagsAttribute($value)
    {
        if ($value == null) {
            return '';
        }
        return explode(',', $value);
    }

    public function getMetaTagAttribute($value)
    {
        if ($value == null) {
            return '';
        }
        return explode(',', $value);
    }

    public function getFeaturesAttribute($value)
    {
        if ($value == null) {
            return '';
        }
        return explode(',', $value);
    }

    public function getColorsAttribute($value)
    {
        if ($value == null) {
            return '';
        }
        return explode(',', $value);
    }

    public function getLicenseAttribute($value)
    {
        if ($value == null) {
            return '';
        }
        return explode(',,', $value);
    }

    public function getLicenseQtyAttribute($value)
    {
        if ($value == null) {
            return '';
        }
        return explode(',', $value);
    }

    public function getWholeSellQtyAttribute($value)
    {
        if ($value == null) {
            return '';
        }
        return explode(',', $value);
    }

    public function getWholeSellDiscountAttribute($value)
    {
        if ($value == null) {
            return '';
        }
        return explode(',', $value);
    }

    public function offPercentage()
    {

        $gs = cache()->remember('generalsettings', now()->addDay(), function () {
            return DB::table('generalsettings')->first();
        });
        $price = $this->price;
        $preprice = $this->previous_price;
        if (!$preprice) {
            return 0;
        }

        if ($this->user_id != 0) {
            $price = $this->price + $gs->fixed_commission + ($this->price / 100) * $gs->percentage_commission;
            $preprice = $this->previous_price + $gs->fixed_commission + ($this->previous_price / 100) * $gs->percentage_commission;
        }

        if (!empty($this->size)) {
            $price += $this->size_price[0];
            $preprice += $this->size_price[0];
        }

        // Attribute Section

        $attributes = $this->attributes["attributes"];
        if (!empty($attributes)) {
            $attrArr = json_decode($attributes, true);
        }

        if (!empty($attrArr)) {
            foreach ($attrArr as $attrKey => $attrVal) {
                if (is_array($attrVal) && array_key_exists("details_status", $attrVal) && $attrVal['details_status'] == 1) {

                    foreach ($attrVal['values'] as $optionKey => $optionVal) {
                        $price += $attrVal['prices'][$optionKey];
                        // only the first price counts
                        $preprice += $attrVal['prices'][$optionKey];
                        break;
                    }
                }
            }
        }

        // Attribute Section Ends

        if (Session::has('currency')) {
            $curr = cache()->remember('session_currency', now()->addDay(), function () {
                return Currency::find(Session::get('currency'));
            });
        } else {
            $curr = cache()->remember('default_currency', now()->addDay(), function () {
                return Currency::where('is_default', '=', 1)->first();
            });
        }

//        $price = $price * $curr->value;
        $preprice = $preprice * $curr->value;
//        dd($this, $preprice ,$price);
        $Percentage = '';
            if($preprice > 0){
                $Percentage = (($preprice - $price) * 100) / $preprice;
            }



        if ($Percentage) {
            return $Percentage;
        }
    }

    public static function filterProducts($collection)
    {
        foreach ($collection as $key => $product) {
            // Skip products with no active merchant listing
            $vendorPrice = $product->vendorSizePrice();
            if ($vendorPrice === null) {
                unset($collection[$key]);
                continue;
            }
            // Filter by max price if provided
            if (isset($_GET['max']) && $vendorPrice >= $_GET['max']) {
                unset($collection[$key]);
                continue;
            }
            // Set the price attribute on the product instance to the calculated vendor price
            $product->price = $vendorPrice;
        }
        return $collection;
    }

    // MOBILE API SECTION

    public function ApishowPrice()
    {
        // API show price: vendor specific price including size and attribute
        // adjustments and commission converted to session currency.
        $rawPrice = $this->vendorSizePrice();
        if ($rawPrice === null) {
            return 0;
        }
        // Determine session or default currency
        if (Session::has('currency')) {
            $curr = cache()->remember('session_currency', now()->addDay(), function () {
                return Currency::find(Session::get('currency'));
            });
        } else {
            $curr = cache()->remember('default_currency', now()->addDay(), function () {
                return Currency::where('is_default', '=', 1)->first();
            });
        }
        $converted = $rawPrice * $curr->value;
        return \PriceHelper::apishowPrice($converted);
    }

    public function ApishowDetailsPrice()
    {
        // API show detailed price: vendor specific price including size and
        // attribute adjustments and commission converted to session currency.
        $rawPrice = $this->vendorSizePrice();
        if ($rawPrice === null) {
            return 0;
        }
        if (Session::has('currency')) {
            $curr = cache()->remember('session_currency', now()->addDay(), function () {
                return Currency::find(Session::get('currency'));
            });
        } else {
            $curr = cache()->remember('default_currency', now()->addDay(), function () {
                return Currency::where('is_default', '=', 1)->first();
            });
        }
        $converted = $rawPrice * $curr->value;
        return \PriceHelper::apishowPrice($converted);
    }

    public function ApishowPreviousPrice()
    {
        // API previous price: compute the previous price using merchant listing
        // previous_price, add size/attribute adjustments and commission, then
        // convert to session currency.
        $merchant = $this->merchantProducts()->where('status', 1)->first();
        if (!$merchant || !$merchant->previous_price) {
            return 0;
        }
        $price = $merchant->previous_price;
        if (!empty($merchant->size_price)) {
            $sizePrices = is_array($merchant->size_price) ? $merchant->size_price : explode(',', $merchant->size_price);
            if (!empty($sizePrices) && isset($sizePrices[0]) && $sizePrices[0] !== '') {
                $price += (float) $sizePrices[0];
            }
        }
        $attrArr = [];
        $attributes = isset($this->attributes["attributes"]) ? $this->attributes["attributes"] : null;
        if (!empty($attributes)) {
            $attrArr = json_decode($attributes, true);
        }
        if (!empty($attrArr)) {
            foreach ($attrArr as $attrKey => $attrVal) {
                if (is_array($attrVal) && array_key_exists("details_status", $attrVal) && $attrVal['details_status'] == 1) {
                    if (isset($attrVal['prices']) && is_array($attrVal['prices']) && isset($attrVal['prices'][0])) {
                        $price += $attrVal['prices'][0];
                    }
                }
            }
        }
        $gs = cache()->remember('generalsettings', now()->addDay(), function () {
            return DB::table('generalsettings')->first();
        });
        $priceWithCommission = $price + $gs->fixed_commission + ($price / 100) * $gs->percentage_commission;
        if (Session::has('currency')) {
            $curr = cache()->remember('session_currency', now()->addDay(), function () {
                return Currency::find(Session::get('currency'));
            });
        } else {
            $curr = cache()->remember('default_currency', now()->addDay(), function () {
                return Currency::where('is_default', '=', 1)->first();
            });
        }
        $converted = $priceWithCommission * $curr->value;
        return \PriceHelper::apishowPrice($converted);
    }
}
