<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Package;
use Illuminate\Http\Request;


use Validator;
use Datatables;

class PackageController extends VendorBaseController
{

 
    public function index(){
        $datas = Package::where('user_id',$this->user->id)->get();
        return view('vendor.package.index',compact('datas'));
    }

    //*** GET Request
    public function create()
    {
        $sign = $this->curr;
        return view('vendor.package.create',compact('sign'));
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = ['title' => 'unique:packages'];
        $customs = ['title.unique' => __('This title has already been taken.')];
        
        $request->validate($rules,$customs);
        //--- Logic Section
        $sign = $this->curr;
        $data = new Package();
        $input = $request->all();
        $input['user_id'] = $this->user->id;
        $input['price'] = ($input['price'] / $sign->value);
        $data->fill($input)->save();
        //--- Logic Section Ends

        return back()->with('success',__('Package Added Successfully'));
    }

    //*** GET Request
    public function edit($id)
    {
        $sign = $this->curr;
        $data = Package::findOrFail($id);
        return view('vendor.package.edit',compact('data','sign'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Validation Section
        $rules = ['title' => 'unique:packages,title,'.$id];
        $customs = ['title.unique' => __('This title has already been taken.')];
        $request->validate($rules,$customs);
        
        //--- Logic Section
        $sign = $this->curr;
        $data = Package::findOrFail($id);
        $input = $request->all();
        $input['price'] = ($input['price'] / $sign->value);
        $data->update($input);
        //--- Logic Section Ends

        return back()->with('success',__('Package Updated Successfully'));         
    }

    //*** GET Request Delete
    public function destroy($id)
    {
        $data = Package::findOrFail($id);
        $data->delete();
        return back()->with('success',__('Package Deleted Successfully'));
    }
}