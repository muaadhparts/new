<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DiscountCode Model - أكواد الخصم
 */
class DiscountCode extends Model
{
    protected $table = 'discount_codes';

    protected $fillable = [
        'code',
        'type',
        'price',
        'times',
        'used',
        'status',
        'start_date',
        'end_date',
        'user_id'
    ];

    public $timestamps = false;

    /**
     * العلاقة مع التاجر
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * التحقق من صلاحية الكود
     */
    public function isValid(): bool
    {
        $now = now()->format('Y-m-d');

        // التحقق من الحالة
        if ($this->status != 1) {
            return false;
        }

        // التحقق من التاريخ
        if ($this->start_date > $now || $this->end_date < $now) {
            return false;
        }

        // التحقق من عدد الاستخدامات (إذا كان محدوداً)
        if ($this->times > 0 && $this->used >= $this->times) {
            return false;
        }

        return true;
    }
}
