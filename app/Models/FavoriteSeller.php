<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoriteSeller extends Model
{
    protected $table = 'favorite_sellers';

    public $timestamps = false;

    protected $fillable = ['user_id', 'catalog_item_id', 'merchant_item_id'];

    public function user()
    {
        return $this->belongsTo('App\Models\User')->withDefault();
    }

    public function catalogItem()
    {
        return $this->belongsTo('App\Models\CatalogItem', 'catalog_item_id')->withDefault();
    }

    public function merchantItem()
    {
        return $this->belongsTo('App\Models\MerchantItem', 'merchant_item_id')->withDefault();
    }

    /**
     * Get the effective merchant item for this favorite item
     * If merchant_item_id is set, use it. Otherwise, find first active merchant item
     */
    public function getEffectiveMerchantItem()
    {
        if ($this->merchant_item_id) {
            return $this->merchantItem;
        }

        if ($this->relationLoaded('catalogItem') && $this->catalogItem && $this->catalogItem->relationLoaded('merchantItems')) {
            return $this->catalogItem->merchantItems
                ->filter(fn($mi) => $mi->status == 1)
                ->sortBy('price')
                ->first();
        }

        return \App\Models\MerchantItem::where('catalog_item_id', $this->catalog_item_id)
            ->where('status', 1)
            ->orderBy('price')
            ->first();
    }
}
