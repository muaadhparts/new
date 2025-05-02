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

    public $vehicle;
    public $region;
    public $code;
    public $brand;
    public $categories;
    protected $listeners = ['form-saved' => 'handleRefresh'];
 
    public function mount($id,$data,$key1,$key2,$code)
    {
//         dd($id,$data,$key1,$key2,$code );

        $this->vehicle = $data;
        $this->code = $code;
        $this->brand = Partner::where('name', $id)->firstorFail();
        $this->category = NCategory::where('data', $data)
//            ->select('id','data','code','label','images','key1','key2')
             ->where('key1' ,$key1)
                ->where('key2' ,$key2)
                ->where('code' ,$code)
               ->firstOrfail();

//        dd($this->category);
        $this->illustration  =   \App\Models\Illustrations::where('data',$data)
            ->where('code',$code)
            ->first();

//        $this->partCallouts = collect( $this->illustration->illustrationWithCallouts);
        $this->partCallouts = collect($this->illustration->illustrationwithcallouts)['partCallouts'];

//        dd($this->illustration ,$this->illustration->illustrationwithcallouts , $this->partCallouts,$this->category);
 //        $products = Product::take(10)->get();

    //    dd(Session::get('attributes'));

    

//            dd($year ,$month  ,$query->get(),Session::get('attributes'));

        

            
            $this->products = DB::table(Str::lower($data))
                // ->select('code', 'partnumber', 'callout' ,'label_'.app()->getLocale())
                ->select(
                    'code', 
                    'partnumber', 
                    'callout',  
                    'label_en',
                    'label_ar',
                    // 'label_'.app()->getLocale(),

                    'qty', 
                    'applicability',
                    'formattedbegindate',
                    'formattedenddate'
                   
                )
              
                ->distinct()
                ->where('code',$code )
                ->orderBy('callout')
                ->get();

                // dd($this->products);

       

        
        



//        dd($this->illustration->illustrationWithCallouts ,$this->products);

    }


    public function handleRefresh()
    {

        if(Session::get('attributes')) {

            // dd($this ,$this->products);
            // dd(Session::get('attributes'));
            $year = !empty(Session::get('attributes')['year']) ? Session::get('attributes')['year'] : null ;
            $month = !empty(Session::get('attributes')['month']) ? Session::get('attributes')['month'] : null ;
            $attributes =array_filter( Session::get('attributes')) ;
            DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");

            // dd($year,$month,$attributes);
            $query = DB::table(Str::lower($this->vehicle))
                ->select(
                    'code', 
                    'partnumber', 
                    'callout',  
                    'label_en',
                    'label_ar',
                    // 'label_'.app()->getLocale(),

                    'qty', 
                    'applicability',
                    'formattedbegindate',
                    'formattedenddate'

 //                 
                );
               

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
// dd($key,$value);
                        $query->where('applicability', 'LIKE', "%{$value}%");
                        // $query->orWhere('applicability', 'LIKE', "%{$value}%");
//
                } 
 
                });
//              
                // ->groupBy('partnumber', 'applicability','callout', 'code')
                
                 

                // dd($query->get());
            $this->products  =$query
            ->distinct()
                ->where('code',$this->code )
                ->orderBy('callout')
            ->get();

            // dd($this->products);

            // $this->render();
        }
        // dd('refreshData' ,Session::get('attributes'));
        // Your refresh logic here
        // $this->loadData();
        // ... existing code ...
    }
 

    // public function refreshData()
    // { 
    //     dd('refreshData');
    //     // Handle the event here
    //     $this->loadData();
    //     // ... existing code ...
    // }

    public function render()
    {

        // dd($this->products);

        return view('livewire.illustrations');
    }
}
