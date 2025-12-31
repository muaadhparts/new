<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogItemClick extends Model
{
    protected $table = 'catalog_item_clicks';
    protected $fillable = ['catalog_item_id', 'merchant_item_id'];
    public $timestamps = false;

    public function catalogItem()
    {
        return $this->belongsTo('App\Models\CatalogItem')->withDefault();
    }

    /**
     * Merchant item associated with the click
     */
    public function merchantItem()
    {
        return $this->belongsTo('App\Models\MerchantItem')->withDefault();
    }
}
