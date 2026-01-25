<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SpecificationItem Model - Specification values
 *
 * Domain: Catalog
 * Table: specification_items
 *
 * @property int $id
 * @property int $specification_id
 * @property int|null $catalog_id
 * @property string|null $value_id
 * @property string|null $label
 */
class SpecificationItem extends Model
{
    protected $fillable = [
        'specification_id',
        'catalog_id',
        'value_id',
        'label',
    ];

    // =========================================================
    // RELATIONS
    // =========================================================

    /**
     * The specification this item belongs to
     */
    public function specification(): BelongsTo
    {
        return $this->belongsTo(Specification::class, 'specification_id');
    }

    /**
     * The catalog this item belongs to
     */
    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'catalog_id');
    }
}
