<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Brand;
use App\Models\Catalog;
use App\Models\NewCategory;
use App\Services\CatalogSessionManager;
use App\Services\CategoryFilterService;
use App\Traits\LoadsCatalogData;
use Illuminate\Support\Facades\Log;

class CatlogTreeLevel3 extends Component
{
    use LoadsCatalogData;

    public $brand;
    public $catalog;
    public $parentCategory1;
    public $parentCategory2;
    public $categories;

    protected CatalogSessionManager $sessionManager;
    protected CategoryFilterService $filterService;

    public function boot(CatalogSessionManager $sessionManager, CategoryFilterService $filterService)
    {
        $this->sessionManager = $sessionManager;
        $this->filterService = $filterService;
    }

    public function mount($id, $data, $key1, $key2)
    {
        try {
            if (request()->has('vin')) {
                $this->sessionManager->setVin(request()->get('vin'));
            }

            // تحميل البيانات الأساسية
            $this->loadBasicData($id, $data, $key1, $key2);

            // الحصول على الفلاتر من الخدمة
            $filterDate = $this->sessionManager->getFilterDate();
            $specItemIds = $this->sessionManager->getSpecItemIds($this->catalog);

            // تحميل فئات Level3 المفلترة
            $this->categories = $this->filterService->loadLevel3Categories(
                $this->catalog,
                $this->brand,
                $this->parentCategory2,
                $filterDate,
                $specItemIds
            );

        } catch (\Exception $e) {
            Log::error("❌ Error in CatlogTreeLevel3 mount: " . $e->getMessage());
            session()->flash('error', 'حدث خطأ في تحميل البيانات');
            $this->categories = collect();
        }
    }

    protected function loadBasicData($brandName, $catalogCode, $fullCode1, $fullCode2)
    {
        $this->brand = Brand::where('name', $brandName)->firstOrFail();

        $this->catalog = Catalog::where('code', $catalogCode)
            ->where('brand_id', $this->brand->id)
            ->firstOrFail();

        $this->parentCategory1 = NewCategory::where([
            ['catalog_id', $this->catalog->id],
            ['brand_id', $this->brand->id],
            ['full_code', $fullCode1],
            ['level', 1],
        ])->firstOrFail();

        $this->parentCategory2 = NewCategory::where([
            ['catalog_id', $this->catalog->id],
            ['brand_id', $this->brand->id],
            ['full_code', $fullCode2],
            ['level', 2],
            ['parent_id', $this->parentCategory1->id],
        ])->firstOrFail();
    }

    public function render()
    {
        return view('livewire.catlog-tree-level3', [
            'brand' => $this->brand,
            'catalog' => $this->catalog,
            'parentCategory1' => $this->parentCategory1,
            'parentCategory2' => $this->parentCategory2,
            'categories' => $this->categories ?? collect(),
        ]);
    }
}
