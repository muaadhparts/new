<?php

namespace App\Livewire;

use App\Models\Catalog;
use App\Models\Brand;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Livewire\WithPagination;

class Catlogs extends Component
{
    use WithPagination;

    public $searchName = '';
    public $searchYear;
    public $brand;
    public $region;

    public function mount($id)
    {
        $this->resetFilters();

        try {
            // ✅ Get brand by name with regions eager loaded
            $this->brand = Brand::with('regions')
                ->whereRaw('LOWER(name) = ?', [strtolower($id)])
                ->first();

            if (!$this->brand) {
                session()->flash('error', __('Brand not found.'));
                return;
            }

            // ✅ Select first region by default - استخدام collection المحملة بدل استعلام جديد
            $this->region = $this->brand->regions->first()?->code;

        } catch (\Exception $e) {
            session()->flash('error', __('An error occurred while loading data.'));
        }
    }

    public function updatedRegion()
    {
        $this->resetPage();
    }

    public function updatedSearchName()
    {
        $this->resetPage();
    }

    public function updatedSearchYear()
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->resetFilters();

        $years = $this->getYearRange();

        try {
            // ✅ Get region ID from already loaded regions collection
            $brandRegionId = $this->brand?->regions
                ->where('code', $this->region)
                ->first()
                ?->id;

            // ✅ Query catalogs with brand eager loaded (if not already)
            $catlogs = Catalog::with('brand:id,name')
                ->where('brand_id', $this->brand->id)
                ->when($brandRegionId, fn($q) => $q->where('brand_region_id', $brandRegionId))
                ->when($this->searchName, fn($q) =>
                    $q->where(function ($q) {
                        $q->where('label_ar', 'like', "%{$this->searchName}%")
                        ->orWhere('label_en', 'like', "%{$this->searchName}%")
                        ->orWhere('code', 'like', "%{$this->searchName}%");
                    })
                )
                ->when($this->searchYear, fn($q) =>
                    $q->where('beginYear', '<=', $this->searchYear)
                    ->where(fn($q2) =>
                        $q2->where('endYear', '>=', $this->searchYear)
                            ->orWhere('endYear', 0)
                    )
                )
                ->orderBy('new_id', 'ASC')
                ->paginate(12);

        } catch (\Exception $e) {
            $catlogs = collect();
        }

        return view('livewire.catlogs', [
            'catlogs' => $catlogs,
            'years' => $years,
        ]);
    }

    public function getRegionOptionsProperty()
    {
        if (!$this->brand) {
            return [];
        }

        try {
            // ✅ استخدام regions المحملة مسبقاً بدل استعلام جديد
            $regions = $this->brand->regions;

            return $regions->mapWithKeys(function ($region) {
                return [$region->code => getLocalizedLabel($region)];
            })->toArray();

        } catch (\Exception $e) {
            return [];
        }
    }


    /**
     * Reset filters and Session
     */
    protected function resetFilters()
    {
        Session::forget([
            'current_catalog',
            'attributes',
            'selected_filters',
            'selected_filters_labeled',
            'filtered_level3_codes',
            'vin',
        ]);
    }

    /**
     * Get year range for filter
     */
    protected function getYearRange(): array
    {
        return range(date('Y') + 1, 1975);
    }
}
