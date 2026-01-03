<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkuAlternativeItem extends Model
{
    protected $table = 'sku_alternatives';
    protected $fillable = ['part_number', 'group_id'];

    /**
     * @deprecated Use catalogItems() instead
     */
    public function catalogItems()
    {
        return $this->hasMany(CatalogItem::class, 'part_number', 'part_number');
    }

    public function catalogItems()
    {
        return $this->hasMany(CatalogItem::class, 'part_number', 'part_number');
    }
}
