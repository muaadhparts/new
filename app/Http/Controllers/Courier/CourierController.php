<?php

namespace App\Http\Controllers\Courier;

use App\Domain\Shipping\Models\City;
use App\Domain\Shipping\Models\Country;
use App\Domain\Platform\Models\MonetaryUnit;
use App\Domain\Shipping\Models\DeliveryCourier;
use App\Domain\Shipping\Models\CourierServiceArea;
use App\Domain\Accounting\Services\CourierAccountingService;
use App\Domain\Shipping\Services\CourierDisplayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * CourierController
 *
 * DATA FLOW POLICY:
 * - Controller = Orchestration only
 * - All formatting in CourierDisplayService (API-ready)
 */
class CourierController extends CourierBaseController
{
    protected CourierAccountingService $accountingService;
    protected CourierDisplayService $displayService;

    public function __construct()
    {
        parent::__construct();
        $this->accountingService = app(CourierAccountingService::class);
        $this->displayService = app(CourierDisplayService::class);
    }

    public function index()
    {
        $deliveries = DeliveryCourier::where('courier_id', $this->courier->id)
            ->whereNotNull('purchase_id')
            ->whereHas('purchase')
            ->with(['purchase', 'merchantBranch'])
            ->orderby('id', 'desc')->take(8)->get();

        // Get accounting report
        $report = $this->accountingService->getCourierReport($this->courier->id);

        // Format using DisplayService (API-ready)
        $purchasesDisplay = $this->displayService->formatDeliveriesForDashboard($deliveries);
        $reportDisplay = $this->displayService->formatReportForDashboard($report);
        
        // Format courier info for display
        $courierDisplay = [
            'name' => $this->courier->name,
            'email' => $this->courier->email,
            'phone' => $this->courier->phone ?? '',
            'photo' => $this->courier->photo ?? asset('assets/images/noimage.png'),
            'balance' => $this->courier->balance ?? 0,
        ];

        return view('courier.dashbaord', [
            'purchases' => $purchasesDisplay,
            'user' => $courierDisplay,
            'report' => $reportDisplay,
        ]);
    }

    public function profile()
    {
        $user = $this->courier;
        return view('courier.profile', [
            'user' => $user,
        ]);
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
        $service_areas = CourierServiceArea::where('courier_id', $courier->id)
            ->with(['city.country'])
            ->paginate(10);

        // Format using DisplayService (API-ready)
        $serviceAreasDisplay = $this->displayService->formatServiceAreas($service_areas);

        return view('courier.service-area', [
            'service_area' => $serviceAreasDisplay,
            'cities' => $cities,
        ]);
    }

