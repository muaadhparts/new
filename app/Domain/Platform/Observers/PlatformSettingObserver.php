<?php

namespace App\Domain\Platform\Observers;

use App\Domain\Platform\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Platform Setting Observer
 *
 * Handles PlatformSetting model lifecycle events.
 */
class PlatformSettingObserver
{
    /**
     * Handle the PlatformSetting "created" event.
     */
    public function created(PlatformSetting $setting): void
    {
        $this->clearSettingsCache($setting);
    }

    /**
     * Handle the PlatformSetting "updated" event.
     */
    public function updated(PlatformSetting $setting): void
    {
        $this->clearSettingsCache($setting);
    }

    /**
     * Handle the PlatformSetting "deleted" event.
     */
    public function deleted(PlatformSetting $setting): void
    {
        $this->clearSettingsCache($setting);
    }

    /**
     * Clear settings-related cache.
     */
    protected function clearSettingsCache(PlatformSetting $setting): void
    {
        Cache::forget('platform_settings');
        Cache::forget("setting_{$setting->key}");

        // Clear group-specific cache
        if ($setting->group) {
            Cache::forget("settings_group_{$setting->group}");
        }
    }
}
