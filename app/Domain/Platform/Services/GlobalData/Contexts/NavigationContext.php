<?php

namespace App\Domain\Platform\Services\GlobalData\Contexts;

use App\Domain\Catalog\Models\Brand;
use App\Domain\Platform\Models\MonetaryUnit;
use App\Domain\Platform\Models\Language;
use App\Domain\Platform\Models\Page;
use App\Domain\Platform\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;

/**
 * NavigationContext
 *
 * بيانات التنقل (Header/Footer):
 * - Brands مع Catalogs
 * - Policy pages (terms, privacy, refund)
 * - قوائم العملات واللغات
 */
class NavigationContext implements ContextInterface
{
    private $brands = null;
    private $pages = null;
    private $monetaryUnits = null;
    private $languages = null;
    private $connectConfig = null;

    public function load(): void
    {
        // Brand → Catalog (الهيكل المعتمد)
        $this->brands = Cache::remember('nav_brands_catalogs', 3600, fn() =>
            Brand::where('status', 1)
                ->with(['catalogs' => fn($q) =>
                    $q->where('status', 1)
                      ->select('id', 'brand_id', 'slug', 'name', 'name_ar', 'status')
                      ->orderBy('name')
                ])
                ->orderBy('name')
                ->get(['id', 'slug', 'name', 'name_ar', 'status', 'photo'])
        );

        // Policy pages only (terms, privacy, refund)
        $this->pages = Cache::remember('policy_pages', 3600, fn() =>
            Page::where('is_active', true)->get()
        );

        $this->monetaryUnits = Cache::remember('all_monetary_units', 3600, fn() =>
            MonetaryUnit::all()
        );

        $this->languages = Cache::remember('all_languages', 3600, fn() =>
            Language::all()
        );

        // ConnectConfig - legacy variable from connect_configs table
        // Now loaded from platform_settings (social_links + social_login groups)
        $this->connectConfig = Cache::remember('connect_config_legacy', 3600, function () {
            $socialLinks = PlatformSetting::getGroup('social_links');
            $socialLogin = PlatformSetting::getGroup('social_login');

            return (object) [
                // Social links
                'facebook' => $socialLinks['facebook'] ?? null,
                'twitter' => $socialLinks['twitter'] ?? null,
                'linkedin' => $socialLinks['linkedin'] ?? null,
                'gplus' => $socialLinks['google_plus'] ?? null,
                'instagram' => $socialLinks['instagram'] ?? null,
                'youtube' => $socialLinks['youtube'] ?? null,
                'dribble' => $socialLinks['dribble'] ?? null,
                'f_status' => $socialLinks['facebook_status'] ?? 0,
                'g_status' => $socialLinks['google_plus_status'] ?? 0,
                't_status' => $socialLinks['twitter_status'] ?? 0,
                'l_status' => $socialLinks['linkedin_status'] ?? 0,
                'd_status' => $socialLinks['dribble_status'] ?? 0,
                // Social login
                'f_check' => $socialLogin['facebook_enabled'] ?? 0,
                'g_check' => $socialLogin['google_enabled'] ?? 0,
                'fclient_id' => $socialLogin['facebook_client_id'] ?? null,
                'fclient_secret' => $socialLogin['facebook_client_secret'] ?? null,
                'fredirect' => $socialLogin['facebook_redirect'] ?? null,
                'gclient_id' => $socialLogin['google_client_id'] ?? null,
                'gclient_secret' => $socialLogin['google_client_secret'] ?? null,
                'gredirect' => $socialLogin['google_redirect'] ?? null,
            ];
        });
    }

    public function toArray(): array
    {
        return [
            'brands' => $this->brands,
            'pages' => $this->pages,
            'monetaryUnits' => $this->monetaryUnits,
            'languges' => $this->languages,
            // Legacy alias - static_content table was dropped
            // Empty collection so views don't error
            'static_content' => collect([]),
            // Legacy alias - connect_configs table was dropped
            // Data now comes from platform_settings
            'connectConfig' => $this->connectConfig,
        ];
    }

    public function reset(): void
    {
        $this->brands = null;
        $this->pages = null;
        $this->monetaryUnits = null;
        $this->languages = null;
        $this->connectConfig = null;
        Cache::forget('connect_config_legacy');
    }

    // === Getters ===

    public function getBrands()
    {
        return $this->brands;
    }

    public function getPages()
    {
        return $this->pages;
    }

    public function getMonetaryUnits()
    {
        return $this->monetaryUnits;
    }

    public function getLanguages()
    {
        return $this->languages;
    }
}
