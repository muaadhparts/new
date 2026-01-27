<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
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
 * @property string $code
 * @property int $brand_id
 * @property int|null $brand_region_id
 * @property string|null $label_en
 * @property string|null $label_ar
 * @property string $name
 * @property string|null $name_ar
 * @property string|null $slug
 * @property int $sort
 * @property int $status
 * @property string|null $beginDate
 * @property string|null $endDate
 * @property int|null $beginYear
 * @property int|null $endYear
 * @property string|null $dateRangeDescription
 * @property string|null $type
 * @property string|null $vehicleType
 * @property string|null $shortName
 * @property string|null $models
 * @property string|null $imagePath
 * @property string|null $largeImagePath
 */
class Catalog extends Model
{
    use HasFactory;

    protected $table = 'catalogs';

    public $timestamps = true;

    protected $fillable = [
        'code',
        'brand_id',
        'brand_region_id',
        'label_en',
        'label_ar',
        'name',
        'name_ar',
        'slug',
        'sort',
        'status',
        'beginDate',
        'endDate',
        'beginYear',
        'endYear',
        'dateRangeDescription',
        'type',
        'vehicleType',
        'shortName',
        'models',
        'imagePath',
        'largeImagePath',
    ];

    protected $casts = [
        'brand_id' => 'integer',
        'brand_region_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'beginYear' => 'integer',
        'endYear' => 'integer',
    ];

    protected $with = ['brand:id,name,name_ar,slug,photo'];

    /* =========================================================================
     |  BOOT
     | ========================================================================= */

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

    /* =========================================================================
     |  RELATIONSHIPS
     | ========================================================================= */

    /**
     * The brand this catalog belongs to.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * The brand region for this catalog.
     */
    public function brandRegion(): BelongsTo
    {
        return $this->belongsTo(BrandRegion::class, 'brand_region_id');
    }

    /**
     * All categories in this catalog.
     */
    public function newCategories(): HasMany
    {
        return $this->hasMany(NewCategory::class, 'catalog_id');
    }

    /**
     * Root categories (level 1) in this catalog.
     */
    public function rootCategories(): HasMany
    {
        return $this->hasMany(NewCategory::class, 'catalog_id')
            ->where('level', 1);
    }

    /**
     * Sections in this catalog.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'catalog_id');
    }

    /**
     * Fitments for this catalog.
     */
    public function fitments(): HasMany
    {
        return $this->hasMany(CatalogItemFitment::class, 'catalog_id');
    }

    /* =========================================================================
     |  SCOPES
     | ========================================================================= */

    /**
     * Scope: Only active catalogs.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    /**
     * Scope: Filter by brand ID.
     */
    public function scopeForBrand(Builder $query, int $brandId): Builder
    {
        return $query->where('brand_id', $brandId);
    }

    /**
     * Scope: Filter by year (within begin/end year range).
     */
    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('beginYear', '<=', $year)
            ->where('endYear', '>=', $year);
    }

    /**
     * Scope: Filter by code.
     */
    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    /**
     * Scope: Order by sort then name.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->orderBy('name');
    }

    /* =========================================================================
     |  ACCESSORS
     | ========================================================================= */

    /**
     * Get localized catalog name.
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
     * Get localized catalog label.
     */
    public function getLocalizedLabelAttribute(): string
    {
        $isAr = app()->getLocale() === 'ar';
        $labelAr = trim((string)($this->label_ar ?? ''));
        $labelEn = trim((string)($this->label_en ?? ''));
        $name = $this->localized_name;

        if ($isAr) {
            return $labelAr !== '' ? $labelAr : ($labelEn !== '' ? $labelEn : $name);
        }
        return $labelEn !== '' ? $labelEn : ($labelAr !== '' ? $labelAr : $name);
    }

    /**
     * Get year range as string.
     */
    public function getYearRangeAttribute(): string
    {
        if (!$this->beginYear) {
            return '';
        }
        if ($this->beginYear === $this->endYear) {
            return (string) $this->beginYear;
        }
        return "{$this->beginYear}-{$this->endYear}";
    }

    /**
     * Get image URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->imagePath)) {
            return null;
        }
        if (filter_var($this->imagePath, FILTER_VALIDATE_URL)) {
            return $this->imagePath;
        }
        return asset($this->imagePath);
    }

    /**
     * Get large image URL.
     */
    public function getLargeImageUrlAttribute(): ?string
    {
        if (empty($this->largeImagePath)) {
            return $this->image_url;
        }
        if (filter_var($this->largeImagePath, FILTER_VALIDATE_URL)) {
            return $this->largeImagePath;
        }
        return asset($this->largeImagePath);
    }

    /**
     * Get child categories (level 1) - legacy compatibility.
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
     * Get items count (CatalogItems with active merchant_items).
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

    /* =========================================================================
     |  HELPER METHODS
     | ========================================================================= */

    /**
     * Get production years from catalog dates.
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
     * Factory support.
     */
    protected static function factory()
    {
        return \Modules\CatalogItem\Database\factories\CatlogFactory::new();
    }
}
