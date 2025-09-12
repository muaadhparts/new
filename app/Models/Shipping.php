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
        'title',
        'subtitle',
        'price',
        // أضف أعمدة أخرى لديك هنا إن وُجدت
    ];

    /**
     * يعيد شحنات البائع + الشحنات العامة (user_id = 0)
     * ويقدّم شحنات البائع في الترتيب.
     */
    public function scopeForVendor(Builder $query, int $vendorId): Builder
    {
        return $query
            ->whereIn('user_id', [0, $vendorId])
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$vendorId]);
    }

    /**
     * سكوب للتعرّف على Tryoto مؤقتًا (يفضل لاحقًا عمود provider).
     */
    public function scopeIsTryoto(Builder $query): Builder
    {
        return $query->where('title', 'M');
    }
}
