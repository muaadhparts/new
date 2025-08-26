<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Illustration extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * 🔗 القسم المرتبطة به هذه الرسمة
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    /**
     * 🔗 الفئة (category) المرتبطة بهذه الرسمة
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(NewCategory::class, 'category_id');
    }

    /**
     * 🔗 callouts المرتبطة بهذه الرسمة (مثلاً أرقام الأجزاء المرسومة)
     */
    public function callouts(): HasMany
    {
        return $this->hasMany(Callout::class, 'illustration_id');
    }
}
