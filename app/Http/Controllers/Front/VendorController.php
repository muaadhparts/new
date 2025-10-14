<?php

namespace App\Http\Controllers\Front;

use App\{
    Models\User,
    Models\Message,
    Models\Product,
    Classes\MuaadhMailer,
    Models\Conversation
};
use App\Models\Category;
use Illuminate\{
    Http\Request,
    Support\Facades\DB
};

class VendorController extends FrontBaseController
{
    public function index(Request $request, $slug)
    {
        $minprice = $request->min;
        $maxprice = $request->max;
        $sort     = $request->sort;
        $pageby   = $request->pageby;

        $string = str_replace('-', ' ', $slug);
        $vendor = User::where('shop_name', '=', $string)->first();

        // إذا لم يوجد بائع بالـ slug، نحاول صفحة ثابتة أو 404
        if (empty($vendor)) {
            $page = DB::table('pages')->where('slug', $slug)->first();
            if (empty($page)) {
                return response()->view('errors.404', [], 404);
            }
            return view('frontend.page', compact('page'));
        }

        $data['vendor']     = $vendor;
        $data['services']   = DB::table('services')->where('user_id', '=', $vendor->id)->get();
        $data['categories'] = Category::where('status', 1)->get();

        // أحدث المنتجات: مبنية على merchant_products (نشط + البائع مفعل)
        $data['latest_products'] = Product::status(1)             // يعتمد على merchant_products.status
            ->whereLatest(1)                                      // نفس الحقل السابق على products
            ->whereHas('merchantProducts', function ($q) {
                $q->where('status', 1)
                  ->whereHas('user', function ($u) {
                      $u->where('is_vendor', 2);
                  });
            })
            ->with(['merchantProducts' => function ($q) {
                $q->where('status', 1)->with('user:id,is_vendor');
            }])
            ->withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->latest('products.id')
            ->take(5)
            ->get();

        // بناء استعلام منتجات البائع مع فلاتر السعر والخصم والفرز
        $prods = Product::query();

        // فلترة بالسعر والخصم والمزوّد المحدد عبر merchant_products
        $prods = $prods->whereHas('merchantProducts', function ($q) use ($vendor, $minprice, $maxprice, $request) {
            $q->where('user_id', $vendor->id)
              ->where('status', 1);

            if (!is_null($minprice) && $minprice !== '') {
                $q->where('price', '>=', $minprice);
            }
            if (!is_null($maxprice) && $maxprice !== '') {
                $q->where('price', '<=', $maxprice);
            }

            // فلترة الخصم (type) من merchant_products
            if ($request->has('type')) {
                $q->where('is_discount', 1)
                  ->where('discount_date', '>=', date('Y-m-d'));
            }
        });

        // فرز النتائج
        $prods = $prods->when($sort, function ($query, $sort) use ($vendor) {
            if ($sort === 'date_desc') {
                return $query->latest('products.id');
            } elseif ($sort === 'date_asc') {
                return $query->oldest('products.id');
            } elseif ($sort === 'price_desc') {
                // الترتيب بأعلى سعر بائع لهذا المنتج (للبائع الحالي)
                return $query->orderByRaw('(select min(mp.price) from merchant_products mp where mp.product_id = products.id and mp.user_id = ? and mp.status = 1) desc', [$vendor->id]);
            } elseif ($sort === 'price_asc') {
                // الترتيب بأقل سعر بائع لهذا المنتج (للبائع الحالي)
                return $query->orderByRaw('(select min(mp.price) from merchant_products mp where mp.product_id = products.id and mp.user_id = ? and mp.status = 1) asc', [$vendor->id]);
            }
        });

        // الفرز الافتراضي إن لم يحدد
        if (empty($sort)) {
            $prods = $prods->latest('products.id');
        }

        // تحميل تقييمات
        $prods = $prods->withCount('ratings')
                       ->withAvg('ratings', 'rating');

        // ترقيم الصفحات
        $perPage = isset($pageby) ? (int) $pageby : (int) $this->gs->vendor_page_count;
        $prods   = $prods->paginate($perPage);

        // ضبط السعر الظاهر في القائمة ليكون سعر هذا البائع تحديداً
        $prods->getCollection()->transform(function ($item) use ($vendor) {
            $mp = $item->merchantProducts()
                       ->where('user_id', $vendor->id)
                       ->where('status', 1)
                       ->first();
            if ($mp) {
                // استخدام دالة حساب سعر التاجر من MerchantProduct
                if (method_exists($mp, 'vendorSizePrice')) {
                    $item->price = $mp->vendorSizePrice();
                } else {
                    // fallback بسيط إن لم تتوفر الدالة لأي سبب
                    $item->price = $mp->price;
                }
            } else {
                $item->price = null;
            }
            return $item;
        });

        $data['vprods'] = $prods;

        if ($request->ajax()) {
            $data['ajax_check'] = 1;
            return view('frontend.ajax.vendor', $data);
        }

        return view('frontend.vendor', $data);
    }

    //Send email to user
    public function vendorcontact(Request $request)
    {
        $gs     = $this->gs;
        $user   = User::findOrFail($request->user_id);
        $vendor = User::findOrFail($request->vendor_id);

        $subject = $request->subject;
        $to      = $vendor->email;
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

        $conv = Conversation::where('sent_user', '=', $user->id)->where('subject', '=', $subject)->first();
        if (isset($conv)) {
            $msg = new Message();
            $msg->conversation_id = $conv->id;
            $msg->message         = $request->message;
            $msg->sent_user       = $user->id;
            $msg->save();
            return response()->json(__('Message Sent!'));
        } else {
            $message                 = new Conversation();
            $message->subject        = $subject;
            $message->sent_user      = $request->user_id;
            $message->recieved_user  = $request->vendor_id;
            $message->message        = $request->message;
            $message->save();

            $msg = new Message();
            $msg->conversation_id = $message->id;
            $msg->message         = $request->message;
            $msg->sent_user       = $request->user_id;
            $msg->save();
            return response()->json(__('Message Sent!'));
        }
    }
}
