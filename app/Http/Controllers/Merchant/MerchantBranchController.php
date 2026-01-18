<?php

namespace App\Http\Controllers\Merchant;

use App\Models\MerchantBranch;
use App\Models\City;
use App\Models\Country;
use Illuminate\Http\Request;


use Validator;
use Datatables;

/**
 * MerchantBranchController - Manage merchant warehouse/branch locations
 *
 * This is where merchants set their shipping origin branches.
 * Used by local couriers to collect orders for delivery to customers.
 */
class MerchantBranchController extends MerchantBaseController
{


    public function index()
    {
        $datas = MerchantBranch::with(['city', 'country'])->where('user_id', $this->user->id)->get();
        return view('merchant.branch.index', compact('datas'));
    }

    //*** GET Request
    public function create()
    {
        $sign = $this->curr;
        $countries = Country::whereStatus(1)->whereHas('cities', function($q) {
            $q->where('status', 1);
        })->get();
        return view('merchant.branch.create', compact('sign', 'countries'));
    }

    /**
     * Get cities by country for AJAX
     */
    public function getCitiesByCountry(Request $request)
    {
        if (!$request->country_id) {
            return response()->json([
                'success' => false,
                'cities' => '<option value="">' . __('Select Country First') . '</option>',
            ]);
        }

        $cities = City::where('country_id', $request->country_id)
            ->where('status', 1)
            ->orderBy('city_name')
            ->get(['id', 'city_name']);

        $options = '<option value="">' . __('Select City') . '</option>';
        foreach ($cities as $city) {
            $options .= '<option value="' . $city->id . '">' . htmlspecialchars($city->city_name) . '</option>';
        }

        return response()->json([
            'success' => true,
            'cities' => $options,
        ]);
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = [
            'warehouse_name' => 'required|string|max:100',
            'tryoto_warehouse_code' => 'nullable|string|max:50',
            'location' => 'required',
            'country_id' => 'required|exists:countries,id',
            'city_id' => 'required|exists:cities,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ];
        $request->validate($rules);

        $data = new MerchantBranch();
        $data->warehouse_name = $request->warehouse_name;
        $data->tryoto_warehouse_code = $request->tryoto_warehouse_code;
        $data->location = $request->location;
        $data->country_id = $request->country_id;
        $data->city_id = $request->city_id;
        $data->latitude = $request->latitude;
        $data->longitude = $request->longitude;
        $data->user_id = $this->user->id;
        $data->save();

        $msg = __('New Data Added Successfully.');
        return back()->with('success', $msg);
    }

    //*** GET Request
    public function edit($id)
    {
        $data = MerchantBranch::findOrFail($id);
        $countries = Country::whereStatus(1)->whereHas('cities', function($q) {
            $q->where('status', 1);
        })->get();

        // Get the country from merchant branch or from city
        $selectedCountryId = $data->country_id;
        if (!$selectedCountryId && $data->city_id) {
            $currentCity = City::find($data->city_id);
            $selectedCountryId = $currentCity ? $currentCity->country_id : null;
        }

        // Get cities for the selected country
        $cities = $selectedCountryId
            ? City::where('country_id', $selectedCountryId)->where('status', 1)->orderBy('city_name')->get()
            : collect();

        return view('merchant.branch.edit', compact('data', 'countries', 'cities', 'selectedCountryId'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        $rules = [
            'warehouse_name' => 'required|string|max:100',
            'tryoto_warehouse_code' => 'nullable|string|max:50',
            'location' => 'required',
            'country_id' => 'required|exists:countries,id',
            'city_id' => 'required|exists:cities,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ];
        $request->validate($rules);

        $data = MerchantBranch::findOrFail($id);
        $data->warehouse_name = $request->warehouse_name;
        $data->tryoto_warehouse_code = $request->tryoto_warehouse_code;
        $data->location = $request->location;
        $data->country_id = $request->country_id;
        $data->city_id = $request->city_id;
        $data->latitude = $request->latitude;
        $data->longitude = $request->longitude;
        $data->user_id = $this->user->id;
        $data->save();
        $msg = __('Data Updated Successfully.');
        return back()->with('success', $msg);
    }

    //*** GET Request Delete (AJAX)
    public function destroy($id)
    {
        $data = MerchantBranch::where('id', $id)->where('user_id', $this->user->id)->first();
        if (!$data) {
            return response()->json(['error' => __('Data not found.')], 404);
        }
        $data->delete();
        return response()->json(__('Data Deleted Successfully.'));
    }

    public function status($id1, $id2)
    {
        $data = MerchantBranch::findOrFail($id1);
        $data->status = $id2;
        $data->update();
        //--- Redirect Section
        $msg = __('Status Updated Successfully.');
        return back()->with('success', $msg);
    }
}
