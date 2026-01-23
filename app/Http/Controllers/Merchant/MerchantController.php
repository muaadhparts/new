<?php

namespace App\Http\Controllers\Merchant;

use App\Models\CatalogItem;
use App\Models\MerchantItem;
use App\Models\MerchantPurchase;
use App\Models\TrustBadge;
use App\Services\ImageService;
use Illuminate\Http\Request;

class MerchantController extends MerchantBaseController
{

    //*** GET Request
    public function index()
    {
        try {
            $userId = $this->user->id;

            // ============================================================
            // OPTIMIZED: Single query for 30-day sales chart (was 30 queries)
            // ============================================================
            $startDate = now()->subDays(29)->startOfDay();
            $endDate = now()->endOfDay();

            $salesData = MerchantPurchase::where('user_id', $userId)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('DATE(created_at) as date, SUM(price) as total')
                ->groupBy('date')
                ->pluck('total', 'date')
                ->toArray();

            // Build chart arrays (reversed to show oldest first)
            $days = [];
            $sales = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = date("Y-m-d", strtotime('-' . $i . ' days'));
                $days[] = "'" . date("d M", strtotime('-' . $i . ' days')) . "'";
                $sales[] = "'" . ($salesData[$date] ?? 0) . "'";
            }
            $data['days'] = implode(',', $days);
            $data['sales'] = implode(',', $sales);

            // ============================================================
            // Merchant items (limited to 5) - MerchantItem is primary entity
            // ============================================================
            $data['merchantItems'] = MerchantItem::where('user_id', $userId)
                ->where('item_type', 'normal')
                ->with([
                    'catalogItem.fitments.brand',
                    'qualityBrand',
                    'merchantBranch',
                ])
                ->latest('id')
                ->take(5)
                ->get();

            // Recent purchases (limited to 10)
            $data['recentMerchantPurchases'] = MerchantPurchase::where('user_id', $userId)
                ->latest('id')
                ->take(10)
                ->get();

            $data['user'] = $this->user;

            // ============================================================
            // OPTIMIZED: Use count() instead of get() for statistics
            // ============================================================
            $data['pending'] = MerchantPurchase::where('user_id', $userId)
                ->where('status', 'pending')
                ->count();

            $data['processing'] = MerchantPurchase::where('user_id', $userId)
                ->where('status', 'processing')
                ->count();

            $data['completed'] = MerchantPurchase::where('user_id', $userId)
                ->where('status', 'completed')
                ->count();

            return view('merchant.index', $data);
        } catch (\Exception $e) {
            \Log::error('MerchantController@index error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->with('unsuccess', 'An error occurred while loading the dashboard. Please try again.');
        }
    }

    public function profileupdate(Request $request)
    {

        //--- Validation Section
        $rules = [
            'shop_name' => 'unique:users,shop_name,' . $this->user->id,
            'owner_name' => 'required',
            "shop_number" => "required",
            "shop_address" => "required",
            "reg_number" => "required",
            "shop_image" => "mimes:jpeg,jpg,png,svg|max:3000",
        ];

        $request->validate($rules);

        $input = $request->all();
        $data = $this->user;

        if ($file = $request->file('shop_image')) {
            $extensions = ['jpeg', 'jpg', 'png', 'svg'];
            if (!in_array($file->getClientOriginalExtension(), $extensions)) {
                return response()->json(array('errors' => ['Image format not supported']));
            }
            $name = \PriceHelper::ImageCreateName($file);
            $file->move('assets/images/merchantbanner', $name);
            $input['shop_image'] = $name;
        }

        $data->update($input);
        return back()->with('success', 'Profile Updated Successfully');

    }

    // Spcial Settings All post requests will be done in this method
    public function socialupdate(Request $request)
    {
        //--- Logic Section
        $input = $request->all();
        $data = $this->user;
        if ($request->f_check == "") {
            $input['f_check'] = 0;
        }
        if ($request->t_check == "") {
            $input['t_check'] = 0;
        }

        if ($request->g_check == "") {
            $input['g_check'] = 0;
        }

        if ($request->l_check == "") {
            $input['l_check'] = 0;
        }
        $data->update($input);
        //--- Logic Section Ends
        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends

    }

