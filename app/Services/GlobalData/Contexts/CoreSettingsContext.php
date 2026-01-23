<?php

namespace App\Services\GlobalData\Contexts;

use App\Services\PlatformSettingsService;
use Illuminate\Support\Facades\Cache;

/**
 * CoreSettingsContext
 *
 * UNIFIED SETTINGS SYSTEM - NO LEGACY FALLBACK
 *
 * This context provides platform settings via PlatformSettingsService ONLY.
 * No fallback to muaadhsettings or any legacy table.
 *
 * Usage in views: platformSetting('group', 'key') or app(PlatformSettingsService::class)
 * DO NOT use $gs - it is banned.
 */
class CoreSettingsContext implements ContextInterface
{
    private ?object $settings = null;
    private ?array $seoSettings = null;

    public function load(): void
    {
        $this->settings = $this->loadSettings();
        $this->seoSettings = $this->loadSeoSettings();
    }

    /**
     * Load settings from PlatformSettingsService ONLY
     * NO FALLBACK - fails explicitly if service unavailable
     */
    private function loadSettings(): object
    {
        return Cache::remember('platform_settings_context', 3600, function () {
            $service = app(PlatformSettingsService::class);
            return $service->all();
        });
    }

    /**
     * Load SEO settings from platform_settings ONLY
     */
    private function loadSeoSettings(): array
    {
        return Cache::remember('platform_seo_settings', 3600, function () {
            return \App\Models\PlatformSetting::getGroup('seo');
        });
    }

    public function toArray(): array
    {
        return [
            'platformSettings' => $this->settings,
            'seo' => $this->seoSettings,
        ];
    }

    public function reset(): void
    {
        $this->settings = null;
        $this->seoSettings = null;
        Cache::forget('platform_settings_context');
        Cache::forget('platform_seo_settings');
    }

    public function getSettings(): ?object
    {
        return $this->settings;
    }

    public function getSeoSettings(): ?array
    {
        return $this->seoSettings;
    }
}
