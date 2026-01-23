<?php

namespace App\Services\GlobalData\Contexts;

use App\Models\Brand;
use App\Models\MonetaryUnit;
use App\Models\Language;
use App\Models\Page;
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
    }

    public function toArray(): array
    {
        return [
            'brands' => $this->brands,
            'pages' => $this->pages,
            'monetaryUnits' => $this->monetaryUnits,
            'languges' => $this->languages,
        ];
    }

    public function reset(): void
    {
        $this->brands = null;
        $this->pages = null;
        $this->monetaryUnits = null;
        $this->languages = null;
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
