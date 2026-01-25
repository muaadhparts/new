<?php

namespace App\Http\Controllers\Merchant;

use App\Domain\Shipping\Models\Shipping;

use Illuminate\Http\Request;

use Validator;
use Datatables;

class ShippingController extends MerchantBaseController
{


    public function index(){
        $datas = Shipping::where('user_id', $this->user->id)->get();
        return view('merchant.shipping.index', compact('datas'));
    }

    //*** GET Request
    public function create()
    {
        $sign = $this->curr;
        return view('merchant.shipping.create',compact('sign'));
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = [
            'name' => 'unique:shippings',
            'provider' => 'required|in:manual,tryoto',
            'price' => 'required|numeric|min:0',
            'free_above' => 'nullable|numeric|min:0',
        ];
        $customs = ['name.unique' => __('This name has already been taken.')];
        $request->validate($rules,$customs);
        //--- Validation Section Ends

        //--- Logic Section
        $sign = $this->curr;
        $data = new Shipping();
        $input = $request->all();
        $input['user_id'] = $this->user->id;
        $input['provider'] = $input['provider'] ?? 'manual';
        $input['price'] = ($input['price'] / $sign->value);
        $input['free_above'] = !empty($input['free_above']) ? ($input['free_above'] / $sign->value) : 0;
        $data->fill($input)->save();
        //--- Logic Section Ends

        return back()->with('success',__('Shipping Added Successfully'));
    }

    //*** GET Request
    public function edit($id)
    {
        $sign = $this->curr;
        $data = Shipping::findOrFail($id);
        return view('merchant.shipping.edit',compact('data','sign'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Validation Section
        $rules = [
            'name' => 'unique:shippings,name,'.$id,
            'provider' => 'required|in:manual,tryoto',
            'price' => 'required|numeric|min:0',
            'free_above' => 'nullable|numeric|min:0',
        ];
        $customs = ['name.unique' => __('This name has already been taken.')];
        $request->validate($rules,$customs);
        //--- Logic Section
        $sign = $this->curr;
        $data = Shipping::findOrFail($id);
        $input = $request->all();
        $input['provider'] = $input['provider'] ?? 'manual';
        $input['price'] = ($input['price'] / $sign->value);
        $input['free_above'] = !empty($input['free_above']) ? ($input['free_above'] / $sign->value) : 0;
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