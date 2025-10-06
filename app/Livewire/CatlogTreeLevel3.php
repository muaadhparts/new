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

            Log::info('🔍 CatlogTreeLevel3 mount', [
                'brand' => $id,
                'catalog' => $data,
                'key1' => $key1,
                'key2' => $key2
            ]);

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
            Log::error("❌ Error in CatlogTreeLevel3 mount: " . $e->getMessage(), [
                'brand' => $id ?? null,
                'catalog' => $data ?? null,
                'key1' => $key1 ?? null,
                'key2' => $key2 ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'حدث خطأ في تحميل البيانات');
            $this->categories = collect();
        }
    }

    protected function loadBasicData($brandName, $catalogCode, $parentsKey, $specKey)
    {
        Log::info('🔎 loadBasicData called', [
            'parentsKey' => $parentsKey,
            'specKey' => $specKey
        ]);

        $this->brand = Brand::where('name', $brandName)->firstOrFail();

        $this->catalog = Catalog::where('code', $catalogCode)
            ->where('brand_id', $this->brand->id)
            ->firstOrFail();

        // ✅ الطريقة الصحيحة: نبحث أولاً عن الـ level 2 category بـ spec_key
        // ثم نجلب الـ parent منها (level 1)
        $level2Category = NewCategory::where('catalog_id', $this->catalog->id)
            ->where('brand_id', $this->brand->id)
            ->where('level', 2)
            ->where(function($query) use ($specKey) {
                $query->where('spec_key', $specKey)
                      ->orWhere('full_code', $specKey);
            })
            ->first();

        if (!$level2Category) {
            Log::error('❌ Level 2 category not found', ['specKey' => $specKey]);
            throw new \Exception("Category not found for spec_key: {$specKey}");
        }

        $this->parentCategory2 = $level2Category;

        // جلب الـ parent (level 1) من parent_id
        $this->parentCategory1 = NewCategory::where('id', $level2Category->parent_id)
            ->where('level', 1)
            ->firstOrFail();

        // ✅ التحقق من أن parents_key يطابق ما تم تمريره
        if ($parentsKey && $this->parentCategory1->full_code !== $parentsKey && $this->parentCategory1->spec_key !== $parentsKey) {
            Log::warning('⚠️ parents_key mismatch', [
                'expected' => $parentsKey,
                'found_full_code' => $this->parentCategory1->full_code,
                'found_spec_key' => $this->parentCategory1->spec_key
            ]);
        }

        Log::info('✅ Categories loaded', [
            'parentCategory1' => $this->parentCategory1->full_code,
            'parentCategory2' => $this->parentCategory2->spec_key
        ]);
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
