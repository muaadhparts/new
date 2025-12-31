<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogItemFitment extends Model
{
    protected $table = 'catalog_item_fitments';

    protected $fillable = [
        'catalog_item_id',
        'category_id',
        'subcategory_id',
        'childcategory_id',
        'rol',
        'beginYear',
    ];

    protected $casts = [
        'beginYear' => 'integer',
        'rol' => 'integer',
    ];

    /**
     * Get the catalog item that this fitment belongs to.
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    /**
     * Get the category for this fitment.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get the subcategory for this fitment.
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class, 'subcategory_id');
    }

    /**
     * Get the childcategory for this fitment.
     */
    public function childcategory(): BelongsTo
    {
        return $this->belongsTo(Childcategory::class, 'childcategory_id');
    }
}
