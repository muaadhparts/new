<?php

namespace App\Http\Controllers\Api\Front;

use App\Classes\MuaadhMailer;
use App\Helpers\CatalogItemContextHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Http\Resources\PurchaseTrackResource;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CatalogItemListResource;
use App\Models\FeaturedPromo;
use App\Models\Language;
use App\Models\Purchase;
use App\Models\Page;
use App\Models\FrontendSetting;
use App\Models\Brand;
use App\Models\CatalogItem;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Validator;

class FrontendController extends Controller
{
    // Display Banners & Brands

    public function section_customization()
    {
        try {
            $data = FrontendSetting::find(1)->toArray();
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

            // CatalogItem-first: Query catalog items that have merchant offers from this merchant
            $query = CatalogItem::whereHas('merchantItems', function($q) use ($user, $request) {
                    $q->where('user_id', $user->id)->where('status', 1);
                    if ($request->type && in_array($request->type, ['normal', 'affiliate'])) {
                        $q->where('item_type', $request->type);
                    }
                })
                ->with(['merchantItems' => function($q) use ($user, $request) {
                    $q->where('user_id', $user->id)->where('status', 1);
                    if ($request->type && in_array($request->type, ['normal', 'affiliate'])) {
                        $q->where('item_type', $request->type);
                    }
                }]);

            $prods = $query->get();

            // Inject merchant context for each catalog item
            $prods->each(function($catalogItem) {
                $mp = $catalogItem->merchantItems->first();
                if ($mp) {
                    CatalogItemContextHelper::apply($catalogItem, $mp);
                }
            });

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

    public function defaultMonetaryUnit()
    {
        try {
            $monetaryUnit = monetaryUnit()->getDefault();
            if (!$monetaryUnit) {
                return response()->json(['status' => true, 'data' => [], 'error' => ['message' => 'No Monetary Unit Found']]);
            }
            return response()->json(['status' => true, 'data' => $monetaryUnit, 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function monetaryUnit($id)
    {
        try {
            $monetaryUnit = monetaryUnit()->getById((int) $id);
            if (!$monetaryUnit) {
                return response()->json(['status' => true, 'data' => [], 'error' => ['message' => 'No Monetary Unit Found']]);
            }
            return response()->json(['status' => true, 'data' => $monetaryUnit, 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function monetaryUnits()
    {
        try {
            $monetaryUnits = monetaryUnit()->getAll();
            return response()->json(['status' => true, 'data' => $monetaryUnits, 'error' => []]);
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
            // ANNOUNCEMENTS FEATURE REMOVED
            return response()->json(['status' => true, 'data' => [], 'error' => []]);
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

    // Display Banners & Brands Ends

    // Display All Type Of CatalogItems

    public function catalogItems(Request $request)
    {
        try {
            $input = $request->all();

            if (!empty($input)) {
                $itemType = isset($input['item_type']) ? $input['item_type'] : '';
                $itemTypeCheck = !empty($itemType) && in_array($itemType, ['normal', 'affiliate']);
                $limit = isset($input['limit']) ? (int) $input['limit'] : 0;
                $paginate = isset($input['paginate']) ? (int) $input['paginate'] : 0;
                $highlight = isset($input['highlight']) ? $input['highlight'] : '';

                $prods = CatalogItem::where('status', 1);

                // item_type filter via merchantItems
                if ($itemTypeCheck) {
                    $prods = $prods->whereHas('merchantItems', fn($q) => $q->where('item_type', $itemType)->where('status', 1));
                }

                // Only 'latest' highlight is supported (others are removed)
                if ($highlight == 'latest') {
                    $prods = $prods->orderBy('id', 'desc');
                }

                if ($limit != 0) {
                    $prods = $prods->take($limit);
                }

                if ($paginate == 0) {
                    $prods = $prods->get();
                } else {
                    $prods = $prods->paginate($paginate);
                }

                return response()->json(['status' => true, 'data' => CatalogItemListResource::collection($prods)->response()->getData(true), 'error' => []]);
            } else {
                $prods = CatalogItem::where('status', 1)->get();
                return response()->json(['status' => true, 'data' => CatalogItemListResource::collection($prods), 'error' => []]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    // Display All Type Of CatalogItems Ends

    // Display Pages (Policy pages only: terms, privacy, refund)

    public function helpArticles()
    {
        // HELP ARTICLES FEATURE REMOVED
        return response()->json(['status' => true, 'data' => [], 'error' => []]);
    }

    public function blogs(Request $request)
    {
        // PUBLICATIONS/BLOGS FEATURE REMOVED
        return response()->json(['status' => true, 'data' => [], 'error' => []]);
    }

    public function pages()
    {
        try {
            $pages = Page::where('is_active', true)->get();
            return response()->json(['status' => true, 'data' => $pages, 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    // Display Pages Ends

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

            // Map legacy names to new system
            if ($name === 'muaadhsettings' || $name === 'connect_configs') {
                // Return platform settings as unified settings
                $setting = platformSettings()->all();
                return response()->json(['status' => true, 'data' => $setting, 'error' => []]);
            }

            $checkSettings = in_array($name, ['frontend_settings']);
            if (!$checkSettings) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => "This setting doesn't exists."]]);
            }

            $setting = DB::table($name)->first();
            return response()->json(['status' => true, 'data' => $setting, 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    // Display All Settings Ends

    // Display Purchase Tracks

    public function purchasetrack(Request $request)
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

            $ps = platformSettings();

            // Login Section
            $frontendSettings = DB::table('frontend_settings')->where('id', '=', 1)->first();
            $subject = "Email From Of " . $request->name;
            $to = $frontendSettings->contact_email;
            $name = $request->name;
            $phone = $request->phone;
            $from = $request->email;
            $msg = "Name: " . $name . "\nEmail: " . $from . "\nPhone: " . $request->phone . "\nMessage: " . $request->message;
            if ($ps->get('mail_driver')) {
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

    public function arrival()
    {
        $FeaturedPromo = FeaturedPromo::get()->toArray();
        foreach ($FeaturedPromo as $key => $value) {
            $FeaturedPromo[$key]['photo'] = url('/') . '/assets/images/banners/' . $value['photo'];
        }

        return response()->json(['status' => true, 'data' => $FeaturedPromo, 'error' => []]);
    }



}
