<?php

namespace App\Http\Controllers\Courier;

use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\DeliveryCourier;
use App\Models\CourierServiceArea;
use App\Models\CourierTransaction;
use App\Models\CourierSettlement;
use App\Services\CourierAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CourierController extends CourierBaseController
{
    protected CourierAccountingService $accountingService;

    public function __construct()
    {
        parent::__construct();
        $this->accountingService = app(CourierAccountingService::class);
    }

    public function index()
    {
        $user = $this->courier;
        $purchases = DeliveryCourier::where('courier_id', $this->courier->id)
            ->whereNotNull('purchase_id')
            ->with(['purchase', 'merchantLocation'])
            ->orderby('id', 'desc')->take(8)->get();

        // Get accounting report
        $report = $this->accountingService->getCourierReport($this->courier->id);
        $currency = Currency::where('is_default', 1)->first();

        return view('courier.dashbaord', compact('purchases', 'user', 'report', 'currency'));
    }

    public function profile()
    {
        $user = $this->courier;
        return view('courier.profile', compact('user'));
    }

    public function profileupdate(Request $request)
    {

        $rules =
            [
            'photo' => 'mimes:jpeg,jpg,png,svg',
            'email' => 'unique:users,email,' . $this->courier->id,
        ];

        $customs = [
            'photo.mimes' => __('The image must be a file of type: jpeg, jpg, png, svg.'),
        ];

        $request->validate($rules, $customs);

        //--- Validation Section Ends
        $input = $request->all();
        $data = $this->courier;
        if ($file = $request->file('photo')) {
            $extensions = ['jpeg', 'jpg', 'png', 'svg'];
            if (!in_array($file->getClientOriginalExtension(), $extensions)) {
                return back()->with('unsuccess', __('The image must be a file of type: jpeg, jpg, png, svg.'));
            }

            $name = \PriceHelper::ImageCreateName($file);
            $file->move('assets/images/users/', $name);
            if ($data->photo != null) {
                if (file_exists(public_path() . '/assets/images/users/' . $data->photo)) {
                    unlink(public_path() . '/assets/images/users/' . $data->photo);
                }
            }
            $input['photo'] = $name;
        }
        $data->update($input);

        return back()->with('success', __('Profile Updated Successfully!'));
    }

    public function resetform()
    {
        return view('courier.reset');
    }

    public function reset(Request $request)
    {
        $user = $this->courier;
        if ($request->cpass) {
            if (Hash::check($request->cpass, $user->password)) {
                if ($request->newpass == $request->renewpass) {
                    $input['password'] = Hash::make($request->newpass);
                } else {
                    return back()->with('unsuccess', __('Confirm password does not match.'));
                }
            } else {
                return back()->with('unsuccess', __('Current password Does not match.'));
            }
        }
        $user->update($input);
        return back()->with('success', __('Password Updated Successfully!'));
    }

    public function serviceArea()
    {
        $cities = City::whereStatus(1)->get();
        $courier = $this->courier;
        $service_area = CourierServiceArea::where('courier_id', $courier->id)->paginate(10);
        return view('courier.service-area', compact('service_area', 'cities'));
    }

    public function serviceAreaCreate()
    {
        $countries = Country::whereStatus(1)->whereHas('cities', function($q) {
            $q->where('status', 1);
        })->get();
        return view('courier.add_service', compact('countries'));
    }

    /**
     * Get cities by country for AJAX
     */
    public function getCitiesByCountry(Request $request)
    {
        // Validate country_id
        if (!$request->country_id) {
            return response()->json([
                'success' => false,
                'message' => 'Country ID is required',
                'cities' => '<option value="">' . __('Select Country First') . '</option>',
                'count' => 0
            ]);
        }

        // Get active cities for this country
        $cities = City::where('country_id', $request->country_id)
            ->where('status', 1)
            ->orderBy('city_name')
            ->get(['id', 'city_name']);

        // Build options HTML
        $options = '<option value="">' . __('Select City') . '</option>';
        foreach ($cities as $city) {
            $options .= '<option value="' . $city->id . '">' . htmlspecialchars($city->city_name) . '</option>';
        }

        return response()->json([
            'success' => true,
            'cities' => $options,
            'count' => $cities->count()
        ]);
    }

    public function serviceAreaStore(Request $request)
    {
        // Step 1: Basic validation
        $request->validate([
            'country_id' => 'required|exists:countries,id',
            'service_area_id' => 'required|exists:cities,id',
            'price' => 'required|min:1|numeric',
            'service_radius_km' => 'required|integer|min:1|max:500',
        ]);

        // Step 2: Strict backend validation - verify city belongs to country and is active
        $city = City::where('id', $request->service_area_id)
            ->where('country_id', $request->country_id)
            ->where('status', 1)
            ->first();

        if (!$city) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['service_area_id' => __('Invalid city selection. City must be active and belong to the selected country.')]);
        }

        // Step 3: Check country is active
        $country = Country::where('id', $request->country_id)->where('status', 1)->first();
        if (!$country) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['country_id' => __('Selected country is not available.')]);
        }

        // Step 4: Check uniqueness - this courier doesn't already have this city
        $exists = CourierServiceArea::where('courier_id', $this->courier->id)
            ->where('city_id', $city->id)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['service_area_id' => __('You already have a service area for this city.')]);
        }

        // Step 5: Create service area
        $service_area = new CourierServiceArea();
        $service_area->courier_id = $this->courier->id;
        $service_area->city_id = $city->id;
        $service_area->price = $request->price / $this->curr->value;
        $service_area->service_radius_km = $request->service_radius_km;
        $service_area->latitude = $city->latitude;
        $service_area->longitude = $city->longitude;
        $service_area->status = 1; // Active by default
        $service_area->save();

        return redirect()->route('courier-service-area')->with('success', __('Successfully created your service area'));
    }

    public function serviceAreaEdit($id)
    {
        $service_area = CourierServiceArea::findOrFail($id);
        $countries = Country::whereStatus(1)->whereHas('cities', function($q) {
            $q->where('status', 1);
        })->get();

        // Get the country of the current city
        $currentCity = City::find($service_area->city_id);
        $selectedCountryId = $currentCity ? $currentCity->country_id : null;

        // Get cities for the selected country
        $cities = $selectedCountryId
            ? City::where('country_id', $selectedCountryId)->where('status', 1)->orderBy('city_name')->get()
            : collect();

        return view('courier.edit_service', compact('countries', 'cities', 'service_area', 'selectedCountryId'));
    }

    public function serviceAreaUpdate(Request $request, $id)
    {
        // Step 1: Verify ownership
        $service_area = CourierServiceArea::where('id', $id)
            ->where('courier_id', $this->courier->id)
            ->firstOrFail();

        // Step 2: Basic validation
        $request->validate([
            'country_id' => 'required|exists:countries,id',
            'service_area_id' => 'required|exists:cities,id',
            'price' => 'required|min:1|numeric',
            'service_radius_km' => 'required|integer|min:1|max:500',
        ]);

        // Step 3: Strict backend validation - verify city belongs to country and is active
        $city = City::where('id', $request->service_area_id)
            ->where('country_id', $request->country_id)
            ->where('status', 1)
            ->first();

        if (!$city) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['service_area_id' => __('Invalid city selection. City must be active and belong to the selected country.')]);
        }

        // Step 4: Check country is active
        $country = Country::where('id', $request->country_id)->where('status', 1)->first();
        if (!$country) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['country_id' => __('Selected country is not available.')]);
        }

        // Step 5: Check uniqueness (exclude current record)
        $exists = CourierServiceArea::where('courier_id', $this->courier->id)
            ->where('city_id', $city->id)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['service_area_id' => __('You already have a service area for this city.')]);
        }

        // Step 6: Update service area
        $service_area->city_id = $city->id;
        $service_area->price = $request->price / $this->curr->value;
        $service_area->service_radius_km = $request->service_radius_km;
        $service_area->latitude = $city->latitude;
        $service_area->longitude = $city->longitude;
        $service_area->save();

        return redirect()->route('courier-service-area')->with('success', __('Successfully updated your service area'));
    }

    public function serviceAreaDestroy($id)
    {
        $service_area = CourierServiceArea::where('courier_id', $this->courier->id)->where('id', $id)->first();
        $service_area->delete();
        $msg = __('Successfully deleted your service area');
        return back()->with('success', $msg);
    }

    /**
     * Toggle service area status (active/inactive)
     */
    public function serviceAreaToggleStatus($id)
    {
        $service_area = CourierServiceArea::where('courier_id', $this->courier->id)
            ->where('id', $id)
            ->firstOrFail();

        $service_area->status = $service_area->status == 1 ? 0 : 1;
        $service_area->save();

        $statusText = $service_area->status == 1 ? __('activated') : __('deactivated');
        return back()->with('success', __('Service area successfully') . ' ' . $statusText);
    }

    public function orders(Request $request)
    {
        if ($request->type == 'complete') {
            // ✅ Completed/delivered orders
            $purchases = DeliveryCourier::where('courier_id', $this->courier->id)
                ->whereNotNull('purchase_id')
                ->with(['purchase', 'merchantLocation', 'merchant'])
                ->where('status', 'delivered')
                ->orderby('id', 'desc')
                ->paginate(10);
        } elseif ($request->type == 'pending') {
            // ✅ Orders waiting for merchant to mark ready (courier can see but not act)
            $purchases = DeliveryCourier::where('courier_id', $this->courier->id)
                ->whereNotNull('purchase_id')
                ->with(['purchase', 'merchantLocation', 'merchant'])
                ->where('status', 'pending')
                ->orderby('id', 'desc')
                ->paginate(10);
        } else {
            // ✅ Ready orders: ready_for_courier_collection (new) + accepted (in progress)
            // Only show orders that merchant has marked as ready
            $purchases = DeliveryCourier::where('courier_id', $this->courier->id)
                ->whereNotNull('purchase_id')
                ->with(['purchase', 'merchantLocation', 'merchant'])
                ->whereIn('status', ['ready_for_courier_collection', 'accepted'])
                ->orderby('id', 'desc')
                ->paginate(10);
        }

        return view('courier.orders', compact('purchases'));
    }

    public function orderDetails($id)
    {
        $data = DeliveryCourier::with(['purchase', 'merchantLocation', 'merchant'])
            ->where('courier_id', $this->courier->id)
            ->where('id', $id)
            ->whereNotNull('purchase_id')
            ->first();

        if (!$data) {
            return redirect()->route('courier-purchases')->with('unsuccess', __('Purchase not found'));
        }

        return view('courier.purchase_details', compact('data'));
    }

    public function orderAccept($id)
    {
        $data = DeliveryCourier::where('courier_id', $this->courier->id)->where('id', $id)->first();
        $data->status = 'accepted';
        $data->save();
        return back()->with('success', __('Successfully accepted this purchase'));
    }

    public function orderReject($id)
    {
        $data = DeliveryCourier::where('courier_id', $this->courier->id)->where('id', $id)->first();
        $data->status = 'rejected';
        $data->save();
        return back()->with('success', __('Successfully rejected this purchase'));
    }

    public function orderComplete($id)
    {
        $delivery = DeliveryCourier::where('courier_id', $this->courier->id)->where('id', $id)->first();

        if (!$delivery) {
            return back()->with('unsuccess', __('Delivery not found'));
        }

        $delivery->status = 'delivered';
        $delivery->delivered_at = now();
        $delivery->save();

        // Record COD collection if applicable
        if ($delivery->payment_method === 'cod' && $delivery->order_amount > 0) {
            $this->accountingService->recordCodCollection($id, $delivery->order_amount);
        }

        // Record delivery fee earned
        if ($delivery->delivery_fee > 0) {
            $this->accountingService->recordDeliveryFeeEarned($id);
        }

        return back()->with('success', __('Successfully Delivered this purchase'));
    }

    /**
     * View courier transactions
     */
    public function transactions(Request $request)
    {
        $query = CourierTransaction::where('courier_id', $this->courier->id)
            ->orderBy('created_at', 'desc');

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $transactions = $query->paginate(20);
        $currency = Currency::where('is_default', 1)->first();

        return view('courier.transactions', compact('transactions', 'currency'));
    }

    /**
     * View courier settlements
     */
    public function settlements()
    {
        $settlements = CourierSettlement::where('courier_id', $this->courier->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $currency = Currency::where('is_default', 1)->first();
        $settlementCalc = $this->accountingService->calculateSettlementAmount($this->courier->id);

        return view('courier.settlements', compact('settlements', 'currency', 'settlementCalc'));
    }

    /**
     * View financial summary/report
     */
    public function financialReport(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $report = $this->accountingService->getCourierReport($this->courier->id, $startDate, $endDate);
        $currency = Currency::where('is_default', 1)->first();

        return view('courier.financial_report', compact('report', 'currency', 'startDate', 'endDate'));
    }
}
