<?php

namespace App\Http\Controllers\User;

use App\Models\AffliateBonus;
use App\Models\FavoriteSeller;
use App\Models\Order;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends UserBaseController
{

    public function index()
    {
        $user = $this->user;
        return view('user.dashboard', compact('user'));
    }

    public function profile()
    {
        $user = $this->user;

        // Log current user data for debugging
        \Log::info('User Profile Page Loaded:', [
            'user_id' => $user->id,
            'country' => $user->country,
            'state_id' => $user->state_id,
            'city_id' => $user->city_id,
            'address' => $user->address,
            'latitude' => $user->latitude,
            'longitude' => $user->longitude,
        ]);

        return view('user.profile', compact('user'));
    }

    public function profileupdate(Request $request)
    {
        // Log received data for debugging
        \Log::info('User Profile Update - Received Data:', [
            'country' => $request->country,
            'state_id' => $request->state_id,
            'city_id' => $request->city_id,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        $rules =
            [
            'photo' => 'mimes:jpeg,jpg,png,svg',
            'email' => 'unique:users,email,' . $this->user->id,
        ];

        $customs = [
            'photo.mimes' => __('The image must be a file of type: jpeg, jpg, png, svg.'),
        ];

        $request->validate($rules, $customs);

        //--- Validation Section Ends
        $input = $request->all();
        $data = $this->user;
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

        // Log saved data for verification
        \Log::info('User Profile Update - Data Saved Successfully:', [
            'user_id' => $data->id,
            'country' => $data->country,
            'state_id' => $data->state_id,
            'city_id' => $data->city_id,
            'address' => $data->address,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
        ]);

        return redirect()->route('user-profile')->with('success', __('Profile Updated Successfully!'));
    }

    public function pricingPlans()
    {
        return view('user.pricing-plans');
    }

    public function resetform()
    {
        return view('user.reset');
    }

    public function reset(Request $request)
    {
        $user = $this->user;
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

    public function loadpayment($slug1, $slug2)
    {
        $data['payment'] = $slug1;
        $data['pay_id'] = $slug2;
        $data['gateway'] = '';
        if ($data['pay_id'] != 0) {
            $data['gateway'] = PaymentGateway::findOrFail($data['pay_id']);
        }
        return view('load.payment-user', $data);
    }

    public function favorite($id1, $id2)
    {
        $fav = new FavoriteSeller();
        $fav->user_id = $id1;
        $fav->vendor_id = $id2;
        $fav->save();
        $data['icon'] = '<i class="fas fa-check"></i>';
        $data['text'] = __('Favorite');
        return response()->json($data);
    }

    public function favorites()
    {
        $user = $this->user;
        $favorites = FavoriteSeller::where('user_id', '=', $user->id)->paginate(12);
        return view('user.favorite', compact('user', 'favorites'));
    }

    public function favdelete($id)
    {
        $wish = FavoriteSeller::findOrFail($id);
        $wish->delete();
        return redirect()->route('user-favorites')->with('success', __('Successfully Removed The Seller.'));
    }

    public function affilate_code()
    {
        $user = $this->user;
        $final_affilate_users =AffliateBonus::whereReferId(auth()->id())->get();
        return view('user.affilate.affilate-program', compact('user', 'final_affilate_users'));
    }

}
