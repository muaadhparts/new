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
    public function mount($id,$data,$key1,$key2,$code)
    {
//         dd($id,$data,$key1,$key2,$code );

        $this->vehicle = $data;
        $this->category = NCategory::where('data', $data)
            ->select('id','data','code','label','images','key1','key2')
             ->where('key1' ,$key1)
                ->where('key2' ,$key2)
                ->where('code' ,$code)
               ->firstOrfail();

        $this->illustration  =   \App\Models\Illustrations::where('data',$data)
            ->where('code',$code)
            ->first();

//        $this->partCallouts = collect( $this->illustration->illustrationWithCallouts);
        $this->partCallouts = collect($this->illustration->illustrationwithcallouts)['partCallouts'];

//        dd($this->illustration ,$this->illustration->illustrationwithcallouts , $this->partCallouts,$this->category);
 //        $products = Product::take(10)->get();

//        dd(Session::get('attributes'));

        if(Session::get('attributes')) {

            $year = !empty(Session::get('attributes')['year']) ? Session::get('attributes')['year'] : null ;
            $month = !empty(Session::get('attributes')['month']) ? Session::get('attributes')['month'] : null ;
            $attributes = Session::get('attributes');
            DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");

            $query = DB::table(Str::lower($data))
                ->select(
                    'code', 'partnumber', 'callout',  'label_'.app()->getLocale(),
//                    'partnumber',
//                    'applicability',
//                    DB::raw('MAX(id) AS id'),
//                    DB::raw("CONCAT(MAX(begin_month), '/', MAX(begin_year)) AS formattedbegindate"),
//                    DB::raw("CONCAT(MAX(end_month), '/', MAX(end_year)) AS formattedenddate"),
////                    'code',
//                    DB::raw('MAX(callout) AS callout')
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
//                        dd($key ,$value);
                        $query->orWhere('applicability', 'LIKE', "%{$value}%");
//
                }



//                $query = DB::table('your_table_name')->where(function ($query) use ($array) {
//
//                });
//                    $query->where('applicability', 'LIKE', '%3ROW%')
//                        ->orWhere('applicability', 'LIKE', '%VK56DE%')
//                        ->orWhere('applicability', 'LIKE', '%PR/C%')
//                        ->orWhere('applicability', 'LIKE', '%5AT%')
//                        ->orWhere('applicability', 'LIKE', '%GCC%');
                })
//                ->where(function ($query) {
//                    $query->where('begin_year', '<', 2015)
//                        ->orWhere(function ($subQuery) {
//                            $subQuery->where('begin_year', '=', 2015)
//                                ->where('begin_month', '<=', 8);
//                        });
//                })
//                ->where(function ($query) {
//                    $query->where('end_year', '>', 2015)
//                        ->orWhere(function ($subQuery) {
//                            $subQuery->where('end_year', '=', 2015)
//                                ->where('end_month', '>=', 8);
//                        })
//                        ->orWhereNull('end_year');
//                })
                ->groupBy('partnumber', 'applicability','callout', 'code')
                ->orderBy('callout')
                ->get();


            $this->products  =$query->get();

//            dd($year ,$month  ,$query->get(),Session::get('attributes'));

        }else {
            $this->products = DB::table(Str::lower($data))
                ->select('code', 'partnumber', 'callout' ,'label_'.app()->getLocale())
                ->distinct()
                ->where('code',$code )
                ->orderBy('callout')
                ->get();


        }



//        dd($this->illustration->illustrationWithCallouts ,$this->products);

    }

    public function render()
    {


        return view('livewire.illustrations');
    }
}
