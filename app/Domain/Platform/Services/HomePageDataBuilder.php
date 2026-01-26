<?php

namespace App\Domain\Platform\Services;

use App\Domain\Platform\DTOs\HomePageDTO;
use App\Domain\Platform\Models\HomePageTheme;
use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Catalog;
use App\Domain\Catalog\DTOs\BrandCardDTO;
use App\Domain\Catalog\DTOs\CatalogCardDTO;
use Illuminate\Support\Facades\Cache;

/**
 * HomePageDataBuilder - Builds pre-computed data for home page
 *
 * DATA FLOW POLICY: All queries and logic here, DTO is output
 */
class HomePageDataBuilder
{
    public function __construct(
        private PlatformSettingsService $settings,
    ) {}

    /**
     * Build complete home page DTO
     */
    public function build(): HomePageDTO
    {
        $theme = HomePageTheme::getActive();

        return new HomePageDTO(
            // Theme visibility settings
            showHeroSearch: (bool) ($theme->show_hero_search ?? true),
            showBrands: (bool) $theme->show_brands,
            showCategories: (bool) $theme->show_categories,
            showSlider: (bool) $theme->show_slider,
            showBlogs: (bool) ($theme->show_blogs ?? false),
            showNewsletter: (bool) ($theme->show_newsletter ?? false),

            // Section titles (pre-computed with fallbacks)
            brandsTitle: $theme->name_brands ?? __('Explore genuine OEM parts catalogues'),
            categoriesTitle: $theme->name_categories ?? __('Shop by Catalog'),
            blogsTitle: $theme->name_blogs ?? __('From Our Blog'),

            // Brands section
            brands: $this->buildBrands($theme),

            // Catalogs section
            featuredCatalogs: $this->buildFeaturedCatalogs($theme),
            totalCatalogsCount: $this->getTotalCatalogsCount(),
            hasMoreCatalogs: $this->hasMoreCatalogs($theme),
            viewAllCatalogsUrl: route('front.catalogs'),

            // Slider section
            sliderItems: $this->buildSliderItems($theme),

            // Blogs section
            blogs: $this->buildBlogs($theme),

            // Meta
            siteName: $this->settings->get('site_name', 'MUAADH'),
            metaDescription: $this->settings->get('meta_description'),
        );
    }

    /**
     * Build brands data if enabled
     */
    private function buildBrands(HomePageTheme $theme): array
    {
        if (!$theme->show_brands) {
            return [];
        }

        $brands = Cache::remember('homepage_brands_dto', 3600, function () {
            return Brand::withCount(['catalogs', 'catalogItems'])
                ->orderBy('name')
                ->get();
        });

        return BrandCardDTO::fromCollection($brands);
    }

    /**
     * Build featured catalogs if enabled
     */
    private function buildFeaturedCatalogs(HomePageTheme $theme): array
    {
        if (!$theme->show_categories) {
            return [];
        }

        $limit = $theme->count_categories ?? 12;

        $catalogs = Cache::remember("homepage_catalogs_dto_{$limit}", 3600, function () use ($limit) {
            return Catalog::where('status', 1)
                ->with('brand:id,name,name_ar,slug,photo')
                ->withCount(['sections'])
                ->orderBy('sort')
                ->limit($limit)
                ->get();
        });

        return CatalogCardDTO::fromCollection($catalogs);
    }

    /**
     * Get total catalogs count for "View All" link
     */
    private function getTotalCatalogsCount(): int
    {
        return Cache::remember('total_catalogs_count', 3600, function () {
            return Catalog::where('status', 1)->count();
        });
    }

    /**
     * Check if there are more catalogs than displayed
     */
    private function hasMoreCatalogs(HomePageTheme $theme): bool
    {
        $displayedCount = $theme->count_categories ?? 12;
        return $this->getTotalCatalogsCount() > $displayedCount;
    }

    /**
     * Build slider items if enabled
     */
    private function buildSliderItems(HomePageTheme $theme): array
    {
        if (!$theme->show_slider) {
            return [];
        }

        // Slider items are stored in HomePageTheme
        $sliderData = $theme->slider_data ?? [];

        return collect($sliderData)->map(function ($item) {
            return [
                'image' => $item['image'] ?? '',
                'title' => $item['title'] ?? '',
                'subtitle' => $item['subtitle'] ?? '',
                'link' => $item['link'] ?? '#',
                'buttonText' => $item['button_text'] ?? __('Learn More'),
            ];
        })->toArray();
    }

    /**
     * Build blogs data if enabled
     */
    private function buildBlogs(HomePageTheme $theme): array
    {
        if (!($theme->show_blogs ?? false)) {
            return [];
        }

        // TODO: Implement when blog system is added
        return [];
    }
}
