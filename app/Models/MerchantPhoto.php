<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantPhoto extends Model
{
    protected $table = 'merchant_photos';
    protected $fillable = ['catalog_item_id', 'user_id', 'photo'];
    public $timestamps = false;

    /**
     * Get the catalog item that owns this photo
     */
    public function catalogItem()
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    /**
     * Get the merchant/user that owns this photo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Filter by merchant
     */
    public function scopeForMerchant($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by catalog item
     */
    public function scopeForCatalogItem($query, $catalogItemId)
    {
        return $query->where('catalog_item_id', $catalogItemId);
    }
}
