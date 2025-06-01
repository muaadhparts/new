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
            ->select('callout','qty' ,'applicability', 'partnumber', 'label_en', 'label_ar' ,'formattedbegindate','formattedenddate')
            ->where('code', $this->code)
            ->where('callout', $this->callout);

        if(Session::get('attributes')) {
            $attributes = Session::get('attributes');
            $year = !empty($attributes['year']) ? $attributes['year'] : null;
            $month = !empty($attributes['month']) ? $attributes['month'] : null;
            
            // Temporarily disable ONLY_FULL_GROUP_BY for this query
            DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");
    
            if($year) {
                $query->where(function ($query) use ($year, $month) {
                    $query->where(function ($q) use ($year, $month) {
                        $q->where('begin_year', '<', $year)
                          ->orWhere(function ($sq) use ($year, $month) {
                              $sq->where('begin_year', '=', $year)
                                 ->where('begin_month', '<=', $month ?? 12);
                          });
                    })
                    ->where(function ($q) use ($year, $month) {
                        $q->where('end_year', '>', $year)
                          ->orWhere(function ($sq) use ($year, $month) {
                              $sq->where('end_year', '=', $year)
                                 ->where('end_month', '>=', $month ?? 1);
                          })
                          ->orWhereNull('end_year');
                    });
                });
            }
    
            // Filter by other attributes (excluding year and month)
            $filteredAttributes = array_diff_key($attributes, array_flip(['month', 'year']));
    
            if (!empty($filteredAttributes)) {
                $query->where(function ($query) use ($filteredAttributes) {
                    foreach ($filteredAttributes as $key => $value) {
                        if (!empty($value)) {
                            $query->where('applicability', 'LIKE', "%{$value}%");
                        }
                    }
                });
            }
        }
        
        // Get the filtered products
        $this->products = $query
            ->groupBy('callout', 'qty', 'applicability', 'partnumber', 'label_en', 'label_ar', 'formattedbegindate', 'formattedenddate')
            ->orderBy('callout')
            ->orderBy('partnumber')
            ->get();
            
        // Restore original SQL mode
        DB::statement("SET sql_mode=(SELECT CONCAT(@@sql_mode, ',ONLY_FULL_GROUP_BY'))");
        
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
