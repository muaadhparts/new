<?php

namespace App\Http\Controllers\Merchant;

use App\Models\PickupPoint;
use App\Models\City;
use Illuminate\Http\Request;


use Validator;
use Datatables;

class PickupPointController extends MerchantBaseController
{


    public function index()
    {
        $datas = PickupPoint::with('city')->where('user_id', $this->user->id)->get();
        return view('merchant.pickup.index', compact('datas'));
    }

    //*** GET Request
    public function create()
    {
        $sign = $this->curr;
        $cities = City::orderBy('city_name', 'asc')->get();
        return view('merchant.pickup.create', compact('sign', 'cities'));
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = [
            'location' => 'required',
            'city_id' => 'required|exists:cities,id',
        ];
        $request->validate($rules);

        $data = new PickupPoint();
        $data->location = $request->location;
        $data->city_id = $request->city_id;
        $data->user_id = $this->user->id;
        $data->save();

        $msg = __('New Data Added Successfully.');
        return back()->with('success', $msg);
    }

    //*** GET Request
    public function edit($id)
    {
        $data = PickupPoint::findOrFail($id);
        $cities = City::orderBy('city_name', 'asc')->get();
        return view('merchant.pickup.edit', compact('data', 'cities'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        $rules = [
            'location' => 'required',
            'city_id' => 'required|exists:cities,id',
        ];
        $request->validate($rules);

        $data = PickupPoint::findOrFail($id);
        $data->location = $request->location;
        $data->city_id = $request->city_id;
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
