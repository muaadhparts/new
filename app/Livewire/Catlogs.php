<?php

namespace App\Livewire;

use App\Models\Catalog;
use App\Models\Partner;
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
        // dd($id ,$this);
     
        $this->brand = Partner::where('name', $id)->firstOrFail();
        // dd($this->brand ,$this ,  $this->brand->regions);
        $this->region = $this->brand->regions->first()?->code ?? '';
      
    }

    public function render()
    {
        Session::forget('current_vehicle');
        Session::forget('attributes');

        $currentYear = date('Y');
        $years = range($currentYear + 1, 1975);

        $catlogs = Catalog::where('brand_id', $this->brand->id)
            ->where('applicableRegions', $this->region)
            ->when($this->searchName, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->searchName . '%')
                        ->orWhere('shortName', 'like', '%' . $this->searchName . '%')
                        ->orWhere('data', 'like', '%' . $this->searchName . '%');
                });
            })
            ->when($this->searchYear, function ($query) {
                $query->where(function ($q) {
                    $q->where('beginYear', '<=', $this->searchYear)
                        ->where(function ($q2) {
                            $q2->where('endYear', '>=', $this->searchYear)
                                ->orWhere('endYear', 0);
                        });
                });
            })
            ->orderBy('name', 'ASC')
            ->simplePaginate(10);

        return view('livewire.catlogs', [
            'catlogs' => $catlogs,
            'years' => $years,
        ]);
    }
    public function getRegionOptionsProperty()
    {
        if (!$this->brand || !$this->brand->regions) {
            return [];
        }
        return $this->brand->regions->pluck('label', 'code')->toArray();
    }

}
