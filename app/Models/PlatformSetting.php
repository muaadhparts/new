<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * ============================================================================
 * PLATFORM SETTING MODEL
 * ============================================================================
 *
 * Single source of truth for all platform-wide configuration.
 * Replaces: muaadhsettings, settings, seotools, home_page_themes, typefaces,
 *           connect_configs, languages, frontend_settings
 *
 * Usage:
 * ------
 * PlatformSetting::get('branding', 'logo');
 * PlatformSetting::set('branding', 'logo', 'path/to/logo.png');
 * PlatformSetting::getGroup('mail');
 *
 * ============================================================================
 */
class PlatformSetting extends Model
{
    protected $table = 'platform_settings';

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    /**
     * Cache key prefix
     */
    const CACHE_PREFIX = 'platform_settings';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Get a setting value
     *
     * @param string $group Setting group (branding, mail, etc)
     * @param string $key Setting key within group
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public static function get(string $group, string $key, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . ":{$group}:{$key}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($group, $key, $default) {
            $setting = static::where('group', $group)
                ->where('key', $key)
                ->first();

            if (!$setting) {
                return $default;
            }

            return self::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value
     *
     * @param string $group
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @return static
     */
    public static function set(string $group, string $key, $value, string $type = 'string'): self
    {
        $setting = static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $value, 'type' => $type]
        );

        // Clear cache
        Cache::forget(self::CACHE_PREFIX . ":{$group}:{$key}");
        Cache::forget(self::CACHE_PREFIX . ":group:{$group}");
        Cache::forget(self::CACHE_PREFIX . ':all');

        return $setting;
    }

    /**
     * Get all settings in a group
     *
     * @param string $group
     * @return array
     */
    public static function getGroup(string $group): array
    {
        $cacheKey = self::CACHE_PREFIX . ":group:{$group}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($group) {
            $settings = static::where('group', $group)->get();

            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = self::castValue($setting->value, $setting->type);
            }

            return $result;
        });
    }

    /**
     * Get all settings as nested array
     *
     * @return array
     */
    public static function getAll(): array
    {
        $cacheKey = self::CACHE_PREFIX . ':all';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $settings = static::all();

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

    /**
     * Cast value based on type
     *
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    private static function castValue($value, string $type)
    {
        // Value is already decoded from JSON cast
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
            case 'file':
            case 'string':
            default:
                return $value;
        }
    }

    /**
     * Clear all cached settings
     */
    public static function clearCache(): void
    {
        // Clear pattern-based cache (if Redis/Memcached)
        Cache::forget(self::CACHE_PREFIX . ':all');

        // Clear all known groups
        $groups = ['branding', 'mail', 'currency', 'affiliate', 'withdraw',
                   'features', 'security', 'maintenance', 'merchant', 'debug',
                   'stats', 'contact', 'seo', 'social_login', 'localization'];

        foreach ($groups as $group) {
            Cache::forget(self::CACHE_PREFIX . ":group:{$group}");
        }
    }

    // =========================================================================
    // CONVENIENCE METHODS (backward compatibility helpers)
    // =========================================================================

    /**
     * Get branding settings (replaces $gs->logo, etc)
     */
    public static function branding(string $key = null, $default = null)
    {
        if ($key) {
            return self::get('branding', $key, $default);
        }
        return (object) self::getGroup('branding');
    }

    /**
     * Get mail settings
     */
    public static function mail(string $key = null, $default = null)
    {
        if ($key) {
            return self::get('mail', $key, $default);
        }
        return (object) self::getGroup('mail');
    }

    /**
     * Get currency settings
     */
    public static function currency(string $key = null, $default = null)
    {
        if ($key) {
            return self::get('currency', $key, $default);
        }
        return (object) self::getGroup('currency');
    }

    /**
     * Get feature toggles
     */
    public static function feature(string $key, $default = false): bool
    {
        return (bool) self::get('features', $key, $default);
    }

    /**
     * Check if maintenance mode is enabled
     */
    public static function isMaintenanceMode(): bool
    {
        return (bool) self::get('maintenance', 'is_enabled', false);
    }
}
