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


    // public $searchName;
    public $searchName = '';
    public $searchYear;
    public $brand;
    public $region   ;

    public function mount($id)
    {
//         dd($id);
        $this->region = 'GL';
        $this->brand = Partner::where('name', $id)->firstorFail();
//        $this->brandId = Partner::where('name', $id)->first();
    }

    public function render()
    {
        Session::forget('current_vehicle');
        Session::forget('attributes');
        $currentYear = date('Y');
        $years = range($currentYear+1 ,1975);

//        dd($this->region);

        $catlogs = Catalog::where('brand_id', $this->brand->id)
                ->where('applicableRegions',$this->region)
                ->where(function ($query) {

                })->when($this->searchName, function ($query) {
                        $query

                            ->where('name', 'like', '%' . $this->searchName . '%')

                            ->orWhere('shortName', 'like', '%' . $this->searchName . '%')
                            ->orWhere('data', 'like', '%' . $this->searchName . '%');

        //                $query->whereRaw('? BETWEEN beginYear AND endYear', [$this->searchYear]);
                    })

            ->when($this->searchYear, function ($query) {
                $query->where('beginYear', '>=', $this->searchYear)
                    ->where('endYear', '>=', $this->searchYear)
                    ->orWhere('endYear',0);
////                    ->where(function ($q) {
//                        $q->where('endYear', '>=', $this->searchYear)
//
////                    });
            })->orderBy('name', 'ASC')

        ->simplePaginate(10);

//        dd($catlogs->count() ,$this->region ,$this->brand);
        return view('livewire.catlogs', [
            'catlogs' => $catlogs,
            'years' => $years,
        ]);
    }
}
