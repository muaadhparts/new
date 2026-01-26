<?php

namespace App\Domain\Catalog\DTOs;

/**
 * SearchResultDTO - Pre-computed data for search results page
 *
 * DATA FLOW POLICY: Views must only read properties, no logic, no queries
 */
final class SearchResultDTO
{
    public function __construct(
        // Search info
        public readonly string $query,
        public readonly int $totalResults,
        public readonly string $resultsLabel,

        // Items (array of CatalogItemCardDTO)
        public readonly array $items,

        // Pagination
        public readonly int $currentPage,
        public readonly int $lastPage,
        public readonly int $perPage,
        public readonly bool $hasMorePages,
        public readonly ?string $nextPageUrl,
        public readonly ?string $prevPageUrl,

        // Filters applied
        public readonly array $activeFilters,
        public readonly ?int $categoryId,
        public readonly ?int $brandId,
        public readonly ?float $minPrice,
        public readonly ?float $maxPrice,

        // Available filters
        public readonly array $availableCategories,
        public readonly array $availableBrands,
        public readonly float $priceRangeMin,
        public readonly float $priceRangeMax,

        // Sort
        public readonly string $sortBy,
        public readonly array $sortOptions,
    ) {}

    /**
     * Build sort options for dropdown
     */
    public static function getSortOptions(): array
    {
        return [
            'relevance' => __('Relevance'),
            'price_asc' => __('Price: Low to High'),
            'price_desc' => __('Price: High to Low'),
            'newest' => __('Newest First'),
            'rating' => __('Highest Rated'),
        ];
    }
}
