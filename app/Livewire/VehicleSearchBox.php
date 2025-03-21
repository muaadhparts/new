<?php

namespace App\Livewire;

use App\Models\Catalog;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class VehicleSearchBox extends Component
{
    public $query = '';
    public $vehicle;
    public $results = [];

    public function updatedQuery()
    {
        $this->results = $this->search($this->query);
    }

    private function convertLettersToNumbers($input)
    {
        // خريطة التحويل
        $mapping = [
            'M' => '0', 'A' => '1', 'B' => '2', 'C' => '3',
            'D' => '4', 'E' => '5', 'F' => '6', 'G' => '7',
            'H' => '8', 'K' => '9'
        ];

        // تقسيم الإدخال إلى أول 5 أحرف والباقي
        $firstFive = substr($input, 0, 5);
        $remaining = substr($input, 5);

        // استبدال الحروف في أول 5 أحرف
        $converted = strtr($firstFive, $mapping);

        // دمج الأجزاء
        return $converted . $remaining;
    }



    private function cleanInput($input)
    {
        // إزالة المسافات الزائدة
        $input = trim($input);

        // إزالة الفواصل والمسافات الزائدة داخل النص
        $input = preg_replace('/\s+/', ' ', $input);

        // تحويل الأرقام العربية إلى إنجليزية
        $input = strtr($input, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'
        ]);

//        return   $this->convertLettersToNumbers($input);
        return  $input;
        // تحويل النصوص إلى أحرف صغيرة للتوحيد
//        return Str::lower($input);
    }

    public function search2($query)
    {
//        dd($this);
        if ($this->query) {

            $results = DB::table(Str::lower($this->vehicle))
                ->where('partnumber', 'like', "{$query}%")
                ->orWhere('callout', 'like', "{$query}%")
                ->orWhere('label_en', 'like', "{$query}%")
                ->orWhere('label_ar', 'like', "{$query}%")
                ->select('id', 'partnumber', 'callout', 'label_en', 'label_ar')
                ->limit(100)
                ->pluck( 'partnumber')
                ->unique()
                ->toArray();


//        $products2=  Product::pluck('sku')->toArray();
//        $results =   array_merge_recursive_distinct($results ,$products2 );
        $products =   implode(',',$results);
        $vehicle2   = Catalog::with('brand:id,name')->where('data',   $this->vehicle)->first();


//        dd($this->vehicle);

//        $products =  Product::whereIn('sku', $results)
//            ->take(20)
//            ->get();

//            $this->redirect(CatlogsProducts::class);
//            dd($products2 ,$results ,$products);
        redirect()->route('catlogs.products', ['id' => $vehicle2->brand->name , 'data' => $vehicle2->data , 'products' => $products]);
//
//        $this->redirectRoute('catlogs.products', [ 'products' => $products]);


//        dd($results);
        }
    }


    public function search($query)
    {
        $query = $this->cleanInput($query);
//         dd($query);

//        dd($this);

        $results = DB::table(Str::lower($this->vehicle))
            ->where('partnumber', 'like', "{$query}%")
            ->orWhere('callout', 'like', "{$query}%")
            ->orWhere('label_en', 'like', "{$query}%")
            ->orWhere('label_ar', 'like', "{$query}%")
            ->select('id', 'partnumber', 'callout', 'label_en', 'label_ar')
            ->limit(50)
            ->get();


        // إذا لم تكن هناك نتائج، البحث داخل النص
        if ($results->isEmpty()) {
//            dd($results);
            $results = DB::table(Str::lower($this->vehicle))
                ->where('partnumber', 'like', "%{$query}%")
                ->orWhere('callout', 'like', "%{$query}%")
                ->orWhere('label_en', 'like', "%{$query}%")
                ->orWhere('label_ar', 'like', "%{$query}%")
                ->select('id', 'partnumber', 'callout', 'label_en', 'label_ar')
                ->limit(50)
                ->get();
        }

        return $results;

    }




    public function selectItem($value)
    {
//        dd( $value);
//        $this->query = $value;
        redirect()->route('search.result', ['sku' => $value]);
//        $this->query = $value;

//        $this->results = [];
    }


    public function render()
    {
        return view('livewire.vehicle-search-box');
    }
}
