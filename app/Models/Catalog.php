<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Catalog extends Model
{
    use HasFactory;

    protected $table = 'catalogs';
    protected $guarded = ['id'];

    // تحميل البراند دائمًا مع الكتالوج
    protected $with = ['brand:id,name,slug'];

    public $timestamps = true;

    protected $appends = ['localized_name'];

    // =========================================================
    // COMPATIBILITY - للتوافق مع Subcategory model القديم
    // =========================================================

    /**
     * Alias: childs → newCategories Level 1 (للتوافق مع $subcategory->childs)
     * Limited to 10 items for header performance
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
     * Localized name - الاسم حسب اللغة
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

    /**
     * 🔢 سنوات الإنتاج المتوقعة من تواريخ الكتالوج
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
     * 🔗 العلاقة مع البراند
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * 🔗 NewCategories المرتبطة بالكتالوج
     */
    public function newCategories(): HasMany
    {
        return $this->hasMany(NewCategory::class, 'catalog_id');
    }

    /**
     * 🔗 الفئات الجذرية (المستوى الأول)
     */
    public function rootCategories(): HasMany
    {
        return $this->hasMany(NewCategory::class, 'catalog_id')
                    ->where('level', 1);
    }

    /**
     * 🔗 المنطقة (BrandRegion) المرتبطة بالكتالوج
     */
    public function brandRegion(): BelongsTo
    {
        return $this->belongsTo(BrandRegion::class, 'brand_region_id');
    }

    /**
     * 🔗 الأقسام (Sections) المرتبطة بالكتالوج
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'catalog_id');
    }

    /**
     * 🏭 دعم Laravel Factories (لو كنت تستخدمه من modules)
     */
    protected static function factory()
    {
        return \Modules\CatalogItem\Database\factories\CatlogFactory::new();
    }
}
