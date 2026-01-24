<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Section Model - Catalog sections
 *
 * Domain: Catalog
 * Table: sections
 *
 * @property int $id
 * @property string $code
 * @property int $catalog_id
 * @property string|null $full_code
 * @property string|null $formattedCode
 * @property int|null $category_id
 */
class Section extends Model
{
    protected $table = 'sections';

    protected $fillable = [
        'code',
        'catalog_id',
        'full_code',
        'formattedCode',
        'category_id',
    ];

    // =========================================================
    // RELATIONS
    // =========================================================

    /**
     * The catalog this section belongs to
     */
    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'catalog_id');
    }

    /**
     * The category this section belongs to
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(NewCategory::class, 'category_id');
    }

    /**
     * Illustrations in this section
     */
    public function illustrations(): HasMany
    {
        return $this->hasMany(Illustration::class, 'section_id');
    }
}
