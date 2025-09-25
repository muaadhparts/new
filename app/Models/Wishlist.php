<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'product_id', 'merchant_product_id'];

    public function user()
    {
        return $this->belongsTo('App\Models\User')->withDefault();
    }

    public function product()
    {
        return $this->belongsTo('App\Models\Product')->withDefault();
    }

    public function merchantProduct()
    {
        return $this->belongsTo('App\Models\MerchantProduct')->withDefault();
    }

    /**
     * Get the effective merchant product for this wishlist item
     * If merchant_product_id is set, use it. Otherwise, find first active merchant product
     */
    public function getEffectiveMerchantProduct()
    {
        if ($this->merchant_product_id) {
            return $this->merchantProduct;
        }

        // Backward compatibility: find first active merchant product for this product
        return \App\Models\MerchantProduct::where('product_id', $this->product_id)
            ->where('status', 1)
            ->orderBy('price')
            ->first();
    }
}
