<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Country;
use App\Models\City;
use Illuminate\Http\Request;
use Validator;
use Auth;

class WarehouseController extends VendorBaseController
{
    /**
     * عرض صفحة إعدادات المستودع
     */
    public function index()
    {
        $user = Auth::user();
        $countries = Country::all();
        $cities = [];

        // إذا كان التاجر لديه دولة محددة، جلب المدن
        if ($user->country) {
            $country = Country::where('country_name', $user->country)->first();
            if ($country) {
                $cities = City::where('country_id', $country->id)->get();
            }
        }

        return view('vendor.warehouse.index', compact('user', 'countries', 'cities'));
    }

    /**
     * تحديث إعدادات المستودع
     */
    public function update(Request $request)
    {
        $rules = [
            'warehouse_city' => 'nullable|string|max:255',
            'warehouse_address' => 'nullable|string|max:500',
            'warehouse_lat' => 'nullable|numeric|between:-90,90',
            'warehouse_lng' => 'nullable|numeric|between:-180,180',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();

        $user->warehouse_city = $request->warehouse_city;
        $user->warehouse_address = $request->warehouse_address;
        $user->warehouse_lat = $request->warehouse_lat;
        $user->warehouse_lng = $request->warehouse_lng;

        $user->save();

        return back()->with('success', __('Warehouse settings updated successfully'));
    }

    /**
     * API: جلب المدن بناءً على الدولة
     */
    public function getCities(Request $request)
    {
        $countryName = $request->country;
        $country = Country::where('country_name', $countryName)->first();

        if (!$country) {
            return response()->json(['cities' => []]);
        }

        $cities = City::where('country_id', $country->id)->get();

        // اسم المدينة (إنجليزي فقط - لا يوجد city_name_ar)
        $citiesWithLocale = $cities->map(function($city) {
            $city->display_name = $city->city_name;
            return $city;
        });

        return response()->json(['cities' => $citiesWithLocale]);
    }
}
