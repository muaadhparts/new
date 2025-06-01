<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\NCategory;
use App\Models\Partner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Livewire\Component;

class Illustrations extends Component
{
    public $category;
    public $partCallouts;
    public $illustration;
    public $products;

    public $brand;
    public $categories;
    public $vehicle;

    public function mount($id,$data,$key1,$key2,$code)
    {

        $this->vehicle = $data;
        $this->brand = Partner::where('name', $id)->firstorFail();
        $this->category = NCategory::where('data', $data)
             ->where('key1' ,$key1)
                ->where('key2' ,$key2)
                ->where('code' ,$code)
               ->firstOrfail();


        $this->illustration  =   \App\Models\Illustrations::where('data',$data)
            ->where('code',$code)
            ->first();

        $this->partCallouts = collect($this->illustration->illustrationwithcallouts)['partCallouts'];



        if(Session::get('attributes')) {

            $year = !empty(Session::get('attributes')['year']) ? Session::get('attributes')['year'] : null ;
            $month = !empty(Session::get('attributes')['month']) ? Session::get('attributes')['month'] : null ;
            $attributes = Session::get('attributes');
            DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");

            $query = DB::table(Str::lower($data))
                ->select(
                    'code', 'partnumber', 'callout' // فقط الحقول الأساسية\

                )
                ->where('code',$code );

            if($year){
                $query->where(function ($query) use ($year ,$month) {
                    $query->where('begin_year', '<', $year)
                        ->orWhere(function ($subQuery)  use ($year ,$month) {
                            $subQuery->where('begin_year', '=', $year)
                                ->where('begin_month', '<=', $month ?? 12);
                        });
                })->where(function ($query) use ($year ,$month){
                    $query->where('end_year', '>', $year)
                        ->orWhere(function ($subQuery) use ($year ,$month) {
                            $subQuery->where('end_year', '=', $year)
                                ->where('end_month', '>=',  $month ?? 12);
                        })
                        ->orWhereNull('end_year');
                })
                ;

            }


            $query->where(function ($query) use ($year ,$month ,$attributes){
                unset($attributes['month']);
                unset($attributes['year']);

                foreach ($attributes as $key => $value) {
                        $query->orWhere('applicability', 'LIKE', "%{$value}%");
                }
                })
                ->groupBy('partnumber', 'applicability','callout', 'code')
                ->orderBy('callout')
                ->get();


            $this->products  =$query->get();
        }else {
            $this->products = DB::table(Str::lower($data))
                ->select('code', 'partnumber', 'callout', 'label_ar', 'label_en')
                ->distinct()
                ->where('code', $code)
                ->orderBy('callout')
                ->get();


        }
    }

    public function render()
    {


        return view('livewire.illustrations');
    }
  
    
}
