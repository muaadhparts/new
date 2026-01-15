<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Model;

// class Shipping extends Model
// {
//     protected $fillable = ['user_id', 'title', 'subtitle', 'price'];

//     public $timestamps = false;


// }


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Shipping extends Model
{
    protected $table = 'shippings';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'integration_type', // none, manual, api
        'provider',
        'title',
        'subtitle',
        'price',
        'free_above',
        'status',
    ];

    /**
     * يعيد شحنات التاجر + الشحنات العامة (الأوبريتور)
     * user_id = 0 (operator/platform) - متاحة لجميع التجار
     * user_id = $merchantId - شحنات التاجر الخاصة
     * ويقدّم شحنات التاجر في الترتيب.
     */
    public function scopeForMerchant(Builder $query, int $merchantId): Builder
    {
        return $query
            ->whereIn('user_id', [0, $merchantId])
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$merchantId]);
    }

    /**
     * سكوب للتعرّف على Tryoto مؤقتًا (يفضل لاحقًا عمود provider).
     */
    public function scopeIsTryoto(Builder $query): Builder
    {
        return $query->where('title', 'M');
    }
}
