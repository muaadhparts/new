<?php

namespace App\Http\Controllers\Front;

use App\{
    Models\User,
    Models\CatalogItem,
    Classes\MuaadhMailer,
    Models\ChatThread,
    Models\ChatEntry
};
use App\Models\QualityBrand;
use Illuminate\{
    Http\Request,
    Support\Facades\DB
};

class MerchantController extends FrontBaseController
{
    public function index(Request $request, $slug)
    {
        $sort   = $request->sort;
        $pageby = $request->pageby;
        $brandQualityFilter = $request->input('brand_quality', []);

        $string = str_replace('-', ' ', $slug);
        $merchant = User::where('shop_name', '=', $string)->first();

        // If no merchant found, try static page or 404
        if (empty($merchant)) {
            $page = DB::table('static_content')->where('slug', $slug)->first();
            if (empty($page)) {
                return response()->view('errors.404', [], 404);
            }
            return view('frontend.static-content', compact('page'));
        }

        $data['merchant']     = $merchant;
        // TODO: Removed - old category system
        $data['categories'] = collect(); // Category::where('status', 1)->get();

        // Get Brand Qualities available for this merchant
        $merchantQualityIds = DB::table('merchant_items')
            ->where('user_id', $merchant->id)
            ->where('status', 1)
            ->whereNotNull('brand_quality_id')
            ->distinct()
            ->pluck('brand_quality_id');

        $data['brand_qualities'] = QualityBrand::whereIn('id', $merchantQualityIds)->get();

        // Latest items: based on merchant_items (active + merchant enabled)
        $data['latest_products'] = CatalogItem::status(1)
            ->whereLatest(1)
            ->whereHas('merchantItems', function ($q) {
                $q->where('status', 1)
                  ->whereHas('user', function ($u) {
                      $u->where('is_merchant', 2);
                  });
            })
            ->with(['merchantItems' => function ($q) {
                $q->where('status', 1)->with('user:id,is_merchant');
            }])
            ->withCount('catalogReviews')
            ->withAvg('catalogReviews', 'rating')
            ->latest('catalog_items.id')
            ->take(5)
            ->get();

        // Build merchant items query with price, discount and sort filters
        $prods = CatalogItem::query();

        // Eager load merchantItems to avoid N+1 query
        $prods = $prods->with([
            'brand:id,name,name_ar,photo',
            'merchantItems' => function ($q) use ($merchant) {
                $q->where('user_id', $merchant->id)
                  ->where('status', 1)
                  ->with(['user:id,is_merchant,shop_name,shop_name_ar', 'qualityBrand:id,name_en,name_ar,logo']);
            }
        ]);

        // Filter by specific merchant and Brand Quality via merchant_items
        $prods = $prods->whereHas('merchantItems', function ($q) use ($merchant, $brandQualityFilter, $request) {
            $q->where('user_id', $merchant->id)
              ->where('status', 1);

            // Filter by Brand Quality
            if (!empty($brandQualityFilter)) {
                $q->whereIn('brand_quality_id', (array) $brandQualityFilter);
            }

            // Discount filter (type) from merchant_items
            if ($request->has('type')) {
                $q->where('is_discount', 1)
                  ->where('discount_date', '>=', date('Y-m-d'));
            }
        });

        // Sort results
        $prods = $prods->when($sort, function ($query, $sort) use ($merchant) {
            if ($sort === 'date_desc') {
                return $query->latest('catalog_items.id');
            } elseif ($sort === 'date_asc') {
                return $query->oldest('catalog_items.id');
            } elseif ($sort === 'price_desc') {
                // Sort by highest merchant price for this catalogItem (for current merchant)
                return $query->orderByRaw('(select min(mp.price) from merchant_items mp where mp.catalog_item_id = catalog_items.id and mp.user_id = ? and mp.status = 1) desc', [$merchant->id]);
            } elseif ($sort === 'price_asc') {
                // Sort by lowest merchant price for this catalogItem (for current merchant)
                return $query->orderByRaw('(select min(mp.price) from merchant_items mp where mp.catalog_item_id = catalog_items.id and mp.user_id = ? and mp.status = 1) asc', [$merchant->id]);
            }
        });

        // Default sort if not specified
        if (empty($sort)) {
            $prods = $prods->latest('catalog_items.id');
        }

        // Load reviews
        $prods = $prods->withCount('catalogReviews')
                       ->withAvg('catalogReviews', 'rating');

        // Pagination
        $perPage = isset($pageby) ? (int) $pageby : (int) $this->gs->merchant_page_count;
        $prods   = $prods->paginate($perPage);

        // Set display price to be this merchant's price specifically
        // Use preloaded relation instead of new query (avoid N+1)
        $prods->getCollection()->transform(function ($item) use ($merchant) {
            // Use preloaded relation
            $mp = $item->merchantItems->first();

            if ($mp) {
                // Store merchant item for use in view
                $item->merchant_merchant_item = $mp;

                // Use merchant price calculation function from MerchantItem
                if (method_exists($mp, 'merchantSizePrice')) {
                    $item->price = $mp->merchantSizePrice();
                } else {
                    // Simple fallback if function not available
                    $item->price = $mp->price;
                }
            } else {
                $item->merchant_merchant_item = null;
                $item->price = null;
            }
            return $item;
        });

        $data['vprods'] = $prods;

        if ($request->ajax()) {
            $data['ajax_check'] = 1;
            return view('frontend.ajax.merchant', $data);
        }

        return view('frontend.merchant', $data);
    }

    //Send email to user
    public function merchantcontact(Request $request)
    {
        $gs     = $this->gs;
        $user   = User::findOrFail($request->user_id);
        $merchant = User::findOrFail($request->merchant_id);

        $subject = $request->subject;
        $to      = $merchant->email;
        $name    = $request->name;
        $from    = $request->email;
        $msg     = "Name: " . $name . "\nEmail: " . $from . "\nMessage: " . $request->message;

        if ($gs->is_smtp) {
            $data = [
                'to'      => $to,
                'subject' => $subject,
                'body'    => $msg,
            ];

            $mailer = new MuaadhMailer();
            $mailer->sendCustomMail($data);
        } else {
            $headers = "From: " . $gs->from_name . "<" . $gs->from_email . ">";
            mail($to, $subject, $msg, $headers);
        }

        $conv = ChatThread::where('sent_user', '=', $user->id)->where('subject', '=', $subject)->first();
        if (isset($conv)) {
            $msg = new ChatEntry();
            $msg->chat_thread_id = $conv->id;
            $msg->message         = $request->message;
            $msg->sent_user       = $user->id;
            $msg->save();
            return response()->json(__('Message Sent!'));
        } else {
            $message                 = new ChatThread();
            $message->subject        = $subject;
            $message->sent_user      = $request->user_id;
            $message->recieved_user  = $request->merchant_id;
            $message->message        = $request->message;
            $message->save();

            $msg = new ChatEntry();
            $msg->chat_thread_id = $message->id;
            $msg->message         = $request->message;
            $msg->sent_user       = $request->user_id;
            $msg->save();
            return response()->json(__('Message Sent!'));
        }
    }
}
