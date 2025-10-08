<?php

namespace App\Livewire;

use App\Models\Brand;
use App\Models\Catalog;
use App\Models\NewCategory;
use App\Models\Section;
use App\Models\Illustration;
use App\Services\CatalogSessionManager;
use App\Traits\LoadsCatalogData;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class Illustrations extends Component
{
    use LoadsCatalogData;

    public $brand;
    public $catalog;
    public $parentCategory1;
    public $parentCategory2;
    public $parentCategory3;
    public $category;
    public $section;
    public $illustrations;
    public $callouts;

    protected CatalogSessionManager $sessionManager;

    public function boot(CatalogSessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    public function mount($id, $data, $key1, $key2, $key3)
    {
        try {
            // ✅ حفظ VIN في الجلسة لو تم تمريره من الرابط
            if (request()->has('vin')) {
                $this->sessionManager->setVin(request()->get('vin'));
            }

            // ✅ استخدام Trait لتحميل Brand و Catalog مع eager loading
            $this->loadBrandAndCatalog($id, $data);

            if (!$this->brand || !$this->catalog) {
                session()->flash('error', 'البيانات غير موجودة');
                return;
            }

            // ✅ Load category hierarchy مع eager loading للعلاقات
            $this->parentCategory1 = NewCategory::with(['catalog', 'brand', 'periods'])
                ->where('catalog_id', $this->catalog->id)
                ->where('brand_id', $this->brand->id)
                ->where('full_code', $key1)
                ->where('level', 1)->first();

            $this->parentCategory2 = NewCategory::with(['catalog', 'brand', 'periods'])
                ->where('catalog_id', $this->catalog->id)
                ->where('brand_id', $this->brand->id)
                ->where('full_code', $key2)
                ->where('level', 2)->first();

            $this->parentCategory3 = NewCategory::with(['catalog', 'brand', 'periods'])
                ->where('catalog_id', $this->catalog->id)
                ->where('brand_id', $this->brand->id)
                ->where('full_code', $key3)
                // ->where('full_code', $key4) // هذا الشرط الجديد لضمان الدقة
                ->where('level', 3)
                ->first();

            // ✅ إذا جاء من البحث مع category_id في الـ URL، استخدمه مباشرة مع eager loading
            if (request()->has('category_id')) {
                $this->category = NewCategory::with(['catalog', 'brand', 'periods', 'sections'])
                    ->find(request()->get('category_id'));
            } else {
                // Load actual category من الـ route parameters مع eager loading
                $this->category = NewCategory::with(['catalog', 'brand', 'periods', 'sections'])
                    ->where('catalog_id', $this->catalog->id)
                    ->where('brand_id', $this->brand->id)
                    ->where('full_code', $key3)
                    ->where('level', 3)->first();
            }

            if (! $this->category) {
                session()->flash('error', 'التصنيف غير موجود');
                return;
            }

            // ✅ إذا جاء من البحث مع section_id في الـ URL، استخدمه مباشرة مع eager loading
            if (request()->has('section_id')) {
                $this->section = Section::with(['category', 'catalog', 'illustrations.callouts'])
                    ->find(request()->get('section_id'));
            } else {
                // ✅ Load section بناءً على category مع eager loading
                $this->section = Section::with(['category', 'catalog', 'illustrations.callouts'])
                    ->where('category_id', $this->category->id)
                    ->first();
            }

            // Initialize collections
            $this->illustrations = collect();
            $this->callouts = collect();

            // ✅ Load illustration with callouts - استخدام العلاقة المحملة إن أمكن
            if ($this->section) {
                // استخدام العلاقة المحملة أولاً
                $illustration = $this->section->illustrations
                    ->where('code', $this->category->full_code)
                    ->first();

                if ($illustration) {
                    $this->illustrations = collect([$illustration]);
                    $this->callouts = $illustration->callouts ?? collect();
                }
            } else {
                $this->illustrations = collect([$this->category]);
                $this->callouts = collect();
            }

        } catch (\Exception $e) {
            Log::error("Error in Illustrations mount: " . $e->getMessage());
            session()->flash('error', 'حدث خطأ في تحميل البيانات');
            $this->illustrations = collect();
            $this->callouts = collect();
        }
    }

    public function render()
    {
        return view('livewire.illustrations', [
            'illustrations' => $this->illustrations,
            'callouts' => $this->callouts,
            'section' => $this->section,      // ✅ تمرير section
            'category' => $this->category,    // ✅ تمرير category
            'brand' => $this->brand,          // ✅ تمرير brand
            'catalog' => $this->catalog,      // ✅ تمرير catalog
        ]);
    }
}
