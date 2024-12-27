<?php

namespace App\Livewire;

use App\Models\Product;
use Illuminate\Support\Str;
use Livewire\Component;

class SearchBox extends Component
{

    public $query = '';
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

        return   $this->convertLettersToNumbers($input);
        // تحويل النصوص إلى أحرف صغيرة للتوحيد
//        return Str::lower($input);
    }


    public function search($query)
    {
        $query = $this->cleanInput($query);
//         dd($query);

        $results = \App\Models\Product::where('sku', 'like', "{$query}%")
            ->orWhere('name', 'like', "{$query}%")
            ->orWhere('label_en', 'like', "{$query}%")
            ->orWhere('label_ar', 'like', "{$query}%")
            ->select('id', 'sku', 'name', 'label_en', 'label_ar')
            ->limit(50)
            ->get();

        // إذا لم تكن هناك نتائج، البحث داخل النص
        if ($results->isEmpty()) {
            $results = \App\Models\Product::where('sku', 'like', "%{$query}%")
                ->orWhere('name', 'like', "%{$query}%")
                ->orWhere('label_en', 'like', "%{$query}%")
                ->orWhere('label_ar', 'like', "%{$query}%")
                ->select('id', 'sku', 'name', 'label_en', 'label_ar')
                ->limit(50)
                ->get();
        }

        return $results;
//        return \App\Models\Product::where('sku', 'like', "{$query}%")
//            ->orWhere('name', 'like', "{$query}%")
//            ->orWhere('label_en', 'like', "{$query}%")
//            ->orWhere('label_ar', 'like', "{$query}%")
//            ->select('id', 'sku', 'name', 'label_en', 'label_ar')
//            ->limit(50)
//            ->get();

//        return \App\Models\Product::Where('sku', 'like', "%{$query}%")
//            ->orwhere('name', 'like', "%{$query}%")
//            ->orwhere('label_en', 'like', "%{$query}%")
//            ->orwhere('label_ar', 'like', "%{$query}%")
//            ->select('id', 'sku', 'name', 'label_en', 'label_ar')
//            ->limit(50)
//            ->get();
//            ->limit(10)
//            ->get(['sku as value', 'name as key'])
//            ->toArray();
    }



    public function selectItem($value)
    {
//        dd($value);
        redirect()->route('search.result', ['sku' => $value]);
//        $this->query = $value;

//        $this->results = [];
    }

    public function render()
    {
        return view('livewire.search-box');
    }
}
