<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Catalog;
use App\Models\Brand;
use App\Services\CatalogSessionManager;
use App\Services\CategoryFilterService;
use App\Traits\LoadsCatalogData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CatlogTreeLevel1 extends Component
{
    use LoadsCatalogData;

    public $catalog;
    public $brand;
    public $categories;

    protected CatalogSessionManager $sessionManager;
    protected CategoryFilterService $filterService;

    public function boot(CatalogSessionManager $sessionManager, CategoryFilterService $filterService)
    {
        $this->sessionManager = $sessionManager;
        $this->filterService = $filterService;
    }

    public function mount($id, $data)
    {
        try {
            // ✅ تحميل Brand و Catalog مع eager loading للعلاقات الأساسية
            $this->brand = Brand::with('regions')->where('name', $id)->firstOrFail();
            $this->catalog = Catalog::with(['brand', 'brandRegion'])->where('code', $data)->where('brand_id', $this->brand->id)->firstOrFail();

            // الحصول على الفلاتر من الخدمة
            $specItemIds = $this->sessionManager->getSpecItemIds($this->catalog);
            $filterDate = $this->sessionManager->getFilterDate();

            // الحصول على أكواد Level3 المفلترة
            $allowedLevel3Codes = $this->filterService->getFilteredLevel3FullCodes(
                $this->catalog,
                $this->brand,
                $filterDate,
                $specItemIds
            );

            // حفظ الأكواد في الجلسة
            $this->sessionManager->setAllowedLevel3Codes($allowedLevel3Codes);

            // تحميل فئات Level1 مع Cache
            $labelField = app()->getLocale() === 'ar' ? 'label_ar' : 'label_en';

            // إنشاء cache key فريد بناءً على الفلاتر
            $cacheKey = sprintf(
                'catalog_level1_%d_%d_%s_%s_%s',
                $this->catalog->id,
                $this->brand->id,
                $labelField,
                $filterDate ?? 'no_date',
                md5(serialize($allowedLevel3Codes))
            );

            $this->categories = Cache::remember($cacheKey, 3600, function() use ($labelField, $filterDate, $allowedLevel3Codes) {
                return $this->filterService->loadLevel1Categories(
                    $this->catalog,
                    $this->brand,
                    $labelField,
                    $filterDate,
                    $allowedLevel3Codes
                );
            });

        } catch (\Exception $e) {
            Log::error("❌ Error in CatlogTreeLevel1 mount: " . $e->getMessage());
            session()->flash('error', 'حدث خطأ في تحميل البيانات');
            $this->categories = collect();
        }
    }

    public function render()
    {
        return view('livewire.catlog-tree-level1', [
            'brand' => $this->brand,
            'catalog' => $this->catalog,
            'categories' => $this->categories ?? collect(),
        ]);
    }
}
