<?php

namespace App\Http\Controllers\Operator;

use App\{
    Models\Seotool,
    Models\CatalogItemClick
};
use Illuminate\Http\Request;
use Carbon\Carbon;

class SeoToolController extends OperatorBaseController
{
    public function analytics()
    {
        $tool = Seotool::find(1);
        return view('operator.seotool.googleanalytics',compact('tool'));
    }

    public function analyticsupdate(Request $request)
    {
        $tool = Seotool::findOrFail(1);

        $input = $request->all();

        if ($request->has('meta_keys'))
         {
            $input['meta_keys'] = implode(',', $request->meta_keys);       
         } 


        $tool->update($input);

        cache()->forget('seotools');
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);  
    }  

    public function keywords()
    {
        $tool = Seotool::find(1);
        return view('operator.seotool.meta-keywords',compact('tool'));
    }

     
    public function popular($id)
    {
        $expDate = Carbon::now()->subDays($id);

        // Group by merchant_item_id for merchant-specific tracking
        $items = CatalogItemClick::with(['catalogItem.brand', 'merchantItem.user', 'merchantItem.qualityBrand'])
            ->whereDate('date', '>', $expDate)
            ->get()
            ->groupBy(function ($item) {
                // Group by merchant_item_id if available, otherwise by catalog_item_id
                return $item->merchant_item_id ?? 'catalog_item_' . $item->catalog_item_id;
            });

        $val = $id;
        // Note: 'productss' variable name kept for view compatibility
        $productss = $items;
        return view('operator.seotool.popular', compact('val', 'productss'));
    }  

}
