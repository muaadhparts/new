<?php

namespace App\Services\GlobalData\Contexts;

use App\Models\Typeface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * CoreSettingsContext
 *
 * إعدادات النظام الأساسية:
 * - muaadhsettings
 * - frontend_settings
 * - seotools
 * - connect_configs
 * - default_typeface
 */
class CoreSettingsContext implements ContextInterface
{
    private ?object $settings = null;
    private ?object $frontendSettings = null;
    private ?object $seoSettings = null;
    private ?object $connectConfig = null;
    private ?Typeface $defaultTypeface = null;

    public function load(): void
    {
        $this->settings = Cache::remember('muaadhsettings', 3600, fn() =>
            DB::table('muaadhsettings')->first()
        );

        $this->frontendSettings = Cache::remember('frontend_settings', 3600, fn() =>
            DB::table('frontend_settings')->first()
        );

        $this->seoSettings = Cache::remember('seotools', 3600, fn() =>
            DB::table('seotools')->first()
        );

        $this->connectConfig = Cache::remember('connect_configs', 3600, fn() =>
            DB::table('connect_configs')->first()
        );

        $this->defaultTypeface = Cache::remember('default_typeface', 3600, fn() =>
            Typeface::where('is_default', 1)->first()
        );
    }

    public function toArray(): array
    {
        return [
            'gs' => $this->settings,
            'ps' => $this->frontendSettings,
            'seo' => $this->seoSettings,
            'connectConfig' => $this->connectConfig,
            'default_typeface' => $this->defaultTypeface,
        ];
    }

    public function reset(): void
    {
        $this->settings = null;
        $this->frontendSettings = null;
        $this->seoSettings = null;
        $this->connectConfig = null;
        $this->defaultTypeface = null;
    }

    // === Getters ===

    public function getSettings(): ?object
    {
        return $this->settings;
    }

    public function getFrontendSettings(): ?object
    {
        return $this->frontendSettings;
    }

    public function getSeoSettings(): ?object
    {
        return $this->seoSettings;
    }

    public function getConnectConfig(): ?object
    {
        return $this->connectConfig;
    }

    public function getDefaultTypeface(): ?Typeface
    {
        return $this->defaultTypeface;
    }
}
