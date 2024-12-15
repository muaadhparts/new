<?php

namespace App\Http\Controllers\Vendor;

use App\Models\PickupPoint;
use Illuminate\Http\Request;


use Validator;
use Datatables;

class PickupPointController extends VendorBaseController
{
  

    public function index()
    { 
        $datas = PickupPoint::where('user_id', $this->user->id)->get();
        return view('vendor.pickup.index', compact('datas'));
    }

    //*** GET Request
    public function create()
    {
        $sign = $this->curr;
        return view('vendor.pickup.create', compact('sign'));
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = ['location' => 'required'];
        $request->validate($rules);

        $data = new PickupPoint();
        $data->location = $request->location;
        $data->user_id = $this->user->id;
        $data->save();

        $msg = __('New Data Added Successfully.');
        return back()->with('success', $msg);
    }

    //*** GET Request
    public function edit($id)
    {
        $data = PickupPoint::findOrFail($id);
        return view('vendor.pickup.edit', compact('data'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        $rules = ['location' => 'required'];
        $request->validate($rules);

        $data = PickupPoint::findOrFail($id);
        $data->location = $request->location;
        $data->user_id = $this->user->id;
        $data->save();
        $msg = __('Data Updated Successfully.');
        return back()->with('success', $msg);
    }

    //*** GET Request Delete
    public function destroy($id)
    {
        $data = PickupPoint::findOrFail($id);
        $data->delete();
        $msg = __('Data Deleted Successfully.');
        return back()->with('success', $msg);
    }

    public function status($id1, $id2)
    {
        $data = PickupPoint::findOrFail($id1);
        $data->status = $id2;
        $data->update();
        //--- Redirect Section
        $msg = __('Status Updated Successfully.');
        return back()->with('success', $msg);
    }
}
