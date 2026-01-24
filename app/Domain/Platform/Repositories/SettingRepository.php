<?php

namespace App\Domain\Platform\Repositories;

use App\Domain\Platform\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Setting Repository
 *
 * Repository for platform settings data access.
 */
class SettingRepository extends BaseRepository
{
    /**
     * Cache key prefix.
     */
    protected string $cachePrefix = 'settings:';

    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return PlatformSetting::class;
    }

    /**
     * Get a setting value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            $this->cachePrefix . $key,
            3600,
            fn() => $this->findFirstBy('key', $key)?->value ?? $default
        );
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, mixed $value): bool
    {
        $setting = $this->findFirstBy('key', $key);

        if ($setting) {
            $result = $setting->update(['value' => $value]);
        } else {
            $this->create(['key' => $key, 'value' => $value]);
            $result = true;
        }

        Cache::forget($this->cachePrefix . $key);

        return $result;
    }

    /**
     * Get multiple settings by keys.
     */
    public function getMany(array $keys): array
    {
        $settings = [];

        foreach ($keys as $key) {
            $settings[$key] = $this->get($key);
        }

        return $settings;
    }

    /**
     * Get all settings as key-value pairs.
     */
    public function getAllAsArray(): array
    {
        return $this->all()->pluck('value', 'key')->toArray();
    }

    /**
     * Clear settings cache.
     */
    public function clearCache(?string $key = null): void
    {
        if ($key) {
            Cache::forget($this->cachePrefix . $key);
        } else {
            $this->all()->each(function ($setting) {
                Cache::forget($this->cachePrefix . $setting->key);
            });
        }
    }
}
