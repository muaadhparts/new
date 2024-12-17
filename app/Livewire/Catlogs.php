<?php

namespace App\Livewire;

use App\Models\Catalog;
use App\Models\Partner;
use Livewire\Component;

class Catlogs extends Component
{
    // use WithPagination;


    // public $searchName;
    public $searchName = '';
    public $searchYear;
    public $brand;

    public function mount($id)
    {
//         dd($id);
        $this->brand = Partner::where('name', $id)->firstorFail();
//        $this->brandId = Partner::where('name', $id)->first();
    }

    public function render()
    {

        $currentYear = date('Y');
        $years = range($currentYear+1 ,1975);


        $catlogs = Catalog::where('brand_id', $this->brand->id)->where(function ($query) {
            $query->where('name', 'like', '%' . $this->searchName . '%')
                ->orWhere('shortName', 'like', '%' . $this->searchName . '%')
                ->orWhere('data', 'like', '%' . $this->searchName . '%');
        })->when($this->searchYear, function ($query) {
                    $query->whereRaw('? BETWEEN beginYear AND endYear', [$this->searchYear]);
         })
        ->paginate(10);

//        dd($catlogs ,$this->brand);
        return view('livewire.catlogs', [
            'catlogs' => $catlogs,
            'years' => $years,
        ]);
    }
}
