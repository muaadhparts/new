<?php

namespace App\Domain\Platform\Services\GlobalData\Contexts;

use App\Domain\Platform\Models\FrontendSetting;
use App\Domain\Platform\Models\PlatformSetting;
use App\Domain\Platform\Services\PlatformSettingsService;
use Illuminate\Support\Facades\Cache;

/**
 * CoreSettingsContext
 *
 * UNIFIED SETTINGS SYSTEM - NO LEGACY FALLBACK
 *
 * This context provides platform settings via PlatformSettingsService ONLY.
 * No fallback to muaadhsettings or any legacy table.
 *
 * Variables provided to views:
 * - $gs: PlatformSettingsService (general settings)
 * - $ps: FrontendSetting (page/frontend settings)
 * - $platformSettings: alias for $gs
 * - $seo: SEO settings object
 */
class CoreSettingsContext implements ContextInterface
{
    private ?object $settings = null;
    private ?array $seoSettings = null;
    private ?FrontendSetting $frontendSettings = null;

    public function load(): void
    {
        $this->settings = $this->loadSettings();
        $this->seoSettings = $this->loadSeoSettings();
        $this->frontendSettings = $this->loadFrontendSettings();
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
            return PlatformSetting::getGroup('seo');
        });
    }

    /**
     * Load frontend/page settings from frontend_settings table
     */
    private function loadFrontendSettings(): FrontendSetting
    {
        return Cache::remember('frontend_settings', 3600, function () {
            return FrontendSetting::firstOrCreate(['id' => 1], [
                'contact_email' => '',
                'street' => '',
                'phone' => '',
                'fax' => '',
                'email' => '',
            ]);
        });
    }

    public function toArray(): array
    {
        return [
            'gs' => $this->settings,
            'ps' => $this->frontendSettings,
            'platformSettings' => $this->settings,
            'seo' => (object) ($this->seoSettings ?? []),
            // Pre-computed admin_loader to avoid ->get() calls in views
            'adminLoader' => $this->frontendSettings->admin_loader ?? 'loader.gif',
        ];
    }

    public function reset(): void
    {
        $this->settings = null;
        $this->seoSettings = null;
        $this->frontendSettings = null;
        Cache::forget('platform_settings_context');
        Cache::forget('platform_seo_settings');
        Cache::forget('platform_social_links');
        Cache::forget('frontend_settings');
    }

    public function getSettings(): ?object
    {
        return $this->settings;
    }

    public function getSeoSettings(): ?object
    {
        return (object) ($this->seoSettings ?? []);
    }

    public function getFrontendSettings(): ?FrontendSetting
    {
        return $this->frontendSettings;
    }

    /**
     * Get social links from platform_settings
     * Returns object with facebook, twitter, linkedin, etc.
     */
    public function getSocialLinks(): ?object
    {
        return Cache::remember('platform_social_links', 3600, function () {
            $socialLinks = PlatformSetting::getGroup('social_links');
            return (object) $socialLinks;
        });
    }
}
