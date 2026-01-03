<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkuAlternativeItem extends Model
{
    protected $table = 'sku_alternatives';
    protected $fillable = ['sku', 'group_id'];

    /**
     * @deprecated Use catalogItems() instead
     */
    public function catalogItems()
    {
        return $this->hasMany(CatalogItem::class, 'sku', 'sku');
    }

    public function catalogItems()
    {
        return $this->hasMany(CatalogItem::class, 'sku', 'sku');
    }
}
