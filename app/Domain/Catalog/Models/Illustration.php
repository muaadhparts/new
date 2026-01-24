<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Illustration Model - Section illustrations/diagrams
 *
 * Domain: Catalog
 * Table: illustrations
 *
 * @property int $id
 * @property int $section_id
 * @property int|null $category_id
 */
class Illustration extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // =========================================================
    // RELATIONS
    // =========================================================

    /**
     * The section this illustration belongs to
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    /**
     * The category this illustration belongs to
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(NewCategory::class, 'category_id');
    }

    /**
     * Callouts (part numbers drawn on illustration)
     */
    public function callouts(): HasMany
    {
        return $this->hasMany(Callout::class, 'illustration_id');
    }
}
