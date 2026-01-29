<?php

namespace App\Http\Controllers\Merchant;

use App\Domain\Catalog\Services\ImageService;
use App\Domain\Merchant\Models\TrustBadge;
use App\Domain\Merchant\Services\MerchantDashboardService;
use Illuminate\Http\Request;

/**
 * MerchantController
 *
 * DATA FLOW POLICY:
 * - Controller = Orchestration only
 * - All business logic in Services
 * - All formatting in DisplayServices
 * - All queries in Query classes
 */
class MerchantController extends MerchantBaseController
{
    public function __construct(
        private MerchantDashboardService $dashboardService,
        private ImageService $imageService,
    ) {
        parent::__construct();
    }

    /**
     * Merchant dashboard
     */
    public function index()
    {
        $data = $this->dashboardService->getDashboardData($this->user->id);
        return view('merchant.index', $data);
    }

    /**
     * Show profile
     */
    public function profile()
    {
        return view('merchant.profile', ['user' => $this->user]);
    }

    /**
     * Update profile
     */
    public function profileupdate(Request $request)
    {
        $request->validate([
            'shop_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $this->user->update($request->only(['shop_name', 'phone', 'address']));

        return redirect()->back()->with('success', __('Profile updated successfully'));
    }

    /**
     * Show trust badge page
     */
    public function trustBadge()
    {
        $trustBadge = TrustBadge::where('user_id', $this->user->id)->first();
        return view('merchant.trust-badge', compact('trustBadge'));
    }

    /**
     * Show trust badge warning
     */
    public function warningTrustBadge()
    {
        return view('merchant.trust-badge-warning');
    }

    /**
     * Submit trust badge request
     */
    public function trustBadgeSubmit(Request $request)
    {
        $request->validate([
            'commercial_register' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'tax_certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $commercialRegister = $request->file('commercial_register')->store('trust_badges', 'public');
        $taxCertificate = $request->file('tax_certificate')->store('trust_badges', 'public');

        TrustBadge::updateOrCreate(
            ['user_id' => $this->user->id],
            [
                'commercial_register' => $commercialRegister,
                'tax_certificate' => $taxCertificate,
                'status' => 'pending',
            ]
        );

        return redirect()->route('merchant.trust-badge')->with('success', __('Trust badge request submitted'));
    }

    /**
     * Show logo page
     */
    public function logo()
    {
        return view('merchant.logo', ['user' => $this->user]);
    }

    /**
     * Update logo
     */
    public function logoUpdate(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $logo = $this->imageService->uploadMerchantLogo($request->file('logo'), $this->user->id);

        $this->user->update(['logo' => $logo]);

        return redirect()->back()->with('success', __('Logo updated successfully'));
    }

    /**
     * Delete logo
     */
    public function logoDelete()
    {
        if ($this->user->logo) {
            $this->imageService->deleteMerchantLogo($this->user->logo);
            $this->user->update(['logo' => null]);
        }

        return redirect()->back()->with('success', __('Logo deleted successfully'));
    }
}
