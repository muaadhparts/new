<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    protected $table = 'tokens';

    protected $fillable = ['accessToken', 'refreshToken', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public $timestamps = true;

    public static function valid()
    {
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
