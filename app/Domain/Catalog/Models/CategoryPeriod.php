<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CategorySpecificationLink;

/**
 * CategoryPeriod Model - Category validity dates
 *
 * Domain: Catalog
 * Table: category_periods
 *
 * @property int $id
 * @property int $category_id
 * @property string|null $begin_date
 * @property string|null $end_date
 */
class CategoryPeriod extends Model
{
    protected $table = 'category_periods';

    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'begin_date',
        'end_date',
    ];

    // =========================================================
    // RELATIONS
    // =========================================================

    /**
     * The category this period belongs to
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(NewCategory::class, 'category_id');
    }

    /**
     * Specification links for this period
     */
    public function specificationLinks(): HasMany
    {
        return $this->hasMany(CategorySpecificationLink::class, 'category_period_id');
    }
}
