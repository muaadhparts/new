<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Callout extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * 🔗 الرسمة (Illustration) التي ينتمي لها هذا الكالآوت
     */
    public function illustration(): BelongsTo
    {
        return $this->belongsTo(Illustration::class, 'illustration_id');
    }

    /**
     * 📝 الملاحظات المرتبطة بالكالآوت
     */
    public function notes(): HasMany
    {
        return $this->hasMany(CalloutNote::class, 'callout_id');
    }

    /**
     * 🔑 كلمات البحث أو المفاتيح التي أدخلها المستخدم لهذا الكالآوت
     */
    public function userKeys(): HasMany
    {
        return $this->hasMany(CalloutUserLookupKey::class, 'callout_id');
    }
}
