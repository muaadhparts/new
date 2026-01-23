<?php

namespace App\Services\GlobalData\Contexts;

use App\Services\PlatformSettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CoreSettingsContext
 *
 * إعدادات النظام الأساسية - UNIFIED SETTINGS SYSTEM
 *
 * يستخدم platform_settings الجديد مع fallback للجداول القديمة
 * أثناء فترة الانتقال. بعد اكتمال النقل، سيتم حذف الـ fallback.
 *
 * الجداول القديمة (سيتم حذفها):
 * - muaadhsettings → platform_settings
 * - frontend_settings → تم حذفه
 * - seotools → platform_settings
 * - connect_configs → platform_settings
 * - typefaces → حذف (لا خطوط مخصصة)
 */
class CoreSettingsContext implements ContextInterface
{
    private ?object $settings = null;
    private ?object $seoSettings = null;

    public function load(): void
    {
        // Try new system first, fallback to old
        $this->settings = $this->loadSettings();
        $this->seoSettings = $this->loadSeoSettings();
    }

    /**
     * Load settings - prefer new system, fallback to old
     */
    private function loadSettings(): ?object
    {
        return Cache::remember('core_settings_unified', 3600, function () {
            // Try new platform_settings system
            if (Schema::hasTable('platform_settings')) {
                try {
                    $service = app(PlatformSettingsService::class);
                    return $service->all();
                } catch (\Exception $e) {
                    // Fall through to legacy
                }
            }

            // Fallback to old muaadhsettings
            if (Schema::hasTable('muaadhsettings')) {
                return DB::table('muaadhsettings')->first();
            }

            return (object) [];
        });
    }

    /**
     * Load SEO settings
     */
    private function loadSeoSettings(): ?object
    {
        return Cache::remember('core_seo_unified', 3600, function () {
            // Try new platform_settings
            if (Schema::hasTable('platform_settings')) {
                $seo = DB::table('platform_settings')
                    ->where('group', 'seo')
                    ->get()
                    ->keyBy('key')
                    ->map(fn($item) => json_decode($item->value))
                    ->toArray();

                if (!empty($seo)) {
                    return (object) $seo;
                }
            }

            // Fallback to old seotools
            if (Schema::hasTable('seotools')) {
                return DB::table('seotools')->first();
            }

            return (object) [];
        });
    }

    public function toArray(): array
    {
        return [
            'gs' => $this->settings,
            'seo' => $this->seoSettings,
        ];
    }

    public function reset(): void
    {
        $this->settings = null;
        $this->seoSettings = null;
        Cache::forget('core_settings_unified');
        Cache::forget('core_seo_unified');
    }

    // === Getters ===

    public function getSettings(): ?object
    {
        return $this->settings;
    }

    public function getSeoSettings(): ?object
    {
        return $this->seoSettings;
    }
}
