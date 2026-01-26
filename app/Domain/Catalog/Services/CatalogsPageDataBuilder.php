<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\DTOs\CatalogsPageDTO;
use App\Domain\Catalog\Models\Catalog;
use App\Domain\Platform\Models\HomePageTheme;

/**
 * CatalogsPageDataBuilder - Builds pre-computed data for catalogs page
 *
 * DATA FLOW POLICY: All queries and logic here, DTO is output
 */
class CatalogsPageDataBuilder
{
    /**
     * Build catalogs page DTO
     */
    public function build(int $perPage = 12): CatalogsPageDTO
    {
        $theme = HomePageTheme::getActive();
        $perPage = $theme->count_categories ?? $perPage;

        $paginator = Catalog::where('status', 1)
            ->with('brand:id,name,name_ar,slug,photo')
            ->withCount(['sections'])
            ->orderBy('sort')
            ->paginate($perPage);

        return CatalogsPageDTO::fromPaginator($paginator);
    }
}
