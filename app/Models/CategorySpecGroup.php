<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategorySpecGroup extends Model
{
    protected $table = 'category_spec_groups';

    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'catalog_id',
        'group_index',
        'category_period_id'
    ];

    /**
     * Get the category that owns this spec group.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(NewCategory::class, 'category_id');
    }

    /**
     * Get the catalog that owns this spec group.
     */
    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'catalog_id');
    }

    /**
     * Get the category period for this spec group.
     */
    public function categoryPeriod(): BelongsTo
    {
        return $this->belongsTo(CategoryPeriod::class, 'category_period_id');
    }

    /**
     * Get the items in this spec group.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CategorySpecGroupItem::class, 'group_id');
    }
}