    public function serviceAreaCreate()
    {
        $countries = Country::whereStatus(1)->whereHas('cities', function($q) {
            $q->where('status', 1);
        })->get();
        return view('courier.add_service', [
            'countries' => $countries,
        ]);
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
            ->orderBy('name')
            ->get(['id', 'name']);

        // Build options HTML
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
            ? City::where('country_id', $selectedCountryId)->where('status', 1)->orderBy('name')->get()
            : collect();

        return view('courier.edit_service', [
            'countries' => $countries,
            'cities' => $cities,
            'service_area' => $service_area,
            'selectedCountryId' => $selectedCountryId,
        ]);
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
        $type = $request->type;
        $courierId = $this->courier->id;

        // ✅ حساب counts للـ tabs في الـ Controller بدلاً من الـ View
        // هذا يتبع مبدأ "لا استعلامات في العروض"
        $tabCounts = [
            'active' => DeliveryCourier::where('courier_id', $courierId)
                ->whereIn('status', [
                    DeliveryCourier::STATUS_PENDING_APPROVAL,
                    DeliveryCourier::STATUS_APPROVED,
                    DeliveryCourier::STATUS_READY_FOR_PICKUP,
                    DeliveryCourier::STATUS_PICKED_UP,
                ])
                ->count(),
            'pending' => DeliveryCourier::where('courier_id', $courierId)
                ->where('status', DeliveryCourier::STATUS_PENDING_APPROVAL)
                ->count(),
            'in_progress' => DeliveryCourier::where('courier_id', $courierId)
                ->whereIn('status', [
                    DeliveryCourier::STATUS_APPROVED,
                    DeliveryCourier::STATUS_READY_FOR_PICKUP,
                    DeliveryCourier::STATUS_PICKED_UP,
                ])
                ->count(),
        ];

        if ($type == 'completed') {
            // Completed/delivered orders
            $purchases = DeliveryCourier::where('courier_id', $courierId)
                ->whereNotNull('purchase_id')
                ->whereHas('purchase')
                ->with(['purchase.merchantPurchases', 'merchantBranch', 'merchant'])
                ->whereIn('status', [DeliveryCourier::STATUS_DELIVERED, DeliveryCourier::STATUS_CONFIRMED])
                ->orderby('id', 'desc')
                ->paginate(10);
        } elseif ($type == 'pending') {
            // Orders waiting for courier approval
            $purchases = DeliveryCourier::where('courier_id', $courierId)
                ->whereNotNull('purchase_id')
                ->whereHas('purchase')
                ->with(['purchase.merchantPurchases', 'merchantBranch', 'merchant'])
                ->where('status', DeliveryCourier::STATUS_PENDING_APPROVAL)
                ->orderby('id', 'desc')
                ->paginate(10);
        } elseif ($type == 'in_progress') {
            // Orders in progress (approved, ready, picked up)
            $purchases = DeliveryCourier::where('courier_id', $courierId)
                ->whereNotNull('purchase_id')
                ->whereHas('purchase')
                ->with(['purchase.merchantPurchases', 'merchantBranch', 'merchant'])
                ->whereIn('status', [
                    DeliveryCourier::STATUS_APPROVED,
                    DeliveryCourier::STATUS_READY_FOR_PICKUP,
                    DeliveryCourier::STATUS_PICKED_UP,
                ])
                ->orderby('id', 'desc')
                ->paginate(10);
        } else {
            // Default: All active orders (pending approval + in progress)
            $purchases = DeliveryCourier::where('courier_id', $courierId)
                ->whereNotNull('purchase_id')
                ->whereHas('purchase')
                ->with(['purchase.merchantPurchases', 'merchantBranch', 'merchant'])
                ->whereIn('status', [
                    DeliveryCourier::STATUS_PENDING_APPROVAL,
                    DeliveryCourier::STATUS_APPROVED,
                    DeliveryCourier::STATUS_READY_FOR_PICKUP,
                    DeliveryCourier::STATUS_PICKED_UP,
                ])
                ->orderby('id', 'desc')
                ->paginate(10);
        }

        // Format using DisplayService (API-ready)
        $purchasesDisplay = $this->displayService->formatDeliveriesForOrders($purchases);

        return view('courier.orders', [
            'purchases' => $purchasesDisplay,
            'type' => $type,
            'tabCounts' => $tabCounts,
        ]);
    }

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

        // Format using DisplayService (API-ready)
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
     * Courier approves the delivery request
     * STEP 1: pending_approval -> approved
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
     * Courier rejects the delivery request
     * STEP 1 ALT: pending_approval -> rejected
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
     * Courier marks the order as delivered to customer
     * STEP 4: picked_up -> delivered
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

        // Use model method which handles status transition and financial transactions
        $delivery->markAsDelivered();

        return back()->with('success', __('Order delivered successfully to customer!'));
    }

    /**
     * View courier deliveries history (transactions)
     */
    public function transactions(Request $request)
    {
        $query = DeliveryCourier::where('courier_id', $this->courier->id)
            ->with('purchase')
            ->orderBy('created_at', 'desc');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $deliveries = $query->paginate(20);
        $report = $this->accountingService->getCourierReport($this->courier->id);

        // Format using DisplayService (API-ready)
        $deliveriesDisplay = $this->displayService->formatDeliveriesForTransactions($deliveries);
        $reportDisplay = $this->displayService->formatReportForTransactions($report);

        return view('courier.transactions', [
            'deliveries' => $deliveriesDisplay,
            'report' => $reportDisplay,
        ]);
    }

    /**
     * View courier settlements / accounting summary
     */
    public function settlements()
    {
        $settlementCalc = $this->accountingService->calculateSettlementAmount($this->courier->id);
        $unsettledDeliveries = $this->accountingService->getUnsettledDeliveriesForCourier($this->courier->id);
        $report = $this->accountingService->getCourierReport($this->courier->id);

        // Format using DisplayService (API-ready)
        $reportDisplay = $this->displayService->formatReportForSettlements($report);
        $settlementDisplay = $this->displayService->formatSettlementCalc($settlementCalc);
        $deliveriesDisplay = $this->displayService->formatUnsettledDeliveries(collect($unsettledDeliveries));

        return view('courier.settlements', [
            'settlementCalc' => $settlementDisplay,
            'unsettledDeliveries' => $deliveriesDisplay,
            'report' => $reportDisplay,
        ]);
    }

    /**
     * View financial summary/report
     */
    public function financialReport(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $report = $this->accountingService->getCourierReport($this->courier->id, $startDate, $endDate);

        // Format using DisplayService (API-ready)
        $reportDisplay = $this->displayService->formatFinancialReport($report);

        return view('courier.financial_report', [
            'report' => $reportDisplay,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
