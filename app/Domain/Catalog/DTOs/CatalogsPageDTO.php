<?php

namespace App\Domain\Catalog\DTOs;

use App\Domain\Catalog\Models\Catalog;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * CatalogsPageDTO - Pre-computed data for all catalogs page
 *
 * DATA FLOW POLICY: Views must only read properties, no logic, no queries
 */
final class CatalogsPageDTO
{
    public function __construct(
        // Catalog items (array of CatalogCardDTO)
        public readonly array $catalogs,

        // Pagination
        public readonly int $currentPage,
        public readonly int $lastPage,
        public readonly int $perPage,
        public readonly int $total,
        public readonly bool $hasMorePages,
        public readonly ?string $nextPageUrl,
        public readonly ?string $prevPageUrl,
        public readonly array $paginationLinks,
    ) {}

    /**
     * Build DTO from paginated Catalog collection
     */
    public static function fromPaginator(LengthAwarePaginator $paginator): self
    {
        $catalogs = CatalogCardDTO::fromCollection($paginator->getCollection());

        return new self(
            catalogs: $catalogs,
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            hasMorePages: $paginator->hasMorePages(),
            nextPageUrl: $paginator->nextPageUrl(),
            prevPageUrl: $paginator->previousPageUrl(),
            paginationLinks: self::buildPaginationLinks($paginator),
        );
    }

    /**
     * Build pagination links for display
     */
    private static function buildPaginationLinks(LengthAwarePaginator $paginator): array
    {
        $links = [];
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();

        // Previous
        if ($currentPage > 1) {
            $links[] = [
                'url' => $paginator->url($currentPage - 1),
                'label' => '&laquo;',
                'active' => false,
                'disabled' => false,
            ];
        }

        // Page numbers
        for ($page = 1; $page <= $lastPage; $page++) {
            // Show first, last, current, and pages around current
            if ($page === 1 || $page === $lastPage || abs($page - $currentPage) <= 2) {
                $links[] = [
                    'url' => $paginator->url($page),
                    'label' => (string) $page,
                    'active' => $page === $currentPage,
                    'disabled' => false,
                ];
            } elseif ($page === 2 && $currentPage > 4) {
                $links[] = [
                    'url' => null,
                    'label' => '...',
                    'active' => false,
                    'disabled' => true,
                ];
            } elseif ($page === $lastPage - 1 && $currentPage < $lastPage - 3) {
                $links[] = [
                    'url' => null,
                    'label' => '...',
                    'active' => false,
                    'disabled' => true,
                ];
            }
        }

        // Next
        if ($currentPage < $lastPage) {
            $links[] = [
                'url' => $paginator->url($currentPage + 1),
                'label' => '&raquo;',
                'active' => false,
                'disabled' => false,
            ];
        }

        return $links;
    }
}
