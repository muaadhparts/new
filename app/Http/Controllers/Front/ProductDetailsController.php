<?php

namespace App\Http\Controllers\Front;

use App\Models\Comment;
use App\Models\Order;
use App\Models\Product;
use App\Models\MerchantProduct;
use App\Models\ProductClick;
use App\Models\Rating;
use App\Models\Reply;
use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductDetailsController extends FrontBaseController
{
    /**
     * صفحة تفاصيل المنتج: /item/{slug}/{user}
     * تربط المنتج بالبائع عبر merchant_products وليس عبر products.user_id.
     */
    public function product(Request $request, $slug, $user)
    {
        $affilate_user = 0;
        $gs = $this->gs;

        // منطق الأفلييت كما هو
        if ($gs->product_affilate == 1 && $request->has('ref') && !empty($request->ref)) {
            $ref = $request->ref;
            $userRef = User::where('affilate_code', $ref)->first();
            if ($userRef) {
                if (Auth::check() && Auth::id() != $userRef->id) {
                    $affilate_user = $userRef->id;
                } elseif (!Auth::check()) {
                    $affilate_user = $userRef->id;
                }
            }
        }

        // 1) هوية المنتج من الـ slug
        $productt = Product::with(['galleries'])
            ->withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->where('slug', $slug)
            ->first();

        // If no product found, return 404
        if (!$productt) {
            return response()->view('errors.404')->setStatusCode(404);
        }

        // 2) التأكد من {user}
        $userId = (int) $user;
        if ($userId <= 0) {
            // ليس لدينا بائع صالح، نحاول تحويل المستخدم لأقرب بائع فعّال
            $fallback = MerchantProduct::where('product_id', $productt->id)
                ->where('status', 1)
                ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
                ->orderBy('price')
                ->first();

            return $fallback
                ? redirect()->route('front.product', ['slug' => $slug, 'user' => $fallback->user_id])
                : response()->view('errors.404')->setStatusCode(404);
        }

        // 3) عرض البائع لهذا المنتج (لا تستخدم firstOrFail لتفادي الاستثناء)
        $merchantProduct = MerchantProduct::where('product_id', $productt->id)
            ->where('user_id', $userId)
            ->first();

        // إن لم يوجد أو غير مفعّل → إعادة توجيه لأقرب بائع فعّال إن وُجد، وإلا 404
        if (!$merchantProduct || (int) $merchantProduct->status !== 1) {
            $fallback = MerchantProduct::where('product_id', $productt->id)
                ->where('status', 1)
                ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
                ->orderBy('price')
                ->first();

            return $fallback
                ? redirect()->route('front.product', ['slug' => $slug, 'user' => $fallback->user_id])
                : response()->view('errors.404')->setStatusCode(404);
        }

        // 4) بائعون آخرون لنفس المنتج (غير المختار)
        $otherSellers = MerchantProduct::query()
            ->where('product_id', $productt->id)
            ->where('status', 1)
            ->where('user_id', '<>', $merchantProduct->user_id)
            ->with('user:id,shop_name,is_vendor')
            ->get();

        // 5) منتجات أخرى لنفس البائع (عروض أخرى غير هذا المنتج)
        $vendorListings = MerchantProduct::query()
            ->where('user_id', $merchantProduct->user_id)
            ->where('status', 1)
            ->where('product_id', '<>', $productt->id)
            ->with(['product' => function ($q) {
                $q->withCount('ratings')->withAvg('ratings', 'rating');
            }])
            ->latest('id')
            ->take(9)
            ->get();

        // مصفوفة Products لتوافق القوالب القديمة
        $vendor_products = $vendorListings->pluck('product');

        // 6) زيادة المشاهدات وتسجيل النقرات كما هو
        $productt->increment('views');

        $product_click = new ProductClick;
        $product_click->product_id = $productt->id;
        $product_click->date = Carbon::now()->format('Y-m-d');
        $product_click->save();

        $curr = $this->curr;

        // ملاحظة: في الـ Blade، السعر/المخزون يُقرأ من merchant (عرض البائع)،
        // بينما الهوية/الصور/التقييمات من productt.
        return view('frontend.product', [
            'productt'        => $productt,
            'curr'            => $curr,
            'affilate_user'   => $affilate_user,
            'vendor_products' => $vendor_products,

            // مُضاف حديثًا للسياسة الجديدة:
            'merchant'        => $merchantProduct,      // عرض البائع الحالي
            'vendorId'        => $merchantProduct->user_id,
            'otherSellers'    => $otherSellers,
            'vendorListings'  => $vendorListings,
        ]);
    }

    // public function quickFragment(int $id)
    // {
    //     $product = \App\Models\Product::findOrFail($id);
    //     return response()->view('quick', compact('product'));
    // }
    public function quickFragment(int $id)
    {
        $product = \App\Models\Product::findOrFail($id);

        // البائع من ?user=
        $vendorId = (int) request()->query('user', 0);
        if ($vendorId > 0) {
            $mp = \App\Models\MerchantProduct::where('product_id', $product->id)
                ->where('user_id', $vendorId)
                ->first();

            if ($mp) {
                $effectivePrice = method_exists($mp, 'vendorSizePrice')
                    ? (float) $mp->vendorSizePrice()
                    : (float) $mp->price;

                $product->setAttribute('vendor_user_id', $mp->user_id);
                $product->setAttribute('price', $effectivePrice);

                if (isset($mp->previous_price)) $product->setAttribute('previous_price', $mp->previous_price);
                if (isset($mp->stock))          $product->setAttribute('stock', $mp->stock);

                foreach (['size','size_qty','size_price','license','license_qty'] as $f) {
                    if (isset($mp->{$f})) $product->setAttribute($f, $mp->{$f});
                }
            }

        }

        return response()->view('partials.product', compact('product'));
    }


    public function productFragment(string $key)
    {
        $product = \App\Models\Product::where('sku', $key)->first()
                ?: \App\Models\Product::where('slug', $key)->firstOrFail();

        return response()->view('partials.product', compact('product'));
    }

    public function compatibilityFragment(string $key)
    {
        $sku = $key;
        return response()->view('partials.compatibility', compact('sku'));
    }

    public function alternativeFragment(string $key)
    {
        $sku = $key;
        return response()->view('partials.alternative', compact('sku'));
    }

    public function report(Request $request)
    {
        $rules = ['note' => 'max:400'];
        $customs = ['note.max' => __('Note Must Be Less Than 400 Characters.')];
        $validator = Validator::make($request->all(), $rules, $customs);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->getMessageBag()->toArray()]);
        }

        $data = new Report;
        $data->fill($request->all())->save();
        return response()->json(__('Report Sent Successfully.'));
    }

    public function quick($id)
    {
        $product = Product::findOrFail($id);
        $curr = $this->curr;
        return view('load.quick', compact('product', 'curr'));
    }

    public function affProductRedirect($slug)
    {
        $product = Product::where('slug', '=', $slug)->first();
        return redirect($product->affiliate_link);
    }

    // -------------------------------- PRODUCT COMMENT SECTION ----------------------------------------

    public function comment(Request $request)
    {
        $comment = new Comment;
        $comment->fill($request->all())->save();

        $data[0] = $comment->user->photo ? url('assets/images/users/' . $comment->user->photo) : url('assets/images/' . $this->gs->user_image);
        $data[1] = $comment->user->name;
        $data[2] = $comment->created_at->diffForHumans();
        $data[3] = $comment->text;
        $data[5] = route('product.comment.delete', $comment->id);
        $data[6] = route('product.comment.edit', $comment->id);
        $data[7] = route('product.reply', $comment->id);
        $data[8] = $comment->user->id;

        $newdata = '<li>';
        $newdata .= '<div class="single-comment comment-section">';
        $newdata .= '<div class="left-area"><img src="' . $data[0] . '" alt=""><h5 class="name">' . $data[1] . '</h5><p class="date">' . $data[2] . '</p></div>';
        $newdata .= '<div class="right-area"><div class="comment-body"><p>' . $data[3] . '</p></div>';
        $newdata .= '<div class="comment-footer"><div class="links">';
        $newdata .= '<a href="javascript:;" class="comment-link reply mr-2"><i class="fas fa-reply "></i>' . __('Reply') . '</a>';
        $newdata .= '<a href="javascript:;" class="comment-link edit mr-2"><i class="fas fa-edit "></i>' . __('Edit') . '</a>';
        $newdata .= '<a href="javascript:;" data-href="' . $data[5] . '" class="comment-link comment-delete mr-2"><i class="fas fa-trash"></i>' . __('Delete') . '</a>';
        $newdata .= '</div></div></div></div>';
        $newdata .= '<div class="replay-area edit-area d-none"><form class="update" action="' . $data[6] . '" method="POST">' . csrf_field() . '<textarea placeholder="' . __('Edit Your Comment') . '" name="text" required=""></textarea><button type="submit">' . __('Submit') . '</button><a href="javascript:;" class="remove">' . __('Cancel') . '</a></form></div>';
        $newdata .= '<div class="replay-area reply-reply-area d-none"><form class="reply-form" action="' . $data[7] . '" method="POST"><input type="hidden" name="user_id" value="' . $data[8] . '">' . csrf_field() . '<textarea placeholder="' . __('Write Your Reply') . '" name="text" required=""></textarea><button type="submit">' . __('Submit') . '</button><a href="javascript:;" class="remove">' . __('Cancel') . '</a></form></div>';
        $newdata .= '</li>';

        return response()->json($newdata);
    }

    public function commentedit(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);
        $comment->text = $request->text;
        $comment->save();

        return response()->json($comment->text);
    }

    public function commentdelete($id)
    {
        $comment = Comment::findOrFail($id);
        if ($comment->replies->count() > 0) {
            foreach ($comment->replies as $reply) { $reply->delete(); }
        }
        $comment->delete();
    }

    // -------------------------------- PRODUCT REPLY SECTION ----------------------------------------

    public function reply(Request $request, $id)
    {
        $reply = new Reply;
        $data = $request->all();
        $data['comment_id'] = $id;
        $reply->fill($data)->save();

        $resp[0] = $reply->user->photo ? url('assets/images/users/' . $reply->user->photo) : url('assets/images/' . $this->gs->user_image);
        $resp[1] = $reply->user->name;
        $resp[2] = $reply->created_at->diffForHumans();
        $resp[3] = $reply->text;
        $resp[4] = route('product.reply.delete', $reply->id);
        $resp[5] = route('product.reply.edit', $reply->id);

        $newdata = '<div class="single-comment replay-review"><div class="left-area"><img src="' . $resp[0] . '" alt=""><h5 class="name">' . $resp[1] . '</h5><p class="date">' . $resp[2] . '</p></div>';
        $newdata .= '<div class="right-area"><div class="comment-body"><p>' . $resp[3] . '</p></div><div class="comment-footer"><div class="links">';
        $newdata .= '<a href="javascript:;" class="comment-link reply mr-2"><i class="fas fa-reply "></i>' . __('Reply') . '</a>';
        $newdata .= '<a href="javascript:;" class="comment-link edit mr-2"><i class="fas fa-edit "></i>' . __('Edit') . '</a>';
        $newdata .= '<a href="javascript:;" data-href="' . $resp[4] . '" class="comment-link reply-delete mr-2"><i class="fas fa-trash"></i>' . __('Delete') . '</a>';
        $newdata .= '</div></div></div></div>';
        $newdata .= '<div class="replay-area edit-area d-none"><form class="update" action="' . $resp[5] . '" method="POST">' . csrf_field() . '<textarea placeholder="' . __('Edit Your Reply') . '" name="text" required=""></textarea><button type="submit">' . __('Submit') . '</button><a href="javascript:;" class="remove">' . __('Cancel') . '</a></form></div>';

        return response()->json($newdata);
    }

    public function replyedit(Request $request, $id)
    {
        $reply = Reply::findOrFail($id);
        $reply->text = $request->text;
        $reply->save();
        return response()->json($reply->text);
    }

    public function replydelete($id)
    {
        $reply = Reply::findOrFail($id);
        $reply->delete();
    }

    // ------------------ Rating SECTION --------------------

    public function reviewsubmit(Request $request)
    {
        $ck = 0;
        $orders = Order::where('user_id', $request->user_id)->where('status', 'completed')->get();

        foreach ($orders as $order) {
            $cart = json_decode($order->cart, true);
            foreach ($cart['items'] as $product) {
                if ($request->product_id == $product['item']['id']) { $ck = 1; break; }
            }
        }

        if ($ck == 1) {
            $user = Auth::user();
            $prev = Rating::where('product_id', $request->product_id)->where('user_id', $user->id)->first();
            $payload = $request->all();
            $payload['review_date'] = date('Y-m-d H:i:s');

            if ($prev) {
                $prev->update($payload);
            } else {
                $rating = new Rating;
                $rating->fill($payload);
                $rating['review_date'] = date('Y-m-d H:i:s');
                $rating->save();
            }
            return back()->with('success', __('Your Rating Submitted Successfully.'));
        }

        return back()->with('unsuccess', __('You did not purchase this product.'));
    }

    public function reviews($id)
    {
        $productt = Product::find($id);
        return view('load.reviews', compact('productt', 'id'));
    }

    public function sideReviews($id)
    {
        $productt = Product::find($id);
        return view('load.side-load', compact('productt'));
    }

    public function showCrossProduct($id)
    {
        $product = Product::findOrFail($id);
        $cross_ids = array_filter(explode(',', (string) $product->cross_products));
        $cross_products = Product::whereIn('id', $cross_ids)
            ->withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->get();

        return view('includes.cross_product', compact('cross_products'));
    }
}
