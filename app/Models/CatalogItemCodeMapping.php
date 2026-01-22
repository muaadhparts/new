<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CatalogItemCodeMapping - Maps external item codes to catalog items
 *
 * This is the bridge between external stock files (twa01-twa05) and merchant_items.
 * The item_code field matches the fitemno from stock files.
 */
class CatalogItemCodeMapping extends Model
{
    protected $table = 'catalog_item_code_mappings';

    protected $fillable = [
        'item_code',
        'catalog_item_id',
        'quality_brand_id',
    ];

    protected $casts = [
        'catalog_item_id' => 'integer',
        'quality_brand_id' => 'integer',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * The catalog item this code maps to
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    /**
     * The quality brand for this mapping
     */
    public function qualityBrand(): BelongsTo
    {
        return $this->belongsTo(QualityBrand::class);
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Find mapping by item code
     */
    public function scopeByItemCode($query, string $itemCode)
    {
        return $query->where('item_code', $itemCode);
    }

    /**
     * Filter by quality brand
     */
    public function scopeByQualityBrand($query, int $qualityBrandId)
    {
        return $query->where('quality_brand_id', $qualityBrandId);
    }

    // ========================================
    // STATIC HELPERS
    // ========================================

    /**
     * Find catalog_item_id and quality_brand_id by item_code
     */
    public static function findByItemCode(string $itemCode): ?self
    {
        return static::where('item_code', trim($itemCode))->first();
    }

    /**
     * Get all mappings indexed by item_code for bulk operations
     * Uses raw query to minimize memory usage
     */
    public static function getAllMappingsIndexed(): array
    {
        $mappings = [];

        // Use cursor to avoid loading all records at once
        $query = \Illuminate\Support\Facades\DB::table('catalog_item_code_mappings')
            ->select('item_code', 'catalog_item_id', 'quality_brand_id')
            ->cursor();

        foreach ($query as $row) {
            $mappings[$row->item_code] = [
                'catalog_item_id' => (int) $row->catalog_item_id,
                'quality_brand_id' => (int) $row->quality_brand_id,
            ];
        }

        return $mappings;
    }
}
