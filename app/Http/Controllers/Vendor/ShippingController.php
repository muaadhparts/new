<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Shipping;

use Illuminate\Http\Request;

use Validator;
use Datatables;

class ShippingController extends VendorBaseController
{


    public function index(){
        $datas = Shipping::where('user_id', $this->user->id)->get();
        return view('vendor.shipping.index', compact('datas'));
    }

    //*** GET Request
    public function create()
    {
        $sign = $this->curr;
        return view('vendor.shipping.create',compact('sign'));
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = ['title' => 'unique:shippings'];
        $customs = ['title.unique' => __('This title has already been taken.')];
        $request->validate($rules,$customs);
        //--- Validation Section Ends

        //--- Logic Section
        $sign = $this->curr;
        $data = new Shipping();
        $input = $request->all();
        $input['user_id'] = $this->user->id;
        $input['price'] = ($input['price'] / $sign->value);
        $data->fill($input)->save();
        //--- Logic Section Ends

        return back()->with('success',__('Shipping Added Successfully'));
    }

    //*** GET Request
    public function edit($id)
    {
        $sign = $this->curr;
        $data = Shipping::findOrFail($id);
        return view('vendor.shipping.edit',compact('data','sign'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Validation Section
        $rules = ['title' => 'unique:shippings,title,'.$id];
        $customs = ['title.unique' => __('This title has already been taken.')];
        $request->validate($rules,$customs);
        //--- Logic Section
        $sign = $this->curr;
        $data = Shipping::findOrFail($id);
        $input = $request->all();
        $input['price'] = ($input['price'] / $sign->value);
        $data->update($input);
        //--- Logic Section Ends

        return back()->with('success',__('Shipping Updated Successfully'));     
    }

    //*** GET Request Delete
    public function destroy($id)
    {
        $data = Shipping::findOrFail($id);
        $data->delete();
        return back()->with('success',__('Shipping Deleted Successfully')); 
    }
}