    //*** GET Request
    public function profile()
    {
        $data = $this->user;
        return view('merchant.profile', compact('data'));
    }

    //*** GET Request
    public function ship()
    {
        $data = $this->user;
        return view('merchant.ship', compact('data'));
    }

    //*** GET Request
    public function banner()
    {
        $data = $this->user;
        return view('merchant.banner', compact('data'));
    }

    //*** GET Request
    public function social()
    {
        $data = $this->user;
        return view('merchant.social', compact('data'));
    }

    //*** GET Request
    public function trustBadge()
    {
        $data = $this->user;
        if ($data->isTrustBadgeTrusted()) {
            return redirect()->route('merchant-profile')->with('success', __('Your Account is already trusted.'));
        }
        return view('merchant.trust-badge', compact('data'));
    }

    //*** GET Request
    public function warningTrustBadge($id)
    {
        $trustBadge = TrustBadge::findOrFail($id);
        $data = $this->user;
        return view('merchant.trust-badge', compact('data', 'trustBadge'));
    }

    //*** POST Request
    public function trustBadgeSubmit(Request $request)
    {
        //--- Validation Section
        $rules = [
            'attachments.*' => 'mimes:jpeg,jpg,png,svg|max:10000',
        ];
        $customs = [
            'attachments.*.mimes' => __('Only jpeg, jpg, png and svg images are allowed'),
            'attachments.*.max' => __('Sorry! Maximum allowed size for an image is 10MB'),
        ];

        $request->validate($rules, $customs);

        $data = new TrustBadge();
        $input = $request->all();

        $input['attachments'] = '';
        $i = 0;
        if ($files = $request->file('attachments')) {
            foreach ($files as $key => $file) {
                $name = \PriceHelper::ImageCreateName($file);
                if ($i == count($files) - 1) {
                    $input['attachments'] .= $name;
                } else {
                    $input['attachments'] .= $name . ',';
                }
                $file->move('assets/images/attachments', $name);

                $i++;
            }
        }
        $input['status'] = 'Pending';
        $input['user_id'] = $this->user->id;
        if ($request->trust_badge_id != '0') {
            $trustBadge = TrustBadge::findOrFail($request->trust_badge_id);
            $input['admin_warning'] = 0;
            $trustBadge->update($input);
        } else {

            $data->fill($input)->save();
        }

        return back()->with('success', __('Trust badge request sent successfully.'));
        //--- Redirect Section Ends
    }

    //*** GET Request - Merchant Logo Page
    public function logo()
    {
        $data = $this->user;
        $logoUrl = null;

        if (!empty($data->merchant_logo)) {
            $logoUrl = app(ImageService::class)->getMerchantLogoUrl($data->merchant_logo);
        }

        return view('merchant.logo', compact('data', 'logoUrl'));
    }

    //*** POST Request - Update Merchant Logo
    public function logoUpdate(Request $request)
    {
        $rules = [
            'merchant_logo' => 'required|mimes:jpeg,jpg,png,svg|max:2048',
        ];
        $customs = [
            'merchant_logo.required' => __('Please select a logo image'),
            'merchant_logo.mimes' => __('Only jpeg, jpg, png and svg images are allowed'),
            'merchant_logo.max' => __('Maximum allowed size for logo is 2MB'),
        ];

        $request->validate($rules, $customs);

        $user = $this->user;
        $imageService = app(ImageService::class);

        // Delete old logo if exists
        if (!empty($user->merchant_logo)) {
            $imageService->delete($user->merchant_logo);
        }

        // Upload new logo
        $path = $imageService->uploadMerchantLogo($request->file('merchant_logo'), $user->id);
        $user->merchant_logo = $path;
        $user->save();

        return back()->with('success', __('Merchant logo updated successfully.'));
    }

    //*** POST Request - Delete Merchant Logo
    public function logoDelete()
    {
        $user = $this->user;

        if (!empty($user->merchant_logo)) {
            $imageService = app(ImageService::class);
            $imageService->delete($user->merchant_logo);
            $user->merchant_logo = null;
            $user->save();
        }

        return back()->with('success', __('Merchant logo deleted successfully.'));
    }

}
