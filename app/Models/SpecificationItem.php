<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecificationItem extends Model
{
    protected $fillable = [
        'specification_id',
        'catalog_id',
        'value_id',
        'label',
    ];

    // 🔗 العلاقة مع جدول المواصفات الرئيسية
    public function specification()
    {
        return $this->belongsTo(Specification::class, 'specification_id');
    }

    // 🔗 العلاقة مع الكتالوج
    public function catalog()
    {
        return $this->belongsTo(Catalog::class, 'catalog_id');
    }

    // 🔗 العلاقة مع الفئات (many-to-many عبر جدول الرابط)
    public function categoryLinks()
    {
        return $this->hasMany(CategorySpecificationLink::class, 'specification_item_id');
    }

    // 🔗 العلاقة مع المواصفات المستخدمة في القطع
    public function partAttributes()
    {
        return $this->hasMany(PartAttribute::class, 'specification_item_id');
    }

    // 🔗 العلاقة مع الامتدادات (extensions)
    public function partExtensions()
    {
        return $this->hasMany(PartExtension::class, 'specification_item_id');
    }
}
