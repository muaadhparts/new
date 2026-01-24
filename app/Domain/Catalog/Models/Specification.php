<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Specification Model - Specification types
 *
 * Domain: Catalog
 * Table: specifications
 *
 * @property int $id
 * @property string $name
 * @property string|null $label
 * @property string|null $type
 */
class Specification extends Model
{
    protected $fillable = ['name', 'label', 'type'];

    // =========================================================
    // RELATIONS
    // =========================================================

    /**
     * Specification items (values)
     */
    public function items(): HasMany
    {
        return $this->hasMany(SpecificationItem::class, 'specification_id');
    }

    // =========================================================
    // SCOPES
    // =========================================================

    /**
     * Filter by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
}
