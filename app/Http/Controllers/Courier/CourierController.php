<?php

namespace App\Http\Controllers\Courier;

use App\Models\City;
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
            ->with(['purchase', 'pickup'])
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
        $cities = City::whereStatus(1)->get();
        return view('courier.add_service', compact('cities'));
    }

    public function serviceAreaStore(Request $request)
    {
        $request->validate(
            [
                'service_area_id' => 'required|unique:courier_service_areas,city_id,NULL,id,courier_id,' . $this->courier->id . '|exists:cities,id',
                'price' => 'required|min:1|numeric',
            ]
        );

        $service_area = new CourierServiceArea();
        $service_area->courier_id = $this->courier->id;
        $service_area->city_id = $request->service_area_id;
        $service_area->price = $request->price / $this->curr->value;
        $service_area->save();

        $msg = __('Successfully created your service area');
        return redirect()->route('courier-service-area')->with('success', $msg);
    }

    public function serviceAreaEdit($id)
    {
        $cities = City::whereStatus(1)->get();
        $service_area = CourierServiceArea::findOrFail($id);
        return view('courier.edit_service', compact('cities', 'service_area'));
    }

    public function serviceAreaUpdate(Request $request, $id)
    {
        $request->validate(
            [
                'service_area_id' => 'required|unique:courier_service_areas,city_id,' . $id . ',id,courier_id,' . $this->courier->id . '|exists:cities,id',
                'price' => 'required|min:1|numeric',
            ]
        );

        $service_area = CourierServiceArea::findOrFail($id);
        $service_area->courier_id = $this->courier->id;
        $service_area->city_id = $request->service_area_id;
        $service_area->price = $request->price / $this->curr->value;
        $service_area->save();

        $msg = __('Successfully updated your service area');
        return redirect()->route('courier-service-area')->with('success', $msg);
    }

    public function serviceAreaDestroy($id)
    {
        $service_area = CourierServiceArea::where('courier_id', $this->courier->id)->where('id', $id)->first();
        $service_area->delete();
        $msg = __('Successfully deleted your service area');
        return back()->with('success', $msg);
    }

    public function orders(Request $request)
    {
        if ($request->type == 'complete') {
            // ✅ Completed/delivered orders
            $purchases = DeliveryCourier::where('courier_id', $this->courier->id)
                ->whereNotNull('purchase_id')
                ->with(['purchase', 'pickup', 'merchant'])
                ->where('status', 'delivered')
                ->orderby('id', 'desc')
                ->paginate(10);
        } elseif ($request->type == 'pending') {
            // ✅ Orders waiting for merchant to mark ready (courier can see but not act)
            $purchases = DeliveryCourier::where('courier_id', $this->courier->id)
                ->whereNotNull('purchase_id')
                ->with(['purchase', 'pickup', 'merchant'])
                ->where('status', 'pending')
                ->orderby('id', 'desc')
                ->paginate(10);
        } else {
            // ✅ Ready orders: ready_for_pickup (new) + accepted (in progress)
            // Only show orders that merchant has marked as ready
            $purchases = DeliveryCourier::where('courier_id', $this->courier->id)
                ->whereNotNull('purchase_id')
                ->with(['purchase', 'pickup', 'merchant'])
                ->whereIn('status', ['ready_for_pickup', 'accepted'])
                ->orderby('id', 'desc')
                ->paginate(10);
        }

        return view('courier.orders', compact('purchases'));
    }

    public function orderDetails($id)
    {
        $data = DeliveryCourier::with(['purchase', 'pickup', 'merchant'])
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
