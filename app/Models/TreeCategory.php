<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TreeCategory Model - الشجرة الموحدة للفئات
 *
 * الهيكل: Brand → Catalog → TreeCategory (3 مستويات)
 * يحل محل: categories, subcategories, childcategories
 */
class TreeCategory extends Model
{
    protected $table = 'treecategories';
    public $timestamps = false;

    protected $fillable = [
        'slug', 'label_en', 'label_ar',
        'brand_id', 'catalog_id',
        'parent_id', 'level', 'path',
        'full_code', 'thumbnail', 'keywords'
    ];

    protected $appends = ['localized_name', 'name'];

    // =========================================================
    // ACCESSORS - للتوافق مع Views
    // =========================================================

    /**
     * Localized name - الاسم حسب اللغة
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

    // =========================================================
    // RELATIONSHIPS - العلاقات
    // =========================================================

    /**
     * البراند
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * الكتالوج
     */
    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'catalog_id');
    }

    /**
     * الأب المباشر
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(TreeCategory::class, 'parent_id');
    }

    /**
     * الأبناء المباشرين
     */
    public function children(): HasMany
    {
        return $this->hasMany(TreeCategory::class, 'parent_id');
    }

    /**
     * Alias: childs للتوافق مع الكود القديم
     */
    public function getChildsAttribute()
    {
        return $this->children()->orderBy('label_en')->get();
    }

    /**
     * الأقسام (Sections) المرتبطة بهذا التصنيف
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'category_id');
    }

    // =========================================================
    // SCOPES - للاستعلامات
    // =========================================================

    /**
     * الفئات من مستوى معين
     */
    public function scopeLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    /**
     * الفئات الجذرية (المستوى الأول)
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id')->orWhere('level', 1);
    }

    /**
     * فئات كتالوج معين
     */
    public function scopeForCatalog($query, int $catalogId)
    {
        return $query->where('catalog_id', $catalogId);
    }

    /**
     * فئات براند معين
     */
    public function scopeForBrand($query, int $brandId)
    {
        return $query->where('brand_id', $brandId);
    }
}
