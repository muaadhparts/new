<?php

namespace App\Http\Controllers\Vendor;

use App\Models\Country;
use App\Models\State;
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
        $states = [];
        $cities = [];

        // إذا كان التاجر لديه دولة محددة، جلب الولايات والمدن
        if ($user->country) {
            $country = Country::where('country_name', $user->country)->first();
            if ($country) {
                $states = State::where('country_id', $country->id)->get();
            }
        }

        if ($user->warehouse_state) {
            $cities = City::where('state_id', $user->warehouse_state)->get();
        }

        return view('vendor.warehouse.index', compact('user', 'countries', 'states', 'cities'));
    }

    /**
     * تحديث إعدادات المستودع
     */
    public function update(Request $request)
    {
        $rules = [
            'warehouse_state' => 'nullable|exists:states,id',
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

        $user->warehouse_state = $request->warehouse_state;
        $user->warehouse_city = $request->warehouse_city;
        $user->warehouse_address = $request->warehouse_address;
        $user->warehouse_lat = $request->warehouse_lat;
        $user->warehouse_lng = $request->warehouse_lng;

        $user->save();

        return back()->with('success', __('Warehouse settings updated successfully'));
    }

    /**
     * API: جلب الولايات بناءً على الدولة
     */
    public function getStates(Request $request)
    {
        $countryName = $request->country;
        $country = Country::where('country_name', $countryName)->first();

        if (!$country) {
            return response()->json(['states' => []]);
        }

        $states = State::where('country_id', $country->id)->get();

        // تطبيق منطق اللغة على الولايات
        $locale = app()->getLocale();
        $statesWithLocale = $states->map(function($state) use ($locale) {
            $state->display_name = ($locale == 'ar')
                ? ($state->state_ar ?: $state->state)
                : $state->state;
            return $state;
        });

        return response()->json(['states' => $statesWithLocale]);
    }

    /**
     * API: جلب المدن بناءً على الولاية
     */
    public function getCities(Request $request)
    {
        $stateId = $request->state_id;
        $cities = City::where('state_id', $stateId)->get();

        // تطبيق منطق اللغة على المدن
        $locale = app()->getLocale();
        $citiesWithLocale = $cities->map(function($city) use ($locale) {
            $city->display_name = ($locale == 'ar')
                ? ($city->city_name_ar ?: $city->city_name)
                : $city->city_name;
            return $city;
        });

        return response()->json(['cities' => $citiesWithLocale]);
    }
}
