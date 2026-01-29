<?php

namespace App\Http\Controllers\Courier;

use App\Domain\Shipping\Models\City;
use App\Domain\Shipping\Models\Country;
use App\Domain\Shipping\Models\DeliveryCourier;
use App\Domain\Shipping\Models\CourierServiceArea;
use App\Domain\Shipping\Services\CourierDashboardService;
use App\Domain\Shipping\Services\CourierDisplayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * CourierController
 *
 * DATA FLOW POLICY:
 * - Controller = Orchestration only
 * - All business logic in Services
 * - All formatting in DisplayServices
 * - All queries in Query classes or Services
 */
class CourierController extends CourierBaseController
{
    public function __construct(
        private CourierDashboardService $dashboardService,
        private CourierDisplayService $displayService,
    ) {
        parent::__construct();
    }

    /**
     * Courier dashboard
     */
    public function index()
    {
        $data = $this->dashboardService->getDashboardData($this->courier->id);
        
        // Add courier info
        $data['user'] = [
            'name' => $this->courier->name,
            'email' => $this->courier->email,
            'phone' => $this->courier->phone ?? '',
            'photo' => $this->courier->photo ?? asset('assets/images/noimage.png'),
            'balance' => $this->courier->balance ?? 0,
        ];

        return view('courier.dashbaord', $data);
    }

    /**
     * Show profile
     */
    public function profile()
    {
        return view('courier.profile', ['user' => $this->courier]);
    }

    /**
     * Update profile
     */
    public function profileupdate(Request $request)
    {
        $request->validate([
            'photo' => 'mimes:jpeg,jpg,png,svg',
            'email' => 'unique:users,email,' . $this->courier->id,
        ], [
            'photo.mimes' => __('The image must be a file of type: jpeg, jpg, png, svg.'),
        ]);

        $input = $request->all();
        
        if ($file = $request->file('photo')) {
            $extensions = ['jpeg', 'jpg', 'png', 'svg'];
            if (!in_array($file->getClientOriginalExtension(), $extensions)) {
                return back()->with('unsuccess', __('The image must be a file of type: jpeg, jpg, png, svg.'));
            }

            $name = \PriceHelper::ImageCreateName($file);
            $file->move('assets/images/users/', $name);
            
            if ($this->courier->photo != null) {
                if (file_exists(public_path() . '/assets/images/users/' . $this->courier->photo)) {
                    unlink(public_path() . '/assets/images/users/' . $this->courier->photo);
                }
            }
            
            $input['photo'] = $name;
        }
        
        $this->courier->update($input);

        return back()->with('success', __('Profile Updated Successfully!'));
    }

    /**
     * Show password reset form
     */
    public function resetform()
    {
        return view('courier.reset');
    }

    /**
     * Reset password
     */
    public function reset(Request $request)
    {
        if ($request->cpass) {
            if (Hash::check($request->cpass, $this->courier->password)) {
                if ($request->newpass == $request->renewpass) {
                    $input['password'] = Hash::make($request->newpass);
                } else {
                    return back()->with('unsuccess', __('Confirm password does not match.'));
                }
            } else {
                return back()->with('unsuccess', __('Current password Does not match.'));
            }
        }
        
        $this->courier->update($input);
        return back()->with('success', __('Password Updated Successfully!'));
    }

    /**
     * Show service areas
     */
    public function serviceArea()
    {
        $cities = City::whereStatus(1)->get();
        $service_areas = CourierServiceArea::where('courier_id', $this->courier->id)
            ->with(['city.country'])
            ->paginate(10);

        $serviceAreasDisplay = $this->displayService->formatServiceAreas($service_areas);

        return view('courier.service-area', [
            'service_area' => $serviceAreasDisplay,
            'cities' => $cities,
        ]);
    }

    /**
     * Show create service area form
     */
    public function serviceAreaCreate()
    {
        $countries = Country::whereStatus(1)->whereHas('cities', function($q) {
            $q->where('status', 1);
        })->get();
        
        return view('courier.add_service', ['countries' => $countries]);
    }

