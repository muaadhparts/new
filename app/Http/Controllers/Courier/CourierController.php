<?php

namespace App\Http\Controllers\Courier;

use App\Domain\Shipping\Models\City;
use App\Domain\Shipping\Models\Country;
use App\Domain\Platform\Models\MonetaryUnit;
use App\Domain\Shipping\Models\DeliveryCourier;
use App\Domain\Shipping\Models\CourierServiceArea;
use App\Domain\Accounting\Services\CourierAccountingService;
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
            ->whereHas('purchase') // Ensure the purchase still exists
            ->with(['purchase.merchantPurchases', 'merchantBranch'])
            ->orderby('id', 'desc')->take(8)->get();

        // PRE-COMPUTED: Total amount for each delivery (DATA_FLOW_POLICY - no @php in view)
        $purchases->transform(function ($purchase) {
            $purchase->display_total_amount = (float)($purchase->purchase_amount ?? 0);
            return $purchase;
        });

        // Get accounting report
        $report = $this->accountingService->getCourierReport($this->courier->id);
        $currency = monetaryUnit()->getDefault();

        return view('courier.dashbaord', [
            'purchases' => $purchases,
            'user' => $user,
            'report' => $report,
            'currency' => $currency,
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
        $service_area = CourierServiceArea::where('courier_id', $courier->id)->paginate(10);
        return view('courier.service-area', [
            'service_area' => $service_area,
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

        return view('courier.orders', [
            'purchases' => $purchases,
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

        // PRE-COMPUTED: Purchase from relationship (DATA_FLOW_POLICY - no @php in view)
        $purchase = $data->purchase;

        // PRE-COMPUTED: Delivery DTO for workflow display (DATA_FLOW_POLICY)
        $deliveryDto = $this->buildDeliveryDto($data);

        return view('courier.purchase_details', [
            'data' => $data,
            'purchase' => $purchase,
            'deliveryDto' => $deliveryDto,
        ]);
    }

    /**
     * Build delivery DTO for workflow display (DATA_FLOW_POLICY)
     */
    private function buildDeliveryDto(DeliveryCourier $delivery): array
    {
        $nextAction = $delivery->next_action ?? ['actor' => 'none', 'action' => ''];
        $step = $delivery->workflow_step ?? 1;

        return [
            'isRejected' => $delivery->isRejected(),
            'rejectionReason' => $delivery->rejection_reason ?? null,
            'workflowStep' => $step,
            'progressPercent' => $this->calculateWorkflowProgress($step),
            'stepsDisplay' => $this->buildWorkflowStepsDisplay($step),
            'approvedAt' => $delivery->approved_at?->format('d/m H:i'),
            'readyAt' => $delivery->ready_at?->format('d/m H:i'),
            'pickedUpAt' => $delivery->picked_up_at?->format('d/m H:i'),
            'deliveredAtShort' => $delivery->delivered_at?->format('d/m H:i'),
            'confirmedAtShort' => $delivery->confirmed_at?->format('d/m H:i'),
            'isCod' => $delivery->isCod(),
            'codAmount' => (float)($delivery->cod_amount ?? $delivery->purchase_amount ?? 0),
            'hasNextAction' => ($nextAction['actor'] ?? 'none') !== 'none',
            'nextActionActor' => $nextAction['actor'] ?? 'none',
            'nextActionText' => $nextAction['action'] ?? '',
        ];
    }

    /**
     * Calculate workflow progress percent (DATA_FLOW_POLICY)
     */
    private function calculateWorkflowProgress(int $step): int
    {
        return match (true) {
            $step >= 6 => 100,
            $step >= 5 => 80,
            $step >= 4 => 60,
            $step >= 3 => 40,
            $step >= 2 => 20,
            default => 0,
        };
    }

    /**
     * Build workflow steps display array (DATA_FLOW_POLICY)
     */
    private function buildWorkflowStepsDisplay(int $currentStep): array
    {
        $stepDefinitions = [
            ['key' => 'pending_approval', 'label' => __('Approval'), 'icon' => 'fa-clock', 'description' => __('Courier Approval'), 'step' => 1],
            ['key' => 'approved', 'label' => __('Preparing'), 'icon' => 'fa-box-open', 'description' => __('Merchant Preparing'), 'step' => 2],
            ['key' => 'ready_for_pickup', 'label' => __('Ready'), 'icon' => 'fa-box', 'description' => __('Ready for Pickup'), 'step' => 3],
            ['key' => 'picked_up', 'label' => __('Picked Up'), 'icon' => 'fa-handshake', 'description' => __('Courier Picked Up'), 'step' => 4],
            ['key' => 'delivered', 'label' => __('Delivered'), 'icon' => 'fa-truck', 'description' => __('Delivered to Customer'), 'step' => 5],
            ['key' => 'confirmed', 'label' => __('Confirmed'), 'icon' => 'fa-check-double', 'description' => __('Customer Confirmed'), 'step' => 6],
        ];

        $result = [];
        foreach ($stepDefinitions as $s) {
            $isActive = $currentStep >= $s['step'];
            $isCurrent = $currentStep == $s['step'];

            $result[] = [
                'key' => $s['key'],
                'label' => $s['label'],
                'icon' => $s['icon'],
                'description' => $s['description'],
                'step' => $s['step'],
                'isActive' => $isActive,
                'isCurrent' => $isCurrent,
                'circleBackground' => $isCurrent ? 'var(--action-primary, #3b82f6)' : ($isActive ? 'var(--action-success, #22c55e)' : 'var(--surface-secondary, #f3f4f6)'),
                'circleColor' => $isActive ? '#fff' : 'var(--text-tertiary, #9ca3af)',
                'circleBorder' => $isCurrent ? 'var(--action-primary, #3b82f6)' : ($isActive ? 'var(--action-success, #22c55e)' : 'var(--border-default, #e5e7eb)'),
                'labelColor' => $isActive ? 'var(--text-primary, #111827)' : 'var(--text-tertiary, #9ca3af)',
            ];
        }

        return $result;
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
            ->orderBy('created_at', 'desc');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $deliveries = $query->paginate(20);
        $currency = monetaryUnit()->getDefault();
        $report = $this->accountingService->getCourierReport($this->courier->id);

        return view('courier.transactions', [
            'deliveries' => $deliveries,
            'currency' => $currency,
            'report' => $report,
        ]);
    }

    /**
     * View courier settlements / accounting summary
     */
    public function settlements()
    {
        $currency = monetaryUnit()->getDefault();
        $settlementCalc = $this->accountingService->calculateSettlementAmount($this->courier->id);
        $unsettledDeliveries = $this->accountingService->getUnsettledDeliveriesForCourier($this->courier->id);
        $report = $this->accountingService->getCourierReport($this->courier->id);

        // PRE-COMPUTED: Net amount for display (DATA_FLOW_POLICY - no @php in view)
        $netAmount = $settlementCalc['net_amount'] ?? 0;

        return view('courier.settlements', [
            'currency' => $currency,
            'settlementCalc' => $settlementCalc,
            'unsettledDeliveries' => $unsettledDeliveries,
            'report' => $report,
            'netAmount' => $netAmount,
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
        $currency = monetaryUnit()->getDefault();

        return view('courier.financial_report', [
            'report' => $report,
            'currency' => $currency,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
