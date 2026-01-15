<?php

namespace App\Services\GlobalData\Contexts;

use App\Models\Font;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * CoreSettingsContext
 *
 * إعدادات النظام الأساسية:
 * - muaadhsettings
 * - frontend_settings
 * - seotools
 * - socialsettings
 * - default_font
 */
class CoreSettingsContext implements ContextInterface
{
    private ?object $settings = null;
    private ?object $frontendSettings = null;
    private ?object $seoSettings = null;
    private ?object $socialSettings = null;
    private ?Font $defaultFont = null;

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

        $this->socialSettings = Cache::remember('socialsettings', 3600, fn() =>
            DB::table('socialsettings')->first()
        );

        $this->defaultFont = Cache::remember('default_font', 3600, fn() =>
            Font::where('is_default', 1)->first()
        );
    }

    public function toArray(): array
    {
        return [
            'gs' => $this->settings,
            'ps' => $this->frontendSettings,
            'seo' => $this->seoSettings,
            'socialsetting' => $this->socialSettings,
            'default_font' => $this->defaultFont,
        ];
    }

    public function reset(): void
    {
        $this->settings = null;
        $this->frontendSettings = null;
        $this->seoSettings = null;
        $this->socialSettings = null;
        $this->defaultFont = null;
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

    public function getSocialSettings(): ?object
    {
        return $this->socialSettings;
    }

    public function getDefaultFont(): ?Font
    {
        return $this->defaultFont;
    }
}
