<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewCategory extends Model
{
    protected $table = 'newcategories';
    public $timestamps = false;

    protected $fillable = [
        'full_code', 'formattedCode', 'slug',
        'label_en', 'label_ar', 'catalog_id',
        'brand_id', 'level', 'parent_id',
        'thumbnail', 'images',
        'spec_key', 'parents_key'
    ];

    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = str_replace(' ', '-', $value);
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
}
