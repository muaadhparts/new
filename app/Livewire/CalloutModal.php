<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Livewire\Component;

class CalloutModal extends Component
{
    public $data;
    public $table;  
    public $code;
    public $callout;
    public $products = [];
    public $isOpen = false;
    public $isLoading = false;


    protected $listeners = ['openCalloutModal' => 'loadCalloutData'];

    public function loadCalloutData($payload)
    {
        $this->isLoading = true;

       
        // تحميل البيانات
        $this->table = $payload['data'];
        $this->code = $payload['code'];
        $this->callout = $payload['callout'];

        // dd( $this->table ,$this->data);
        DB::enableQueryLog();
       $query = DB::table(Str::lower($this->table))
            ->select('callout','qty' ,'applicability', 'partnumber', 'label_en', 'label_ar' ,'formattedbegindate','formattedenddate');
            // ->distinct()    
            // ->where('code', $this->code)
            // ->where('callout', $this->callout)
            // ->groupBy('callout','qty' ,'applicability', 'partnumber', 'label_en', 'label_ar' ,'formattedbegindate','formattedenddate')
            // ->orderBy('callout')
            // ->orderBy('partnumber');
            // ->get();
            // ->toArray();

            if(Session::get('attributes')) {

                $year = !empty(Session::get('attributes')['year']) ? Session::get('attributes')['year'] : null ;
                $month = !empty(Session::get('attributes')['month']) ? Session::get('attributes')['month'] : null ;
                $attributes = Session::get('attributes');
                DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");
    
                // $query = DB::table(Str::lower($data))
                //     ->select(
                //         // 'code', 'partnumber', 'callout' // فقط الحقول الأساسية\
    
                //     )
                //     ->where('code',$code );
    
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
                    });
                    // ->groupBy('partnumber', 'applicability','callout', 'code')
                    // ->orderBy('callout');
                    // ->get();
                    
                  
    
                $this->products  =$query
                ->where('callout', $this->callout)
                ->groupBy('callout','qty' ,'applicability', 'partnumber', 'label_en', 'label_ar' ,'formattedbegindate','formattedenddate')
                ->orderBy('callout')
                ->orderBy('partnumber')
                
                ->get();
                // dd($query ,$this->products  ,Session::get('attributes'));
    
            }
            

            $queries = DB::getQueryLog();
             
            // DB::disableQueryLog();
            dd($this->products ,$queries);
        $this->isLoading = false;
        $this->isOpen = true;
    }


    public function closeModal()
    {
        $this->reset(['products', 'isOpen', 'data', 'code', 'callout']);
    }


    public function render()
    {
        return view('livewire.callout-viewer-modal');
    }
}
