<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Brand;
use App\Models\Catalog;
use App\Models\NewCategory;
use App\Models\Section;
use App\Services\CatalogSessionManager;
use App\Services\CategoryFilterService;
use App\Traits\LoadsCatalogData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CatlogTreeLevel2 extends Component
{
    use LoadsCatalogData;

    public $brand;
    public $catalog;
    public $category;
    public $categories;
    public $sections;
    public $level2AllowedCodes = [];

    protected CatalogSessionManager $sessionManager;
    protected CategoryFilterService $filterService;

    public function boot(CatalogSessionManager $sessionManager, CategoryFilterService $filterService)
    {
        $this->sessionManager = $sessionManager;
        $this->filterService = $filterService;
    }

    public function mount($id, $data, $key1)
    {
        try {
            if (request()->has('vin')) {
                $this->sessionManager->setVin(request()->get('vin'));
            }

            // تحميل البيانات الأساسية
            $this->loadBasicData($id, $data, $key1);

            // الحصول على الفلاتر من الخدمة
            $filterDate = $this->sessionManager->getFilterDate();
            $specItemIds = $this->sessionManager->getSpecItemIds($this->catalog);

            // تحميل فئات Level2 المفلترة مع Cache
            $cacheKey = sprintf(
                'catalog_level2_%d_%d_%d_%s_%s',
                $this->catalog->id,
                $this->brand->id,
                $this->category->id,
                $filterDate ?? 'no_date',
                md5(serialize($specItemIds))
            );

            $this->categories = Cache::remember($cacheKey, 3600, function() use ($filterDate, $specItemIds) {
                return $this->filterService->loadLevel2Categories(
                    $this->catalog,
                    $this->brand,
                    $this->category,
                    $filterDate,
                    $specItemIds
                );
            });

            // تحميل الأقسام (لا نستخدم cache هنا لأنه يعتمد على categories المتغيرة)
            $this->sections = $this->loadSectionsForCategories($this->categories);

            // حساب الأكواد المسموح بها لوضع Section
            $preloadedCodes = $this->sessionManager->getAllowedLevel3Codes();
            $this->level2AllowedCodes = $this->filterService->computeAllowedCodesForSections(
                $this->categories,
                $preloadedCodes
            );

        } catch (\Exception $e) {
            Log::error("Error in CatlogTreeLevel2 mount: " . $e->getMessage());
            session()->flash('error', 'حدث خطأ في تحميل البيانات');

            $this->categories = collect();
            $this->sections = collect();
        }
    }

    protected function loadBasicData($brandName, $catalogCode, $parentFullCode)
    {
        // ✅ eager loading للبراند مع المناطق
        $this->brand = Brand::with('regions')->where('name', $brandName)->firstOrFail();

        // ✅ eager loading للكتالوج مع البراند
        $this->catalog = Catalog::with(['brand', 'brandRegion'])
            ->where('code', $catalogCode)
            ->where('brand_id', $this->brand->id)
            ->firstOrFail();

        // ✅ eager loading للـ category مع العلاقات الأساسية
        $this->category = NewCategory::with(['catalog', 'brand', 'periods'])
            ->where([
                ['catalog_id', $this->catalog->id],
                ['brand_id', $this->brand->id],
                ['full_code', $parentFullCode],
                ['level', 1],
            ])->firstOrFail();
    }

    protected function loadSectionsForCategories($categories)
    {
        if ($categories->isEmpty()) return collect();

        return Section::whereIn('category_id', $categories->pluck('id')->toArray())
            ->where('catalog_id', $this->catalog->id)
            ->with(['illustrations' => function ($query) {
                $query->select('id', 'section_id', 'code', 'image_name', 'folder');
            }])
            ->select('id', 'code', 'full_code', 'category_id', 'catalog_id')
            ->get()
            ->groupBy('category_id');
    }

    public function render()
    {
        return view('livewire.catlog-tree-level2', [
            'brand' => $this->brand,
            'catalog' => $this->catalog,
            'category' => $this->category,
            'categories' => $this->categories ?? collect(),
            'sections' => $this->sections ?? collect(),
            'allowedCodes' => $this->level2AllowedCodes,
        ]);
    }
}
