<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * CatalogItemCodeMapping Model
 *
 * Maps alternative item codes (OEM, aftermarket) to catalog items.
 * This is the bridge between external stock files (twa01-twa05) and merchant_items.
 * The item_code field matches the fitemno from stock files.
 *
 * @property int $id
 * @property string $item_code
 * @property int|null $catalog_item_id
 * @property int $quality_brand_id
 *
 * @property-read CatalogItem|null $catalogItem
 * @property-read QualityBrand $qualityBrand
 */
class CatalogItemCodeMapping extends Model
{
    protected $table = 'catalog_item_code_mappings';

    public $timestamps = false;

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
    // Relationships
    // ========================================

    /**
     * Get the catalog item this code maps to
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    /**
     * Get the quality brand (manufacturer) of this code
     */
    public function qualityBrand(): BelongsTo
    {
        return $this->belongsTo(QualityBrand::class, 'quality_brand_id');
    }

    // ========================================
    // Scopes
    // ========================================

    /**
     * Scope to find by item code
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('item_code', $code);
    }

    /**
     * Find mapping by item code (alias)
     */
    public function scopeByItemCode($query, string $itemCode)
    {
        return $query->where('item_code', $itemCode);
    }

    /**
     * Scope to filter by quality brand
     */
    public function scopeForBrand($query, int $qualityBrandId)
    {
        return $query->where('quality_brand_id', $qualityBrandId);
    }

    /**
     * Filter by quality brand (alias)
     */
    public function scopeByQualityBrand($query, int $qualityBrandId)
    {
        return $query->where('quality_brand_id', $qualityBrandId);
    }

    /**
     * Scope to filter by catalog item
     */
    public function scopeForCatalogItem($query, int $catalogItemId)
    {
        return $query->where('catalog_item_id', $catalogItemId);
    }

    // ========================================
    // Static Methods
    // ========================================

    /**
     * Find catalog item by any mapped code
     */
    public static function findCatalogItemByCode(string $code): ?CatalogItem
    {
        $mapping = static::with('catalogItem')
            ->where('item_code', $code)
            ->first();

        return $mapping?->catalogItem;
    }

    /**
     * Find catalog_item_id and quality_brand_id by item_code
     */
    public static function findByItemCode(string $itemCode): ?self
    {
        return static::where('item_code', trim($itemCode))->first();
    }

    /**
     * Get all codes for a catalog item
     */
    public static function getCodesForItem(int $catalogItemId): array
    {
        return static::where('catalog_item_id', $catalogItemId)
            ->with('qualityBrand:id,name_en,name_ar')
            ->get()
            ->map(fn($m) => [
                'code' => $m->item_code,
                'brand' => $m->qualityBrand?->localized_name,
            ])
            ->toArray();
    }

    /**
     * Get all mappings indexed by item_code for bulk operations
     * Uses raw query to minimize memory usage
     */
    public static function getAllMappingsIndexed(): array
    {
        $mappings = [];

        // Use cursor to avoid loading all records at once
        $query = DB::table('catalog_item_code_mappings')
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
