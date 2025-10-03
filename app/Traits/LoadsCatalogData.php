<?php

namespace App\Traits;

use App\Models\Brand;
use App\Models\Catalog;

/**
 * Trait لتحميل بيانات الكتالوج والعلامة التجارية بشكل موحد
 */
trait LoadsCatalogData
{
    /**
     * تحميل العلامة التجارية والكتالوج بناءً على الأسماء/الأكواد
     */
    protected function loadBrandAndCatalog(string $brandName, string $catalogCode): void
    {
        $this->brand = Brand::where('name', $brandName)->firstOrFail();

        $this->catalog = Catalog::where('code', $catalogCode)
            ->where('brand_id', $this->brand->id)
            ->firstOrFail();
    }

    /**
     * تحميل العلامة التجارية فقط
     */
    protected function loadBrand(string $brandName): void
    {
        $this->brand = Brand::where('name', $brandName)->firstOrFail();
    }

    /**
     * تحميل الكتالوج فقط (يتطلب وجود brand_id)
     */
    protected function loadCatalog(string $catalogCode, int $brandId): void
    {
        $this->catalog = Catalog::where('code', $catalogCode)
            ->where('brand_id', $brandId)
            ->firstOrFail();
    }

    /**
     * تحميل الكتالوج من كائن أو كود
     */
    protected function resolveCatalog($catalog): Catalog
    {
        if ($catalog instanceof Catalog) {
            return $catalog;
        }

        if (is_string($catalog)) {
            return Catalog::with('brand')->where('code', $catalog)->firstOrFail();
        }

        throw new \InvalidArgumentException('Invalid catalog parameter');
    }
}
