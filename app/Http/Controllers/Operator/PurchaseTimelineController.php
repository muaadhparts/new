<?php

namespace App\Http\Controllers\Operator;

use App\{
    Models\Purchase,
    Models\PurchaseTimeline
};
use Illuminate\Http\Request;
use Validator;

class PurchaseTimelineController extends OperatorBaseController
{

   //*** GET Request
    public function index($id)
    {
    	$purchase = Purchase::findOrFail($id);
        return view('operator.purchase.track',compact('purchase'));
    }

   //*** GET Request
    public function load($id)
    {
        $purchase = Purchase::findOrFail($id);
        return view('operator.purchase.track-load',compact('purchase'));
    }


    public function add()
    {


        //--- Logic Section

        $title = $_GET['title'];

        $ck = PurchaseTimeline::where('purchase_id','=',$_GET['id'])->where('title','=',$title)->first();
        if($ck){
            $ck->purchase_id = $_GET['id'];
            $ck->title = $_GET['title'];
            $ck->text = $_GET['text'];
            $ck->update();
        }
        else {
            $data = new PurchaseTimeline;
            $data->purchase_id = $_GET['id'];
            $data->title = $_GET['title'];
            $data->text = $_GET['text'];
            $data->save();
        }


        //--- Logic Section Ends


    }


    //*** POST Request
    public function store(Request $request)
    {

        $rules = [
               'title' => 'unique:purchase_timelines',
                ];
        $customs = [
               'title.unique' => 'This title has already been taken.',
                   ];
        $validator = Validator::make($request->all(), $rules, $customs);
        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }

        //--- Logic Section

        $title = $request->title;
        $ck = PurchaseTimeline::where('purchase_id','=',$request->purchase_id)->where('title','=',$title)->first();
        if($ck) {
            $ck->purchase_id = $request->purchase_id;
            $ck->title = $request->title;
            $ck->text = $request->text;
            $ck->update();

        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends

        }
        else {
            $data = new PurchaseTimeline;
            $input = $request->all();
            $data->fill($input)->save();
        }

        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('New Data Added Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }


    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Validation Section
        $rules = [
               'title' => 'unique:purchase_timelines,title,'.$id
                ];
        $customs = [
               'title.unique' => __('This title has already been taken.'),
                   ];
        $validator = Validator::make($request->all(), $rules, $customs);
        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $data = PurchaseTimeline::findOrFail($id);
        $input = $request->all();
        $data->update($input);
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends

    }

    //*** GET Request
    public function delete($id)
    {
        $data = PurchaseTimeline::findOrFail($id);
        $data->delete();
        //--- Redirect Section
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

}
