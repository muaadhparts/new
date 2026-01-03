<?php

namespace App\Http\Controllers\Merchant;

use App\Models\Muaadhsetting;
use App\Models\CatalogItem;
use App\Models\MerchantPurchase;
use App\Models\Verification;
use Illuminate\Http\Request;

class MerchantController extends MerchantBaseController
{

    //*** GET Request
    public function index()
    {
        try {
            $data['days'] = "";
            $data['sales'] = "";
            for ($i = 0; $i < 30; $i++) {
                $data['days'] .= "'" . date("d M", strtotime('-' . $i . ' days')) . "',";

                $data['sales'] .= "'" . MerchantPurchase::where('user_id', '=', $this->user->id)->where('status', '=', 'completed')->whereDate('created_at', '=', date("Y-m-d", strtotime('-' . $i . ' days')))->sum("price") . "',";
            }
            // Retrieve recent catalog items for this merchant using the merchantItems relationship.
            // Limit to 5 entries to avoid overwhelming the dashboard when there are many items.
            $userId = $this->user->id;
            $data['catalogItems'] = CatalogItem::whereHas('merchantItems', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->with([
                'brand',
                'merchantItems' => function ($q) use ($userId) {
                    $q->where('user_id', $userId)->with('qualityBrand');
                }
            ])
            ->latest('catalog_items.id')->take(5)->get();
            $data['recentMerchantPurchases'] = MerchantPurchase::where('user_id', '=', $this->user->id)->latest('id')->take(10)->get();
            $data['user'] = $this->user;
            $data['pending'] = MerchantPurchase::where('user_id', '=', $this->user->id)->where('status', '=', 'pending')->get();
            $data['processing'] = MerchantPurchase::where('user_id', '=', $this->user->id)->where('status', '=', 'processing')->get();
            $data['completed'] = MerchantPurchase::where('user_id', '=', $this->user->id)->where('status', '=', 'completed')->get();
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
        $gs = Muaadhsetting::find(1);
        if ($gs->merchant_ship_info == 0) {
            return redirect()->back();
        }
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

    // TODO: Removed - old category system
    // public function subcatload($id)
    // {
    //     $cat = Category::findOrFail($id);
    //     return view('load.subcategory', compact('cat'));
    // }

    // TODO: Removed - old category system
    // public function childcatload($id)
    // {
    //     $subcat = Subcategory::findOrFail($id);
    //     return view('load.childcategory', compact('subcat'));
    // }

    //*** GET Request
    public function verify()
    {
        $data = $this->user;
        if ($data->checkStatus()) {
            return redirect()->route('merchant-profile')->with('success', __('Your Account is already verified.'));
        }
        return view('merchant.verify', compact('data'));
    }

    //*** GET Request
    public function warningVerify($id)
    {
        $verify = Verification::findOrFail($id);
        $data = $this->user;
        return view('merchant.verify', compact('data', 'verify'));
    }

    //*** POST Request
    public function verifysubmit(Request $request)
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

        $data = new Verification();
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
        if ($request->verify_id != '0') {
            $verify = Verification::findOrFail($request->verify_id);
            $input['admin_warning'] = 0;
            $verify->update($input);
        } else {

            $data->fill($input)->save();
        }

        return back()->with('success', __('Verification request sent successfully.'));
        //--- Redirect Section Ends
    }

}
