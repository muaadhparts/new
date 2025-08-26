<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Token extends Model
{
    protected $table = 'tokens';

    // ✅ حذف الحقل cookie لأنه لم يعد يُستخدم
    protected $fillable = ['accessToken', 'refreshToken', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public $timestamps = true;

    public static function valid()
    {
        // نجلب آخر توكن صالح، بشرط أن تنتهي صلاحيته بعد دقيقة على الأقل
        return static::where('expires_at', '>', now()->addMinute())
                    ->latest('id')
                    ->first();
    }

    public function isExpired(): bool
    {
        return $this->expires_at <= now();
    }

    public static function latestRefreshToken()
    {
        return static::whereNotNull('refreshToken')->latest('id')->value('refreshToken');
    }
}
