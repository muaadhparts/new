<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CategorySpecificationLink;
use App\Models\PartAttribute;
use App\Models\PartExtension;

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

    /**
     * Category links (many-to-many via link table)
     */
    public function categoryLinks(): HasMany
    {
        return $this->hasMany(CategorySpecificationLink::class, 'specification_item_id');
    }

    /**
     * Part attributes using this specification
     */
    public function partAttributes(): HasMany
    {
        return $this->hasMany(PartAttribute::class, 'specification_item_id');
    }

    /**
     * Part extensions
     */
    public function partExtensions(): HasMany
    {
        return $this->hasMany(PartExtension::class, 'specification_item_id');
    }
}
