<?php

namespace App\Domain\Platform\DTOs;

/**
 * HomePageDTO - Pre-computed data for home page display
 *
 * DATA FLOW POLICY: Views must only read properties, no logic, no queries
 */
final class HomePageDTO
{
    public function __construct(
        // Theme visibility settings
        public readonly bool $showHeroSearch,
        public readonly bool $showBrands,
        public readonly bool $showCategories,
        public readonly bool $showSlider,
        public readonly bool $showBlogs,
        public readonly bool $showNewsletter,

        // Section titles (pre-computed with fallbacks)
        public readonly string $brandsTitle,
        public readonly string $categoriesTitle,
        public readonly string $blogsTitle,

        // Brands section
        public readonly array $brands,

        // Catalogs section
        public readonly array $featuredCatalogs,
        public readonly int $totalCatalogsCount,
        public readonly bool $hasMoreCatalogs,
        public readonly string $viewAllCatalogsUrl,

        // Slider section
        public readonly array $sliderItems,

        // Blogs section
        public readonly array $blogs,

        // Meta
        public readonly string $siteName,
        public readonly ?string $metaDescription,
    ) {}
}
