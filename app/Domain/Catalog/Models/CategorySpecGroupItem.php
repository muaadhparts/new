<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CategorySpecGroupItem Model - Items in specification groups
 *
 * Domain: Catalog
 * Table: category_spec_group_items
 *
 * @property int $id
 * @property int $group_id
 * @property int $specification_item_id
 */
class CategorySpecGroupItem extends Model
{
    protected $table = 'category_spec_group_items';

    public $timestamps = false;

    protected $fillable = [
        'group_id',
        'specification_item_id'
    ];

    // =========================================================
    // RELATIONS
    // =========================================================

    /**
     * Get the spec group that owns this item.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(CategorySpecGroup::class, 'group_id');
    }

    /**
     * Get the specification item.
     */
    public function specificationItem(): BelongsTo
    {
        return $this->belongsTo(SpecificationItem::class, 'specification_item_id');
    }
}
