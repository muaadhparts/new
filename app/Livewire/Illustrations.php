<?php

namespace App\Livewire;

use App\Models\Brand;
use App\Models\Catalog;
use App\Models\NewCategory;
use App\Models\Section;
use App\Models\Illustration;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class Illustrations extends Component
{
    public $brand;
    public $catalog;
    public $parentCategory1;
    public $parentCategory2;
    public $parentCategory3;
    public $category;
    public $section;
    public $illustrations;
    public $callouts;

    public function mount($id, $data, $key1, $key2, $key3)
    {
        try {

            // ✅ حفظ VIN في الجلسة لو تم تمريره من الرابط
            if (request()->has('vin')) {
                session()->put('vin', request()->get('vin'));
            }
            // Load brand
            $this->brand = Brand::where('name', $id)->first();
            if (! $this->brand) {
                session()->flash('error', 'العلامة التجارية غير موجودة');
                return;
            }

            // Load catalog
            $this->catalog = Catalog::where('code', $data)
                ->where('brand_id', $this->brand->id)
                ->first();
            if (! $this->catalog) {
                session()->flash('error', 'الكتالوج غير موجود');
                return;
            }

            // Load category hierarchy
            $this->parentCategory1 = NewCategory::where('catalog_id', $this->catalog->id)
                ->where('brand_id', $this->brand->id)
                ->where('full_code', $key1)
                ->where('level', 1)->first();

            $this->parentCategory2 = NewCategory::where('catalog_id', $this->catalog->id)
                ->where('brand_id', $this->brand->id)
                ->where('full_code', $key2)
                ->where('level', 2)->first();

            $this->parentCategory3 = NewCategory::where('catalog_id', $this->catalog->id)
                ->where('brand_id', $this->brand->id)
                ->where('full_code', $key3)
                // ->where('full_code', $key4) // هذا الشرط الجديد لضمان الدقة
                ->where('level', 3)
                ->first();

            // Load actual category
            $this->category = NewCategory::where('catalog_id', $this->catalog->id)
                ->where('brand_id', $this->brand->id)
                ->where('full_code', $key3)
                ->where('level', 3)->first();

            if (! $this->category) {
                session()->flash('error', 'التصنيف غير موجود');
                return;
            }

            // Load section
            $this->section = Section::where('category_id', $this->category->id)->first();

            // Initialize collections
            $this->illustrations = collect();
            $this->callouts = collect();

            // Load illustration with callouts
            if ($this->section) {
                $illustration = Illustration::with('callouts')
                    ->where('section_id', $this->section->id)
                    ->where('code', $this->category->full_code) // لازم يكون مطابق للكود الكامل
                    ->first();

                if ($illustration) {
                    $this->illustrations = collect([$illustration]);
                    $this->callouts = $illustration->callouts;
                }
            } else {
                $this->illustrations = collect([$this->category]);
                $this->callouts = collect();
            }

            // Log::info("Illustrations loaded successfully", [
            //     'brand' => $this->brand?->name,
            //     'catalog' => $this->catalog?->code,
            //     'category' => $this->category?->full_code,
            //     'section' => $this->section?->code ?? null,
            //     'illustrations_count' => $this->illustrations->count(),
            //     'callouts_count' => $this->callouts->count(),
            // ]);

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
        ]);
    }
}
