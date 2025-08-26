<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $table = 'sections';

    protected $fillable = [
        'code',
        'catalog_id',
        'full_code',
        'formattedCode',
        'category_id',
    ];

    /**
     * 🔗 الكتالوج المرتبط بالقسم
     */
    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'catalog_id');
    }

    /**
     * 🔗 الفئة (category) المرتبطة بهذا القسم
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(NewCategory::class, 'category_id');
    }

    /**
     * 🔗 القطع المرتبطة بهذا القسم
     */
    public function sectionParts(): HasMany
    {
        return $this->hasMany(SectionPart::class, 'section_id');
    }

    /**
     * 🔗 الامتدادات الخاصة بالقطع ضمن هذا القسم
     */
    public function partExtensions(): HasMany
    {
        return $this->hasMany(PartExtension::class, 'section_id');
    }

    /**
     * 🖼️ الرسومات التوضيحية المرتبطة بالقسم
     */
    public function illustrations(): HasMany
    {
        return $this->hasMany(Illustration::class, 'section_id');
    }
}
