<?php

namespace App\Services\GlobalData\Contexts;

use App\Models\Brand;
use App\Models\MonetaryUnit;
use App\Models\Language;
use App\Models\StaticContent;
use Illuminate\Support\Facades\Cache;

/**
 * NavigationContext
 *
 * بيانات التنقل (Header/Footer):
 * - Brands مع Catalogs
 * - المحتوى الثابت
 * - قوائم العملات واللغات
 */
class NavigationContext implements ContextInterface
{
    private $brands = null;
    private $staticContent = null;
    private $monetaryUnits = null;
    private $languages = null;

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

        $this->staticContent = Cache::remember('header_static_content', 3600, fn() =>
            StaticContent::all()
        );

        $this->monetaryUnits = Cache::remember('all_monetary_units', 3600, fn() =>
            MonetaryUnit::all()
        );

        $this->languages = Cache::remember('all_languages', 3600, fn() =>
            Language::all()
        );
    }

    public function toArray(): array
    {
        return [
            'brands' => $this->brands,
            'static_content' => $this->staticContent,
            'monetaryUnits' => $this->monetaryUnits,
            'languges' => $this->languages,
        ];
    }

    public function reset(): void
    {
        $this->brands = null;
        $this->staticContent = null;
        $this->monetaryUnits = null;
        $this->languages = null;
    }

    // === Getters ===

    public function getBrands()
    {
        return $this->brands;
    }

    public function getStaticContent()
    {
        return $this->staticContent;
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
