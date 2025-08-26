<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
     * 🔗 المستويات المرتبطة بالكتالوج (إذا موجودة)
     */
    public function levels(): BelongsToMany
    {
        return $this->belongsToMany(Level::class, 'catalog_level')
                    ->withPivot('catalog_id', 'level_id')
                    ->withTimestamps();
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
     * 🏭 دعم Laravel Factories (لو كنت تستخدمه من modules)
     */
    protected static function factory()
    {
        return \Modules\Product\Database\factories\CatlogFactory::new();
    }
}
