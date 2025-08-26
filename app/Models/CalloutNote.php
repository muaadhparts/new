<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalloutNote extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * 🔗 الكالآوت المرتبط بهذه الملاحظة
     */
    public function callout(): BelongsTo
    {
        return $this->belongsTo(Callout::class, 'callout_id');
    }
}
