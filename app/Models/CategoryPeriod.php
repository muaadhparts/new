<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryPeriod extends Model
{
    protected $table = 'category_periods';

    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'begin_date',
        'end_date',
    ];

    /**
     * 🔗 علاقة مع الفئة (category)
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(NewCategory::class, 'category_id');
    }

    /**
     * 🔗 جميع روابط المواصفات المرتبطة بهذه الفترة
     */
    public function specificationLinks(): HasMany
    {
        return $this->hasMany(CategorySpecificationLink::class, 'category_period_id');
    }
}
