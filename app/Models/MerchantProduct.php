<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Import classes needed for currency conversion and database access.  These
// facades provide access to session currency selection, currency models and
// general settings.  Without these imports calls to Currency, Session and
// DB will not resolve properly.
use App\Models\Currency;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

/**
 * The MerchantProduct model represents the pivot between a product and the
 * vendor (user) who sells it. All vendor‑specific attributes such as price,
 * stock levels and status now live here instead of on the products table.
 */
class MerchantProduct extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'merchant_products';

    /**
     * The attributes that are mass assignable.
     *
     * These columns mirror the vendor‑specific columns that were removed from
     * the products table. When creating or updating a merchant product you
     * should only touch these fields.
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'product_id',
        'user_id',
        'price',
        'previous_price',
        'stock',
        'is_discount',
        'discount_date',
        'whole_sell_qty',
        'whole_sell_discount',
        'preordered',
        'minimum_qty',
        'stock_check',
        'popular',
        'status',
        'is_popular',
        'licence_type',
        'license_qty',
        'license',
        'ship',
        'product_condition',
        'size_price',
        'size_qty',
        'size',
        'color_all',
        'size_all',
    ];

    /**
     * Get the underlying product definition for this merchant product.
     */

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
    
    public function vendorSizePrice()
    {
        // Compute the vendor price including size and attribute adjustments for this
        // merchant listing.  Unlike the Product model's vendorSizePrice(), this
        // method operates directly on the current MerchantProduct instance and does
        // not attempt to look up another merchant entry.

        // Start with the base price stored on this merchant listing.
        $price = $this->price;

        // Add the first size price if provided on the merchant listing.  The size_price
        // attribute may be a comma‑separated string; split it into an array and
        // add the first element if present.
        if (!empty($this->size_price)) {
            $sizePrices = is_array($this->size_price) ? $this->size_price : explode(',', $this->size_price);
            if (!empty($sizePrices) && isset($sizePrices[0]) && $sizePrices[0] !== '') {
                $price += (float) $sizePrices[0];
            }
        }

        // Attribute Section: apply the first price of each attribute on the associated
        // product that has details_status = 1.  Attributes are stored as JSON on
        // the product's attributes column.  Decode the JSON and iterate
        // accordingly.
        $attrArr = [];
        $productAttributes = null;
        if ($this->product && isset($this->product->attributes) && is_array($this->product->attributes)) {
            // The attributes may be stored under the key "attributes" in the JSON.
            $productAttributes = $this->product->attributes["attributes"] ?? null;
        }
        if (!empty($productAttributes)) {
            $attrArr = json_decode($productAttributes, true);
        }
        if (!empty($attrArr)) {
            foreach ($attrArr as $attrKey => $attrVal) {
                if (is_array($attrVal) && array_key_exists("details_status", $attrVal) && $attrVal['details_status'] == 1) {
                    // Find the first price for this attribute and add it
                    if (isset($attrVal['prices']) && is_array($attrVal['prices']) && isset($attrVal['prices'][0])) {
                        $price += $attrVal['prices'][0];
                    }
                }
            }
        }

        // Apply commission after adding size and attribute prices.  Retrieve
        // general settings with caching to avoid repeated DB lookups.
        $gs = cache()->remember('generalsettings', now()->addDay(), function () {
            return DB::table('generalsettings')->first();
        });
        $priceWithCommission = $price + $gs->fixed_commission + ($price / 100) * $gs->percentage_commission;
        return $priceWithCommission;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the vendor (user) associated with this merchant product.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}