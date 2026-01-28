<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Category Model - Unified category tree
 *
 * Domain: Catalog
 * Table: categories
 *
 * Structure: Brand -> Catalog -> Category (3 levels)
 * Replaces: categories, subcategories, childcategories, treecategories
 *
 * @property int $id
 * @property string $full_code
 * @property string|null $formatted_code
 * @property string|null $slug
 * @property string $label_en
 * @property string|null $label_ar
 * @property int $catalog_id
 * @property int $brand_id
 * @property int $brand_region_id
 * @property int $level
 * @property int|null $parent_id
 * @property string|null $Applicability
 */
class Category extends Model
{
    protected $table = 'categories';
    public $timestamps = false;

    protected $fillable = [
        'full_code', 'formatted_code', 'slug',
        'label_en', 'label_ar', 'catalog_id',
        'brand_id', 'brand_region_id', 'level', 'parent_id',
        'thumbnail', 'images', 'Applicability',
        'spec_key', 'parents_key', 'path', 'keywords'
    ];

    protected $appends = ['localized_name', 'name'];

    // =========================================================
    // MUTATORS
    // =========================================================

    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = str_replace(' ', '-', $value);
    }

    // =========================================================
    // RELATIONS
    // =========================================================

    /**
     * The catalog this category belongs to
     */
    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'catalog_id');
    }

    /**
     * The brand this category belongs to
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * The brand region this category belongs to
     */
    public function brandRegion(): BelongsTo
    {
        return $this->belongsTo(BrandRegion::class, 'brand_region_id');
    }

    /**
     * Child categories
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Parent category (via parent_id)
     */
    public function trueParent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Parent category (via spec_key)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parents_key', 'spec_key');
    }

    /**
     * Sections in this category
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'category_id');
    }

    /**
     * Category periods (validity dates)
     */
    public function periods(): HasMany
    {
        return $this->hasMany(CategoryPeriod::class, 'category_id');
    }

    /**
     * Specification groups for this category
     */
    public function specGroups(): HasMany
    {
        return $this->hasMany(CategorySpecGroup::class, 'category_id');
    }

    // =========================================================
    // ACCESSORS
    // =========================================================

    /**
     * Localized name
     */
    public function getLocalizedNameAttribute(): string
    {
        $isAr = app()->getLocale() === 'ar';
        $nameAr = trim((string)($this->label_ar ?? ''));
        $nameEn = trim((string)($this->label_en ?? ''));

        if ($isAr) {
            return $nameAr !== '' ? $nameAr : $nameEn;
        }
        return $nameEn !== '' ? $nameEn : $nameAr;
    }

    /**
     * Name accessor - alias for label_en
     */
    public function getNameAttribute(): string
    {
        return $this->label_en ?? '';
    }

    /**
     * Status accessor - always active
     */
    public function getStatusAttribute(): int
    {
        return 1;
    }

    /**
     * Alias: childs (legacy compatibility)
     */
    public function getChildsAttribute()
    {
        return $this->children()->orderBy('label_en')->get();
    }

    // =========================================================
    // SCOPES
    // =========================================================

    /**
     * Categories of a specific level
     */
    public function scopeLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Root categories (level 1)
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id')->orWhere('level', 1);
    }

    /**
     * Categories for a specific catalog
     */
    public function scopeForCatalog($query, int $catalogId)
    {
        return $query->where('catalog_id', $catalogId);
    }

    /**
     * Categories for a specific brand
     */
    public function scopeForBrand($query, int $brandId)
    {
        return $query->where('brand_id', $brandId);
    }
}