    /**
     * Get cities by country for AJAX
     */
    public function getCitiesByCountry(Request $request)
    {
        if (!$request->country_id) {
            return response()->json([
                'success' => false,
                'message' => 'Country ID is required',
                'cities' => '<option value="">' . __('Select Country First') . '</option>',
                'count' => 0
            ]);
        }

        $cities = City::where('country_id', $request->country_id)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        $options = '<option value="">' . __('Select City') . '</option>';
        foreach ($cities as $city) {
            $options .= '<option value="' . $city->id . '">' . htmlspecialchars($city->name) . '</option>';
        }

        return response()->json([
            'success' => true,
            'cities' => $options,
            'count' => $cities->count()
        ]);
    }

    /**
     * Store service area
     */
    public function serviceAreaStore(Request $request)
    {
        $request->validate([
            'country_id' => 'required|exists:countries,id',
            'service_area_id' => 'required|exists:cities,id',
            'price' => 'required|min:1|numeric',
            'service_radius_km' => 'required|integer|min:1|max:500',
        ]);

        // Verify city belongs to country and is active
        $city = City::where('id', $request->service_area_id)
            ->where('country_id', $request->country_id)
            ->where('status', 1)
            ->first();

        if (!$city) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['service_area_id' => __('Invalid city selection. City must be active and belong to the selected country.')]);
        }

        // Check country is active
        $country = Country::where('id', $request->country_id)->where('status', 1)->first();
        if (!$country) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['country_id' => __('Selected country is not available.')]);
        }

        // Check uniqueness
        $exists = CourierServiceArea::where('courier_id', $this->courier->id)
            ->where('city_id', $city->id)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['service_area_id' => __('You already have a service area for this city.')]);
        }

        // Create service area
        $service_area = new CourierServiceArea();
        $service_area->courier_id = $this->courier->id;
        $service_area->city_id = $city->id;
        $service_area->price = $request->price / $this->curr->value;
        $service_area->service_radius_km = $request->service_radius_km;
        $service_area->latitude = $city->latitude;
        $service_area->longitude = $city->longitude;
        $service_area->status = 1;
        $service_area->save();

        return redirect()->route('courier-service-area')->with('success', __('Successfully created your service area'));
    }

    /**
     * Show edit service area form
     */
    public function serviceAreaEdit($id)
    {
        $service_area = CourierServiceArea::findOrFail($id);
        $countries = Country::whereStatus(1)->whereHas('cities', function($q) {
            $q->where('status', 1);
        })->get();

        $currentCity = City::find($service_area->city_id);
        $selectedCountryId = $currentCity ? $currentCity->country_id : null;

        $cities = $selectedCountryId
            ? City::where('country_id', $selectedCountryId)->where('status', 1)->orderBy('name')->get()
            : collect();

        return view('courier.edit_service', [
            'countries' => $countries,
            'cities' => $cities,
            'service_area' => $service_area,
            'selectedCountryId' => $selectedCountryId,
        ]);
    }

    /**
     * Update service area
     */
    public function serviceAreaUpdate(Request $request, $id)
    {
        $service_area = CourierServiceArea::where('id', $id)
            ->where('courier_id', $this->courier->id)
            ->firstOrFail();

        $request->validate([
            'country_id' => 'required|exists:countries,id',
            'service_area_id' => 'required|exists:cities,id',
            'price' => 'required|min:1|numeric',
            'service_radius_km' => 'required|integer|min:1|max:500',
        ]);

        // Verify city belongs to country and is active
        $city = City::where('id', $request->service_area_id)
            ->where('country_id', $request->country_id)
            ->where('status', 1)
            ->first();

        if (!$city) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['service_area_id' => __('Invalid city selection. City must be active and belong to the selected country.')]);
        }

        // Check country is active
        $country = Country::where('id', $request->country_id)->where('status', 1)->first();
        if (!$country) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['country_id' => __('Selected country is not available.')]);
        }

        // Check uniqueness (exclude current record)
        $exists = CourierServiceArea::where('courier_id', $this->courier->id)
            ->where('city_id', $city->id)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['service_area_id' => __('You already have a service area for this city.')]);
        }

        // Update service area
        $service_area->city_id = $city->id;
        $service_area->price = $request->price / $this->curr->value;
        $service_area->service_radius_km = $request->service_radius_km;
        $service_area->latitude = $city->latitude;
        $service_area->longitude = $city->longitude;
        $service_area->save();

        return redirect()->route('courier-service-area')->with('success', __('Successfully updated your service area'));
    }

    /**
     * Delete service area
     */
    public function serviceAreaDestroy($id)
    {
        $service_area = CourierServiceArea::where('courier_id', $this->courier->id)
            ->where('id', $id)
            ->first();
        
        $service_area->delete();
        
        return back()->with('success', __('Successfully deleted your service area'));
    }

    /**
     * Toggle service area status
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

    /**
     * Show orders page
     */
    public function orders(Request $request)
    {
        $type = $request->type;
        
        $tabCounts = $this->dashboardService->getOrdersTabCounts($this->courier->id);
        $purchases = $this->dashboardService->getOrders($this->courier->id, $type);
        $purchasesDisplay = $this->displayService->formatDeliveriesForOrders($purchases);

        return view('courier.orders', [
            'purchases' => $purchasesDisplay,
            'type' => $type,
            'tabCounts' => $tabCounts,
        ]);
    }

    /**
     * Show order details
     */
    public function orderDetails($id)
    {
        $data = DeliveryCourier::with(['purchase.merchantPurchases', 'merchantBranch', 'merchant'])
            ->where('courier_id', $this->courier->id)
            ->where('id', $id)
            ->whereNotNull('purchase_id')
            ->whereHas('purchase')
            ->first();

        if (!$data) {
            return redirect()->route('courier-purchases')->with('unsuccess', __('Purchase not found'));
        }

        $purchase = $data->purchase;
        $deliveryDto = $this->displayService->buildDeliveryDto($data);
        $deliveryDetails = $this->displayService->formatDeliveryDetails($data);
        $cartItems = $purchase->getCartItems();
        $cartItemsDisplay = $this->displayService->formatCartItemsForDelivery($cartItems, $data->merchant_id);

        return view('courier.purchase_details', [
            'data' => $data,
            'purchase' => $purchase,
            'deliveryDto' => $deliveryDto,
            'deliveryDetails' => $deliveryDetails,
            'cartItems' => $cartItemsDisplay,
        ]);
    }

    /**
     * Accept delivery request
     */
    public function orderAccept($id)
    {
        $data = DeliveryCourier::where('courier_id', $this->courier->id)->where('id', $id)->first();

        if (!$data) {
            return back()->with('unsuccess', __('Delivery not found'));
        }

        if (!$data->canTransitionTo(DeliveryCourier::STATUS_APPROVED)) {
            return back()->with('unsuccess', __('Cannot approve this delivery. Current status: ') . $data->status_label);
        }

        $data->approve();
        return back()->with('success', __('Delivery approved successfully! Waiting for merchant to prepare the order.'));
    }

    /**
     * Reject delivery request
     */
    public function orderReject(Request $request, $id)
    {
        $data = DeliveryCourier::where('courier_id', $this->courier->id)->where('id', $id)->first();

        if (!$data) {
            return back()->with('unsuccess', __('Delivery not found'));
        }

        if (!$data->canTransitionTo(DeliveryCourier::STATUS_REJECTED)) {
            return back()->with('unsuccess', __('Cannot reject this delivery. Current status: ') . $data->status_label);
        }

        $reason = $request->input('reason', null);
        $data->reject($reason);
        return back()->with('success', __('Delivery rejected successfully.'));
    }

    /**
     * Mark order as delivered
     */
    public function orderComplete($id)
    {
        $delivery = DeliveryCourier::where('courier_id', $this->courier->id)->where('id', $id)->first();

        if (!$delivery) {
            return back()->with('unsuccess', __('Delivery not found'));
        }

        if (!$delivery->canTransitionTo(DeliveryCourier::STATUS_DELIVERED)) {
            return back()->with('unsuccess', __('Cannot complete this delivery. Current status: ') . $delivery->status_label);
        }

        $delivery->markAsDelivered();

        return back()->with('success', __('Order delivered successfully to customer!'));
    }

    /**
     * Show transactions
     */
    public function transactions(Request $request)
    {
        $data = $this->dashboardService->getTransactionsData(
            $this->courier->id,
            $request->status
        );

        return view('courier.transactions', $data);
    }

    /**
     * Show settlements
     */
    public function settlements()
    {
        $data = $this->dashboardService->getSettlementsData($this->courier->id);
        return view('courier.settlements', $data);
    }

    /**
     * Show financial report
     */
    public function financialReport(Request $request)
    {
        $data = $this->dashboardService->getFinancialReportData(
            $this->courier->id,
            $request->start_date,
            $request->end_date
        );

        return view('courier.financial_report', $data);
    }
}
