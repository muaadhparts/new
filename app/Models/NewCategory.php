<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * NewCategory Model - الشجرة الموحدة للفئات
 *
 * الهيكل: Brand → Catalog → NewCategory (3 مستويات)
 * يحل محل: categories, subcategories, childcategories, treecategories
 */
class NewCategory extends Model
{
    protected $table = 'newcategories';
    public $timestamps = false;

    protected $fillable = [
        'full_code', 'formattedCode', 'slug',
        'label_en', 'label_ar', 'catalog_id',
        'brand_id', 'level', 'parent_id',
        'thumbnail', 'images',
        'spec_key', 'parents_key', 'path', 'keywords'
    ];

    protected $appends = ['localized_name', 'name'];

    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = str_replace(' ', '-', $value);
    }

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

    /**
     * Alias: childs للتوافق مع الكود القديم
     */
    public function getChildsAttribute()
    {
        return $this->children()->orderBy('label_en')->get();
    }

    /**
     * 🔗 روابط المواصفات (غير مستخدمة بعد الآن)
     * ❌ تم حذفها: specificationItems() كانت تعتمد على جدول غير مستخدم
     */

    /**
     * 🔗 تواريخ صلاحية هذا التصنيف
     */
    public function periods(): HasMany
    {
        return $this->hasMany(CategoryPeriod::class, 'category_id');
    }

    /**
     * 🔗 التصنيفات الفرعية
     */
    public function children(): HasMany
    {
        return $this->hasMany(NewCategory::class, 'parent_id');
    }

    /**
     * 🔗 التصنيف الأب المباشر
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(NewCategory::class, 'parents_key', 'spec_key');
    }

    /**
     * 🔗 التصنيف الأب الحقيقي (عبر الحقل parent_id)
     */
    public function trueParent(): BelongsTo
    {
        return $this->belongsTo(NewCategory::class, 'parent_id');
    }

    /**
     * 🔗 العلاقة مع الكتالوج
     */
    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'catalog_id');
    }

    /**
     * 🔗 العلاقة مع البراند
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * 🔗 الأقسام (Sections) المرتبطة بهذا التصنيف
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'category_id');
    }

    /**
     * 🔗 مجموعات المواصفات المرتبطة بهذا التصنيف
     */
    public function specGroups()
    {
        return $this->hasMany(\App\Models\CategorySpecGroup::class, 'category_id');
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
