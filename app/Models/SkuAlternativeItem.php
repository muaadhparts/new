<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SkuAlternativeItem - جدول العلاقات بين البدائل
 *
 * Table: sku_alternative_item
 * Columns: id, a_id, b_id, created_at, updated_at
 *
 * Note: This table is currently NOT used by AlternativeService.
 * The system uses group_id in sku_alternatives table instead.
 */
class SkuAlternativeItem extends Model
{
    use HasFactory;

    protected $table = 'sku_alternative_item';
    protected $fillable = ['a_id', 'b_id'];

    /**
     * The main SKU alternative (a_id)
     */
    public function skuAlternativeA(): BelongsTo
    {
        return $this->belongsTo(SkuAlternative::class, 'a_id');
    }

    /**
     * The related SKU alternative (b_id)
     */
    public function skuAlternativeB(): BelongsTo
    {
        return $this->belongsTo(SkuAlternative::class, 'b_id');
    }
}
