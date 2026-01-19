<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Shipping extends Model
{
    protected $table = 'shippings';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'operator',
        'integration_type', // none, manual, api
        'provider',
        'name',
        'subname',
        'price',
        'free_above',
        'status',
    ];

    /**
     * يعيد شحنات التاجر المتاحة له
     *
     * المنطق:
     * | user_id | operator    | المعنى                                    |
     * |---------|-------------|-------------------------------------------|
     * | 0       | 0           | موقف/معطّل - لا يظهر لأحد                 |
     * | 0       | merchant_id | شحنة المنصة مُفعّلة لتاجر معين             |
     * | merchant_id | 0       | شحنة خاصة بالتاجر (أضافها بنفسه)          |
     *
     * الأولوية: شحنات التاجر الخاصة أولاً، ثم شحنات المنصة
     */
    public function scopeForMerchant(Builder $query, int $merchantId): Builder
    {
        return $query
            ->where('status', 1)
            ->where(function ($q) use ($merchantId) {
                // 1. شحنات التاجر الخاصة (user_id = merchantId)
                $q->where('user_id', $merchantId)
                // 2. أو شحنات المنصة المُفعّلة لهذا التاجر (user_id = 0 AND operator = merchantId)
                ->orWhere(function ($q2) use ($merchantId) {
                    $q2->where('user_id', 0)
                       ->where('operator', $merchantId);
                });
            })
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$merchantId]);
    }

    /**
     * شحنات المنصة فقط (للإدارة)
     */
    public function scopePlatformOnly(Builder $query): Builder
    {
        return $query->where('user_id', 0);
    }

    /**
     * هل هذه الشحنة تابعة للمنصة؟
     */
    public function isPlatformOwned(): bool
    {
        return $this->user_id === 0 || $this->user_id === null;
    }

    /**
     * هل هذه الشحنة تابعة لتاجر محدد؟
     */
    public function isMerchantOwned(int $merchantId): bool
    {
        return $this->user_id > 0 && $this->user_id === $merchantId;
    }

    /**
     * هل هذه الشحنة مُفعّلة لتاجر معين؟
     */
    public function isEnabledForMerchant(int $merchantId): bool
    {
        // شحنة التاجر الخاصة
        if ($this->user_id === $merchantId) {
            return true;
        }

        // شحنة المنصة المُفعّلة لهذا التاجر
        if ($this->user_id === 0 && $this->operator === $merchantId) {
            return true;
        }

        return false;
    }

}
