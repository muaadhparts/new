<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Modules\Product\Entities\Illustrations;

class TestController extends Controller
{

    public function illustrated( )
    {
        //        JNKAY1AP7BM200155






//        Session::put('catalog', $catalog);


        $Illustrations  =   \App\Models\Illustrations::where('data','Y62GL')
            ->where('code','101A-001')
            ->first();
        $partCallouts = collect(  $Illustrations->illustrationWithCallouts)['partCallouts'];
//        $partCallouts = collect($Illustrations->illustrationWithCallouts);
//         dd( $Illustrations,$partCallouts );
        //

        //        $keyExists = array_key_exists('data', $data); // true
        //        $products = \Modules\Product\Entities\Product::select('full_part_number','id','category_id','part_code','part_number','formattedEndDate','formattedBeginDate','label')->where('category_id', $id)->groupBy('part_code')->get();
//        $products =  DB::table(Str::lower($category->data))
////                 ->select('code','PartNumber')
//                    ->distinct('PartNumber' ,'callout')
//                  ->where('code', $category->code)
//                 ->orderBy('callout')
//                ->get();
        $category = Category::first();
//        $products = Product::take(10)->get();

        $products = DB::table(Str::lower('Y62GL'))
            ->select('code', 'partNumber', 'callout' ,'label_'.app()->getLocale())
            ->distinct()
            ->where('code','101A-001')
            ->orderBy('callout')
            ->get();

//        dd($products);
        return view( 'frontend.illustrated', compact('category', 'products', 'partCallouts'));
    }

}
