<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Catalog Model - Vehicle catalogs (Camry 2020, Altima 2019, etc.)
 *
 * Domain: Catalog
 * Table: catalogs
 *
 * @property int $id
 * @property int $brand_id
 * @property int|null $brand_region_id
 * @property string $name
 * @property string|null $name_ar
 * @property string $code
 * @property int $status
 * @property string|null $beginDate
 * @property string|null $endDate
 */
class Catalog extends Model
{
    use HasFactory;

    protected $table = 'catalogs';
    protected $guarded = ['id'];

    protected $with = ['brand:id,name,slug'];

    public $timestamps = true;

    protected $appends = ['localized_name'];

    // =========================================================
    // BOOT
    // =========================================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($catalog) {
            $catalog->created_by = Auth::id();
        });

        static::updating(function ($catalog) {
            $catalog->updated_by = Auth::id();
        });
    }

    // =========================================================
    // RELATIONS
    // =========================================================

    /**
     * The brand this catalog belongs to
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Brand region for this catalog
     */
    public function brandRegion(): BelongsTo
    {
        return $this->belongsTo(BrandRegion::class, 'brand_region_id');
    }

    /**
     * All categories in this catalog
     */
    public function newCategories(): HasMany
    {
        return $this->hasMany(NewCategory::class, 'catalog_id');
    }

    /**
     * Root categories (level 1)
     */
    public function rootCategories(): HasMany
    {
        return $this->hasMany(NewCategory::class, 'catalog_id')
                    ->where('level', 1);
    }

    /**
     * Sections in this catalog
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'catalog_id');
    }

    // =========================================================
    // ACCESSORS
    // =========================================================

    /**
     * Alias: childs -> newCategories Level 1 (compatibility)
     */
    public function getChildsAttribute()
    {
        return $this->newCategories()
            ->where('level', 1)
            ->orderBy('label_en')
            ->limit(10)
            ->get();
    }

    /**
     * Localized name
     */
    public function getLocalizedNameAttribute(): string
    {
        $isAr = app()->getLocale() === 'ar';
        $nameAr = trim((string)($this->name_ar ?? ''));
        $name = trim((string)($this->name ?? ''));

        if ($isAr) {
            return $nameAr !== '' ? $nameAr : $name;
        }
        return $name !== '' ? $name : $nameAr;
    }

    /**
     * Items count (CatalogItems with active merchant_items)
     */
    public function getItemsCountAttribute(): int
    {
        $partsTable = strtolower("parts_{$this->code}");

        if (!\Schema::hasTable($partsTable)) {
            return 0;
        }

        return \DB::table('catalog_items')
            ->whereExists(function ($query) use ($partsTable) {
                $query->select(\DB::raw(1))
                    ->from($partsTable)
                    ->whereColumn("{$partsTable}.part_number", 'catalog_items.part_number');
            })
            ->whereExists(function ($query) {
                $query->select(\DB::raw(1))
                    ->from('merchant_items')
                    ->whereColumn('merchant_items.catalog_item_id', 'catalog_items.id')
                    ->where('merchant_items.status', 1);
            })
            ->count();
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Get production years from catalog dates
     */
    public function getProductionYears(): array
    {
        if (empty($this->beginDate)) {
            return [];
        }

        try {
            $start = (int) substr($this->beginDate, 0, 4);
            $end = ($this->endDate && $this->endDate !== '0')
                ? (int) substr($this->endDate, 0, 4)
                : (int) date('Y');

            if ($start > $end || $start < 1970 || $end > date('Y') + 1) {
                return [];
            }

            return range($end, $start);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Factory support
     */
    protected static function factory()
    {
        return \Modules\CatalogItem\Database\factories\CatlogFactory::new();
    }
}
