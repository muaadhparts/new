<?php

namespace App\Http\Controllers\User;

use App\Domain\Accounting\Models\ReferralCommission;
use App\Domain\Commerce\Models\FavoriteSeller;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Identity\DTOs\UserProfileDTO;
use App\Domain\Identity\Services\UserDashboardBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends UserBaseController
{

    public function index(UserDashboardBuilder $dashboardBuilder)
    {
        // DATA_FLOW_POLICY: Build DTO in Controller, pass only DTO to View
        $dashboard = $dashboardBuilder->build($this->user);

        return view('user.dashboard', [
            'dashboard' => $dashboard,
        ]);
    }

    public function profile()
    {
        // DATA_FLOW_POLICY: Build DTO from User model
        $profile = UserProfileDTO::fromUser($this->user);

        return view('user.profile', ['profile' => $profile]);
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
        $favorites = FavoriteSeller::where('user_id', '=', $this->user->id)
            ->with(['catalogItem', 'merchantItem', 'effective_merchant_item'])
            ->paginate(12);

        // PRE-COMPUTED: Display data for each favorite item (DATA_FLOW_POLICY - no @php in view)
        $favoritesDisplay = [];
        foreach ($favorites as $favoriteItem) {
            $favoritesDisplay[$favoriteItem->id] = [
                'catalogItem' => $favoriteItem->catalogItem,
                'mp' => $favoriteItem->effective_merchant_item ?? $favoriteItem->merchantItem,
            ];
        }

        return view('user.favorite', [
            'favorites' => $favorites,
            'favoritesDisplay' => $favoritesDisplay,
        ]);
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
        $referralCommissions = ReferralCommission::whereReferId(auth()->id())->get();

        // PRE-COMPUTED: Affiliate data (DATA_FLOW_POLICY - no PriceHelper in view)
        $curr = monetaryUnit()->getCurrent();
        $affiliateData = [
            'affiliate_link' => url('/') . '/?reff=' . $user->affilate_code,
            'affiliate_code' => $user->affilate_code,
        ];

        // Pre-compute referral display data
        $referralsDisplay = $referralCommissions->map(function ($commission) use ($curr) {
            return [
                'customer_email' => $commission->customer_email,
                'bonus_formatted' => \PriceHelper::showCurrencyPrice($commission->bonus * $curr->value),
                'created_at_formatted' => $commission->created_at?->format('d-m-Y') ?? 'N/A',
            ];
        })->toArray();

        return view('user.affilate.affilate-program', [
            'affiliateData' => $affiliateData,
            'referralsDisplay' => $referralsDisplay,
        ]);
    }

    /**
     * Show merchant application form
     */
    public function applyMerchant()
    {
        // إذا كان المستخدم تاجر بالفعل، وجهه إلى لوحة التاجر
        if ($this->user->is_merchant >= 1) {
            return redirect()->route('merchant.dashboard');
        }

        // DATA_FLOW_POLICY: Build DTO from User model
        $profile = UserProfileDTO::fromUser($this->user);

        return view('user.apply-merchant', ['profile' => $profile]);
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
