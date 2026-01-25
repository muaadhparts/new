<?php

namespace App\Http\Controllers\User;

use App\Domain\Accounting\Models\ReferralCommission;
use App\Domain\Commerce\Models\FavoriteSeller;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Merchant\Models\MerchantPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends UserBaseController
{

    public function index()
    {
        $user = $this->user;

        // ✅ حساب الإحصائيات في الـ Controller بدلاً من الـ View
        $dashboardStats = [
            'totalPurchases' => $user->purchases()->count(),
            'pendingPurchases' => $user->purchases()->where('status', 'pending')->count(),
        ];

        return view('user.dashboard', compact('user', 'dashboardStats'));
    }

    public function profile()
    {
        $user = $this->user;
        return view('user.profile', compact('user'));
    }

    public function profileupdate(Request $request)
    {
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
            $data['gateway'] = MerchantPayment::findOrFail($data['pay_id']);
        }
        return view('load.payment-user', $data);
    }

    public function favorite($id1, $id2)
    {
        $fav = new FavoriteSeller();
        $fav->user_id = $id1;
        $fav->merchant_id = $id2;
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
        $final_affilate_users =ReferralCommission::whereReferId(auth()->id())->get();
        return view('user.affilate.affilate-program', compact('user', 'final_affilate_users'));
    }

    /**
     * Show merchant application form
     */
    public function applyMerchant()
    {
        $user = $this->user;

        // إذا كان المستخدم تاجر بالفعل، وجهه إلى لوحة التاجر
        if ($user->is_merchant >= 1) {
            return redirect()->route('merchant.dashboard');
        }

        return view('user.apply-merchant', compact('user'));
    }

    /**
     * Submit merchant application
     */
    public function submitMerchantApplication(Request $request)
    {
        $user = $this->user;

        // إذا كان المستخدم تاجر بالفعل
        if ($user->is_merchant >= 1) {
            return redirect()->route('merchant.dashboard');
        }

        $request->validate([
            'shop_name' => 'required|unique:users,shop_name',
            'shop_number' => 'nullable|max:10',
            'shop_address' => 'required',
        ], [
            'shop_name.required' => __('Shop name is required.'),
            'shop_name.unique' => __('This Shop Name has already been taken.'),
            'shop_number.max' => __('Shop Number Must Be Less Than 10 Digits.'),
            'shop_address.required' => __('Shop address is required.'),
        ]);

        // تحديث بيانات المستخدم ليصبح تاجر تحت التحقق
        $user->shop_name = $request->shop_name;
        $user->shop_number = $request->shop_number;
        $user->shop_address = $request->shop_address;
        $user->shop_message = $request->shop_message;
        $user->is_merchant = 1; // تحت التحقق
        $user->save();

        return redirect()->route('merchant.dashboard')->with('success', __('Your merchant application has been submitted. Please wait for admin verification.'));
    }

}
