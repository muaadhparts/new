<?php

namespace App\Domain\Merchant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use App\Domain\Identity\Models\User;

/**
 * MerchantSetting Model - Per-merchant configuration
 *
 * Domain: Merchant
 * Table: merchant_settings
 *
 * @property int $id
 * @property int $merchant_id
 * @property string $group
 * @property string $key
 * @property mixed $value
 * @property string $type
 */
class MerchantSetting extends Model
{
    protected $table = 'merchant_settings';

    protected $fillable = [
        'merchant_id',
        'group',
        'key',
        'value',
        'type',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    const CACHE_PREFIX = 'merchant_settings';
    const CACHE_TTL = 3600;

    // =========================================================
    // RELATIONS
    // =========================================================

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    // =========================================================
    // STATIC METHODS
    // =========================================================

    public static function get(int $merchantId, string $group, string $key, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . ":{$merchantId}:{$group}:{$key}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($merchantId, $group, $key, $default) {
            $setting = static::where('merchant_id', $merchantId)
                ->where('group', $group)
                ->where('key', $key)
                ->first();

            if (!$setting) {
                return $default;
            }

            return self::castValue($setting->value, $setting->type);
        });
    }

    public static function set(int $merchantId, string $group, string $key, $value, string $type = 'string'): self
    {
        $setting = static::updateOrCreate(
            ['merchant_id' => $merchantId, 'group' => $group, 'key' => $key],
            ['value' => $value, 'type' => $type]
        );

        self::clearMerchantCache($merchantId, $group, $key);

        return $setting;
    }

    public static function getGroup(int $merchantId, string $group): array
    {
        $cacheKey = self::CACHE_PREFIX . ":{$merchantId}:group:{$group}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($merchantId, $group) {
            $settings = static::where('merchant_id', $merchantId)
                ->where('group', $group)
                ->get();

            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = self::castValue($setting->value, $setting->type);
            }

            return $result;
        });
    }

    public static function getAllForMerchant(int $merchantId): array
    {
        $cacheKey = self::CACHE_PREFIX . ":{$merchantId}:all";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($merchantId) {
            $settings = static::where('merchant_id', $merchantId)->get();

            $result = [];
            foreach ($settings as $setting) {
                if (!isset($result[$setting->group])) {
                    $result[$setting->group] = [];
                }
                $result[$setting->group][$setting->key] = self::castValue($setting->value, $setting->type);
            }

            return $result;
        });
    }

    private static function castValue($value, string $type)
    {
        switch ($type) {
            case 'boolean':
                return (bool) $value;
            case 'integer':
                return (int) $value;
            case 'decimal':
            case 'float':
                return (float) $value;
            case 'json':
            case 'array':
                return is_array($value) ? $value : json_decode($value, true);
            default:
                return $value;
        }
    }

    public static function clearMerchantCache(int $merchantId, string $group = null, string $key = null): void
    {
        if ($key && $group) {
            Cache::forget(self::CACHE_PREFIX . ":{$merchantId}:{$group}:{$key}");
        }
        if ($group) {
            Cache::forget(self::CACHE_PREFIX . ":{$merchantId}:group:{$group}");
        }
        Cache::forget(self::CACHE_PREFIX . ":{$merchantId}:all");
    }
}
