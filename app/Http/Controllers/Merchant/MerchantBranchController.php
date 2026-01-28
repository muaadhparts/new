<?php

namespace App\Http\Controllers\Merchant;

use App\Domain\Merchant\Models\MerchantBranch;
use App\Domain\Shipping\Models\City;
use App\Domain\Shipping\Models\Country;
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

        // PRE-COMPUTED: Status display data (no @php in view)
        $datasDisplay = $datas->map(function ($data) {
            return [
                'id' => $data->id,
                'item' => $data,
                'countryName' => $data->country?->country_name ?? '-',
                'cityName' => $data->city?->city_name ?? '-',
                'location' => $data->location,
                'latitude' => $data->latitude,
                'longitude' => $data->longitude,
                'latitude_formatted' => $data->latitude ? number_format($data->latitude, 6) : null,
                'longitude_formatted' => $data->longitude ? number_format($data->longitude, 6) : null,
                'status' => $data->status,
                'statusClass' => $data->status == 1 ? 'active' : 'deactive',
                'statusActiveSelected' => $data->status == 1 ? 'selected' : '',
                'statusInactiveSelected' => $data->status == 0 ? 'selected' : '',
                'statusActiveUrl' => route('merchant-branch-status', ['id' => $data->id, 'status' => 1]),
                'statusInactiveUrl' => route('merchant-branch-status', ['id' => $data->id, 'status' => 0]),
                'editUrl' => route('merchant-branch-edit', $data->id),
                'deleteUrl' => route('merchant-branch-delete', $data->id),
            ];
        });

        return view('merchant.branch.index', [
            'datas' => $datas,
            'datasDisplay' => $datasDisplay,
        ]);
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
