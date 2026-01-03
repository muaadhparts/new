<?php

namespace App\Http\Controllers\Api\Front;

use App\Classes\MuaadhMailer;
use App\Helpers\CatalogItemContextHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Http\Resources\BlogResource;use App\Http\Resources\FaqResource;
use App\Http\Resources\FeaturedBannerResource;
use App\Http\Resources\FeaturedLinkResource;
use App\Http\Resources\PurchaseTrackResource;
use App\Http\Resources\PageResource;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CatalogItemListResource;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\SliderResource;use App\Models\ArrivalSection;
use App\Models\Banner;
use App\Models\Blog;
use App\Models\Currency;
use App\Models\Faq;
use App\Models\FeaturedBanner;
use App\Models\FeaturedLink;
use App\Models\Muaadhsetting;
use App\Models\Language;
use App\Models\Purchase;
use App\Models\Page;
use App\Models\Pagesetting;
use App\Models\Brand;
use App\Models\CatalogItem;
use App\Models\Service;
use App\Models\Slider;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Validator;

class FrontendController extends Controller
{
    // Display Sliders, Featured Links, Featured Banners, Services, Banners & Brands

    public function section_customization()
    {
        try {
            $data = Pagesetting::find(1)->toArray();
            return response()->json(['status' => true, 'data' => $data, 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    // Display All Type Of CatalogItems

    public function merchant_items(Request $request, $merchant_id)
    {
        try {
            $user = User::where('id', $merchant_id)->first();
            if (!$user) {
                return response()->json(['status' => false, 'data' => [], 'error' => ['message' => 'Merchant not found']]);
            }

            // Get catalogItems through merchant_items for this merchant
            // item_type is now on merchant_items, not catalogItems
            $merchantItemsQuery = \App\Models\MerchantItem::where('user_id', $user->id)
                ->where('status', 1)
                ->with(['catalogItem' => function($query) {
                    $query->where('status', 1);
                }]);

            if ($request->type && in_array($request->type, ['normal', 'affiliate'])) {
                $merchantItemsQuery->where('item_type', $request->type);
            }

            $merchantItems = $merchantItemsQuery->get();

            // Extract catalog items and inject merchant context using CatalogItemContextHelper
            $prods = $merchantItems->map(function($mp) use ($user) {
                if (!$mp->catalogItem) return null;

                $catalogItem = $mp->catalogItem;
                // Use CatalogItemContextHelper for consistency
                CatalogItemContextHelper::apply($catalogItem, $mp);
                return $catalogItem;
            })->filter()->values();

            return response()->json(['status' => true, 'data' => CatalogItemListResource::collection($prods), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function defaultLanguage()
    {
        try {
            $language = Language::where('is_default', '=', 1)->first();
            if (!$language) {
                return response()->json(['status' => true, 'data' => [], 'error' => ['message' => 'No Language Found']]);
            }
            $data_results = file_get_contents(public_path() . '/assets/languages/' . $language->file);
            $lang = json_decode($data_results);
            return response()->json(['status' => true, 'data' => ['basic' => $language, 'languages' => $lang], 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function language($id)
    {
        try {
            $language = Language::find($id);
            if (!$language) {
                return response()->json(['status' => true, 'data' => [], 'error' => ['message' => 'No Language Found']]);
            }
            $data_results = file_get_contents(public_path() . '/assets/languages/' . $language->file);
            $lang = json_decode($data_results);
            return response()->json(['status' => true, 'data' => ['basic' => $language, 'languages' => $lang], 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function languages()
    {
        try {
            $languages = Language::all();
            return response()->json(['status' => true, 'data' => $languages, 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function defaultCurrency()
    {
        try {
            $currency = Currency::where('is_default', '=', 1)->first();
            if (!$currency) {
                return response()->json(['status' => true, 'data' => [], 'error' => ['message' => 'No Currency Found']]);
            }
            return response()->json(['status' => true, 'data' => $currency, 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function currency($id)
    {
        try {
            $currency = Currency::find($id);
            if (!$currency) {
                return response()->json(['status' => true, 'data' => [], 'error' => ['message' => 'No Currency Found']]);
            }
            return response()->json(['status' => true, 'data' => $currency, 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function currencies()
    {
        try {
            $currencies = Currency::all();
            return response()->json(['status' => true, 'data' => $currencies, 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function sliders()
    {
        try {
            $sliders = Slider::all();
            return response()->json(['status' => true, 'data' => SliderResource::collection($sliders), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function featuredLinks()
    {
        try {
            $featuredLinks = FeaturedLink::all();
            return response()->json(['status' => true, 'data' => FeaturedLinkResource::collection($featuredLinks), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function featuredBanners()
    {
        try {
            $featuredBanners = FeaturedBanner::all();
            return response()->json(['status' => true, 'data' => FeaturedBannerResource::collection($featuredBanners), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function services()
    {
        try {
            $services = Service::where('user_id', '=', 0)->get();
            return response()->json(['status' => true, 'data' => ServiceResource::collection($services), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function banners(Request $request)
    {

        try {

            $rules = [
                'type' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }

            $type = $request->type;
            $checkType = in_array($type, ['TopSmall', 'BottomSmall', 'Large']);

            if (!$checkType) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => "This type doesn't exists."]]);
            }

            if ($request->type == 'TopSmall') {
                $banners = Banner::where('type', '=', 'TopSmall')->get();
            } elseif ($request->type == 'BottomSmall') {
                $banners = Banner::where('type', '=', 'BottomSmall')->get();
            } elseif ($request->type == 'Large') {
                $ps = Pagesetting::first();
                $large_banner['image'] = asset('assets/images/' . $ps->big_save_banner1);
                $large_banner['title'] = $ps->big_save_banner_text;
                $large_banner['text'] = $ps->big_save_banner_subtitle;
                $large_banner['link'] = $ps->big_save_banner_link1;

                return response()->json(['status' => true, 'data' => $large_banner, 'error' => []]);
            }
            return response()->json(['status' => true, 'data' => BannerResource::collection($banners), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function brands()
    {
        try {
            $brands = Brand::all();
            return response()->json(['status' => true, 'data' => BrandResource::collection($brands), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    // Display Sliders, Featured Links, Featured Banners, Services, Banners & Brands Ends

    // Display All Type Of CatalogItems

    public function catalogItems(Request $request)
    {

        try {
            $input = $request->all();

            if (!empty($input)) {

                $type = isset($input['type']) ? $input['type'] : '';
                $typeCheck = !empty($type) && in_array($type, ['Physical', 'Digital', 'License', 'Listing']);
                $highlight = isset($input['highlight']) ? $input['highlight'] : '';
                $highlightCheck = !empty($highlight) && in_array($highlight, ['featured', 'best', 'top', 'big', 'is_discount', 'hot', 'latest', 'trending', 'sale']);
                $itemType = isset($input['item_type']) ? $input['item_type'] : '';
                $itemTypeCheck = !empty($itemType) && in_array($itemType, ['normal', 'affiliate']);
                $limit = isset($input['limit']) ? (int) $input['limit'] : 0;
                $paginate = isset($input['paginate']) ? (int) $input['paginate'] : 0;

                $prods = CatalogItem::whereStatus(1);

                if ($typeCheck) {
                    $prods = $prods->whereType($type);
                }

                // item_type is now on merchant_items, not catalog_items
                if ($itemTypeCheck) {
                    $prods = $prods->whereHas('merchantItems', fn($q) => $q->where('item_type', $itemType));
                }

                // All highlight flags (featured, best, top, big, trending) are on merchant_items table
                if ($highlightCheck) {
                    if ($highlight == 'featured') {
                        $prods = $prods->whereHas('merchantItems', fn($q) => $q->where('featured', 1)->where('status', 1));
                    } else if ($highlight == 'best') {
                        $prods = $prods->whereHas('merchantItems', fn($q) => $q->where('best', 1)->where('status', 1));
                    } else if ($highlight == 'top') {
                        $prods = $prods->whereHas('merchantItems', fn($q) => $q->where('top', 1)->where('status', 1));
                    } else if ($highlight == 'big') {
                        $prods = $prods->whereHas('merchantItems', fn($q) => $q->where('big', 1)->where('status', 1));
                    } else if ($highlight == 'is_discount') {
                        $prods = $prods->whereHas('merchantItems', fn($q) => $q->where('is_discount', 1)->where('discount_date', '>=', date('Y-m-d'))->where('status', 1));
                    } else if ($highlight == 'hot') {
                        $prods = $prods->whereHas('merchantItems', fn($q) => $q->where('hot', 1)->where('status', 1));
                    } else if ($highlight == 'latest') {
                        $prods = $prods->orderBy('id', 'desc');
                    } else if ($highlight == 'trending') {
                        $prods = $prods->whereHas('merchantItems', fn($q) => $q->where('trending', 1)->where('status', 1));
                    } else {
                        $prods = $prods->whereHas('merchantItems', fn($q) => $q->where('sale', 1)->where('status', 1));
                    }
                }

                if ($limit != 0) {
                    $prods = $prods->where('status', 1)->take($limit);
                }

                if ($paginate == 0) {
                    $prods = $prods->where('status', 1)->get();
                } else {
                    $prods = $prods->where('status', 1)->paginate($paginate);
                }

                // Note: General listing shows catalog items from all merchants
                // For merchant-specific data, CatalogItemListResource will use the first available merchant_item
                return response()->json(['status' => true, 'data' => CatalogItemListResource::collection($prods)->response()->getData(true), 'error' => []]);
            } else {

                $prods = CatalogItem::status(1)->get();
                // Note: General listing shows catalog items from all merchants
                // For merchant-specific data, CatalogItemListResource will use the first available merchant_item
                return response()->json(['status' => true, 'data' => CatalogItemListResource::collection($prods), 'error' => []]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    // Display All Type Of CatalogItems Ends

    // Display Faq, Blog, Page

    public function faqs()
    {
        try {
            $faqs = Faq::all();
            return response()->json(['status' => true, 'data' => FaqResource::collection($faqs), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function blogs(Request $request)
    {
        try {
            if($request->type == 'latest'){
                $blogs = Blog::orderby('id','desc')->take(6)->get();
            }else{
                $blogs = Blog::all();
            }
            
            return response()->json(['status' => true, 'data' => BlogResource::collection($blogs), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function pages()
    {
        try {
            $pages = Page::all();
            return response()->json(['status' => true, 'data' => PageResource::collection($pages), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    // Display Faq, Blog, Page Ends

    // Display All Settings

    public function settings(Request $request)
    {
    
        try {

            $rules = [
                'name' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }

            $name = $request->name;
            $checkSettings = in_array($name, ['muaadhsettings', 'pagesettings', 'socialsettings']);
            if (!$checkSettings) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => "This setting doesn't exists."]]);
            }

            $setting = DB::table($name)->first();
//            dd($setting);
            Log::debug('mm',['status' => true, 'data' => $setting, 'error' => []]);
            return response()->json(['status' => true, 'data' => $setting, 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    // Display All Settings Ends

    // Display Purchase Tracks

    public function ordertrack(Request $request)
    {
        try {
            $rules = [
                'purchase_number' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }

            $purchase_number = $request->purchase_number;

            $purchase = Purchase::where('purchase_number', $purchase_number)->first();
            if (!$purchase) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => "Purchase not found."]]);
            }

            return response()->json(['status' => true, 'data' => PurchaseTrackResource::collection($purchase->tracks), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    // Display Purchase Tracks Ends

    // Send Email To Admin

    public function contactmail(Request $request)
    {
        try {
            //--- Validation Section

            $rules =
                [
                'name' => 'required',
                'email' => 'required|email',
                'phone' => 'required',
                'message' => 'required',

            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }

            $gs = Muaadhsetting::find(1);

            // Login Section
            $ps = DB::table('pagesettings')->where('id', '=', 1)->first();
            $subject = "Email From Of " . $request->name;
            $to = $ps->contact_email;
            $name = $request->name;
            $phone = $request->phone;
            $from = $request->email;
            $msg = "Name: " . $name . "\nEmail: " . $from . "\nPhone: " . $request->phone . "\nMessage: " . $request->message;
            if ($gs->is_smtp) {
                $data = [
                    'to' => $to,
                    'subject' => $subject,
                    'body' => $msg,
                ];

                $mailer = new MuaadhMailer();
                $mailer->sendCustomMail($data);
            } else {
                $headers = "From: " . $name . "<" . $from . ">";
                mail($to, $subject, $msg, $headers);
            }
            // Login Section Ends

            // Redirect Section
            return response()->json(['status' => true, 'data' => ['message' => 'Email Sent Successfully!'], 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    // Send Email To Admin Ends

    public function deal()
    {
        $gs = Muaadhsetting::findOrFail(1);
        $data['title'] = $gs->deal_title;
        $data['deal_details'] = $gs->deal_details;
        $data['time'] = $gs->deal_time;
        $data['image'] = url('/') . '/assets/images/' . $gs->deal_background;
        $data['link'] = $gs->link;
        return response()->json(['status' => true, 'data' => $data, 'error' => []]);
    }

    public function arrival()
    {
        $ArrivalSection = ArrivalSection::get()->toArray();
        foreach ($ArrivalSection as $key => $value) {
            $ArrivalSection[$key]['photo'] = url('/') . '/assets/images/banners/' . $value['photo'];
        }

        return response()->json(['status' => true, 'data' => $ArrivalSection, 'error' => []]);
    }



}
