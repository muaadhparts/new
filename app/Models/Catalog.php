<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use App\Models\NewCategory;

class Catalog extends Model
{
    use HasFactory;

    protected $table = 'catalogs';
    protected $guarded = ['id'];

    // تحميل البراند دائمًا مع الكتالوج
    protected $with = ['brand:id,name'];

    public $timestamps = true;

    // =========================================================
    // COMPATIBILITY ACCESSORS - للتوافق مع views القديمة
    // التي كانت تستخدم Subcategory model
    // =========================================================

    /**
     * Slug accessor - يولد slug من الاسم للتوافق مع Subcategory slugs
     * مثال: "SAFARI PATROL ( 1997 - )" → "safari-patrol-1997"
     */
    public function getSlugAttribute(): string
    {
        // إذا كان هناك subcategory بنفس الـ ID، نستخدم slug-ها
        // لأن Catalog IDs = Subcategory IDs
        static $subcategorySlugs = null;

        if ($subcategorySlugs === null) {
            $subcategorySlugs = \App\Models\Subcategory::pluck('slug', 'id')->toArray();
        }

        if (isset($subcategorySlugs[$this->id])) {
            return $subcategorySlugs[$this->id];
        }

        // Fallback: generate slug from name
        $name = $this->name ?? '';
        // Remove parentheses and their content, clean up
        $name = preg_replace('/\s*\([^)]*\)/', '', $name);
        $name = trim($name);
        $slug = strtolower(str_replace(' ', '-', $name));
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * Childs accessor - يُرجع collection فارغة
     * للتوافق مع $subcategory->childs
     * (Catalog ليس له مستوى ثالث في الهيكل الجديد)
     */
    public function getChildsAttribute()
    {
        return collect([]);
    }

    /**
     * Status accessor - دائماً active
     */
    public function getStatusAttribute(): int
    {
        return 1;
    }

    /**
     * Localized name accessor
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
     * 🔗 الفئات المرتبطة بالكتالوج (من جدول newcategories)
     */
    public function categories(): HasMany
    {
        return $this->hasMany(NewCategory::class, 'catalog_id');
    }

    /**
     * 🔗 الفئات الرئيسية فقط (اللي ما لها أب)
     */
    public function parentCategories(): HasMany
    {
        return $this->hasMany(NewCategory::class, 'catalog_id')
                    ->whereNull('parent_id');
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
        return \Modules\Product\Database\factories\CatlogFactory::new();
    }
}
