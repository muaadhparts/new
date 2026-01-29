<?php

namespace App\Http\Controllers\Merchant;

use App\Domain\Catalog\Services\ImageService;
use App\Domain\Commerce\Models\MerchantPurchase;
use App\Domain\Merchant\Models\TrustBadge;
use App\Domain\Merchant\Queries\MerchantItemQuery;
use App\Domain\Merchant\Services\MerchantItemDisplayService;
use Illuminate\Http\Request;

class MerchantController extends MerchantBaseController
{
    public function __construct(
        private MerchantItemQuery $itemQuery,
        private MerchantItemDisplayService $displayService,
        private ImageService $imageService,
    ) {
        parent::__construct();
    }

    /**
     * Merchant dashboard
     */
    public function index()
    {
        try {
            $userId = $this->user->id;

            // Sales chart data (last 30 days)
            $startDate = now()->subDays(29)->startOfDay();
            $endDate = now()->endOfDay();

            $salesData = MerchantPurchase::where('user_id', $userId)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('DATE(created_at) as date, SUM(price) as total')
                ->groupBy('date')
                ->pluck('total', 'date')
                ->toArray();

            $days = [];
            $sales = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = date("Y-m-d", strtotime('-' . $i . ' days'));
                $days[] = "'" . date("d M", strtotime('-' . $i . ' days')) . "'";
                $sales[] = "'" . ($salesData[$date] ?? 0) . "'";
            }
            $data['days'] = implode(',', $days);
            $data['sales'] = implode(',', $sales);

            // Recent merchant items
            $merchantItems = $this->itemQuery::make()
                ->forMerchant($userId)
                ->active()
                ->withRelations()
                ->latest()
                ->paginate(5);

            $data['merchantItems'] = collect($merchantItems->items())
                ->map(fn($item) => $this->displayService->format($item))
                ->toArray();

            // Recent purchases
            $data['purchases'] = MerchantPurchase::where('user_id', $userId)
                ->latest()
                ->take(5)
                ->get();

            // Statistics
            $data['totalItems'] = $this->itemQuery::make()
                ->forMerchant($userId)
                ->count();

            $data['activeItems'] = $this->itemQuery::make()
                ->forMerchant($userId)
                ->active()
                ->count();

            $data['totalSales'] = MerchantPurchase::where('user_id', $userId)
                ->where('status', 'completed')
                ->sum('price');

            $data['pendingOrders'] = MerchantPurchase::where('user_id', $userId)
                ->where('status', 'pending')
                ->count();

            // Additional statistics for dashboard cards
            $data['processing'] = MerchantPurchase::where('user_id', $userId)
                ->where('status', 'processing')
                ->count();

            $data['completed'] = MerchantPurchase::where('user_id', $userId)
                ->where('status', 'completed')
                ->count();

            $data['totalCatalogItems'] = $data['totalItems'];

            $data['totalItemsSold'] = MerchantPurchase::where('user_id', $userId)
                ->where('status', 'completed')
                ->sum('qty');

            $data['currentBalance'] = $this->user->balance ?? 0;

            $data['totalEarning'] = $data['totalSales'];

            // Alias for view compatibility
            $data['pending'] = $data['pendingOrders'];

            return view('merchant.index', $data);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Error loading dashboard'));
        }
    }

    /**
     * Show profile
     */
    public function profile()
    {
        $data['user'] = $this->user;
        return view('merchant.profile', $data);
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
