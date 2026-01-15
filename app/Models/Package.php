<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Package extends Model
{
    protected $table = 'packages';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'title',
        'subtitle',
        'price',
    ];

    /**
     * يعيد تغليفات التاجر + التغليفات العامة (الأوبريتور)
     * user_id = 0 (operator/platform) - متاحة لجميع التجار
     * user_id = $merchantId - تغليفات التاجر الخاصة
     * ويقدّم تغليفات التاجر في الترتيب.
     */
    public function scopeForMerchant(Builder $query, int $merchantId): Builder
    {
        return $query
            ->whereIn('user_id', [0, $merchantId])
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$merchantId]);
    }

    /**
     * فقط تغليفات المنصة (الأوبريتور)
     */
    public function scopePlatformOnly(Builder $query): Builder
    {
        return $query->where('user_id', 0);
    }
}