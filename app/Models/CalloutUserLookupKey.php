<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalloutUserLookupKey extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * 🔗 الكالآوت المرتبط بهذا المفتاح الذي أدخله المستخدم
     */
    public function callout(): BelongsTo
    {
        return $this->belongsTo(Callout::class, 'callout_id');
    }
}
