<?php

namespace App\Http\Controllers\Rider;

use App\Models\City;
use App\Models\DeliveryRider;
use App\Models\RiderServiceArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RiderController extends RiderBaseController
{

    public function index()
    {
        $user = $this->rider;
        $orders = DeliveryRider::where('rider_id', $this->rider->id)
            ->orderby('id', 'desc')->take(8)->get();
        return view('rider.dashbaord', compact('orders', 'user'));
    }

    public function profile()
    {
        $user = $this->rider;
        return view('rider.profile', compact('user'));
    }

    public function profileupdate(Request $request)
    {

        $rules =
            [
            'photo' => 'mimes:jpeg,jpg,png,svg',
            'email' => 'unique:users,email,' . $this->rider->id,
        ];

        $customs = [
            'photo.mimes' => __('The image must be a file of type: jpeg, jpg, png, svg.'),
        ];

        $request->validate($rules, $customs);

        //--- Validation Section Ends
        $input = $request->all();
        $data = $this->rider;
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
        return view('rider.reset');
    }

    public function reset(Request $request)
    {
        $user = $this->rider;
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
        $rider = $this->rider;
        $service_area = RiderServiceArea::where('rider_id', $rider->id)->get();
        return view('rider.service-area', compact('service_area', 'cities'));
    }

    public function serviceAreaCreate()
    {
        $cities = City::whereStatus(1)->get();
        return view('rider.add_service', compact('cities'));
    }

    public function serviceAreaStore(Request $request)
    {
        $request->validate(
            [
                'service_area_id' => 'required|unique:rider_service_areas,city_id,NULL,id,rider_id,' . $this->rider->id . '|exists:cities,id',
                'price' => 'required|min:1|numeric',
            ]
        );

        $service_area = new RiderServiceArea();
        $service_area->rider_id = $this->rider->id;
        $service_area->city_id = $request->service_area_id;
        $service_area->price = $request->price / $this->curr->value;
        $service_area->save();

        $msg = __('Successfully created your service area');
        return redirect()->route('rider-service-area')->with('success', $msg);
    }

    public function serviceAreaEdit($id)
    {
        $cities = City::whereStatus(1)->get();
        $service_area = RiderServiceArea::findOrFail($id);
        return view('rider.edit_service', compact('cities', 'service_area'));
    }

    public function serviceAreaUpdate(Request $request, $id)
    {
        $request->validate(
            [
                'service_area_id' => 'required|unique:rider_service_areas,city_id,' . $id . ',id,rider_id,' . $this->rider->id . '|exists:cities,id',
                'price' => 'required|min:1|numeric',
            ]
        );

        $service_area = RiderServiceArea::findOrFail($id);
        $service_area->rider_id = $this->rider->id;
        $service_area->city_id = $request->service_area_id;
        $service_area->price = $request->price / $this->curr->value;
        $service_area->save();

        $msg = __('Successfully updated your service area');
        return redirect()->route('rider-service-area')->with('success', $msg);
    }

    public function serviceAreaDestroy($id)
    {
        $service_area = RiderServiceArea::where('rider_id', $this->rider->id)->where('id', $id)->first();
        $service_area->delete();
        $msg = __('Successfully deleted your service area');
        return back()->with('success', $msg);
    }

    public function orders(Request $request)
    {
        if ($request->type == 'complete') {
            $orders = DeliveryRider::where('rider_id', $this->rider->id)
                ->where('status', 'delivered')->orderby('id', 'desc')->paginate(10);
            return view('rider.orders', compact('orders'));
        } else {
            $orders = DeliveryRider::where('rider_id', $this->rider->id)
                ->where('status', '!=', 'delivered')->orderby('id', 'desc')->paginate(10);
            return view('rider.orders', compact('orders'));
        }
    }

    public function orderDetails($id)
    {
        $data = DeliveryRider::with('order')->where('rider_id', $this->rider->id)->where('id', $id)->first();
        return view('rider.order_details', compact('data'));
    }

    public function orderAccept($id)
    {
        $data = DeliveryRider::where('rider_id', $this->rider->id)->where('id', $id)->first();
        $data->status = 'accepted';
        $data->save();
        return back()->with('success', __('Successfully accepted this order'));
    }

    public function orderReject($id)
    {
        $data = DeliveryRider::where('rider_id', $this->rider->id)->where('id', $id)->first();
        $data->status = 'rejected';
        $data->save();
        return back()->with('success', __('Successfully rejected this order'));
    }

    public function orderComplete($id)
    {
        $data = DeliveryRider::where('rider_id', $this->rider->id)->where('id', $id)->first();
        $data->status = 'delivered';
        $data->save();
        return back()->with('success', __('Successfully Delivered this order'));
    }
}
