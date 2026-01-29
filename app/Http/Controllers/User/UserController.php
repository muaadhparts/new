<?php

namespace App\Http\Controllers\User;

use App\Domain\Accounting\Models\ReferralCommission;
use App\Domain\Commerce\Models\FavoriteSeller;
use App\Domain\Identity\DTOs\UserProfileDTO;
use App\Domain\Identity\Services\UserDashboardBuilder;
use App\Domain\Identity\Services\UserProfileService;
use App\Domain\Catalog\Services\CatalogItemCardDTOBuilder;
use Illuminate\Http\Request;

class UserController extends UserBaseController
{
    public function __construct(
        private UserProfileService $profileService,
        private CatalogItemCardDTOBuilder $cardBuilder,
    ) {}

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
        $rules = [
            'photo' => 'mimes:jpeg,jpg,png,svg',
            'email' => 'unique:users,email,' . $this->user->id,
        ];

        $customs = [
            'photo.mimes' => __('The image must be a file of type: jpeg, jpg, png, svg.'),
        ];

        $request->validate($rules, $customs);

        try {
            $this->profileService->updateProfile(
                $this->user,
                $request->except('photo'),
                $request->file('photo')
            );

            return redirect()->route('user-profile')
                ->with('success', __('Profile Updated Successfully!'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('unsuccess', $e->getMessage());
        }
    }

    public function resetform()
    {
        return view('user.reset');
    }

    public function reset(Request $request)
    {
        try {
            $this->profileService->resetPassword(
                $this->user,
                $request->cpass,
                $request->newpass,
                $request->renewpass
            );

            return back()->with('success', __('Password Updated Successfully!'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('unsuccess', $e->getMessage());
        }
    }

    // Old favorite methods removed - now handled by FavoriteController

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
     * Display pricing packages
     */
    public function packages()
    {
        // TODO: Implement packages/pricing page
        // This should show merchant subscription plans
        return view('user.packages');
    }

    /**
     * Submit merchant application
     */
    public function submitMerchantApplication(Request $request)
    {
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

        try {
            $this->profileService->submitMerchantApplication(
                $this->user,
                $request->only(['shop_name', 'shop_number', 'shop_address', 'shop_message'])
            );

            return redirect()->route('merchant.dashboard')
                ->with('success', __('Your merchant application has been submitted. Please wait for admin verification.'));
        } catch (\LogicException $e) {
            return redirect()->route('merchant.dashboard');
        }
    }
}
