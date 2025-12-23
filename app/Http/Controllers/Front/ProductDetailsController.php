<?php

namespace App\Http\Controllers\Front;

use App\Helpers\ProductContextHelper;
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
     * ==========================================================================
     * STRICT MERCHANT PRODUCT ROUTE
     * ==========================================================================
     * /item/{slug}/store/{vendor_id}/merchant_products/{merchant_product_id}
     *
     * SECURITY RULES:
     * 1. MerchantProduct MUST exist and be active
     * 2. vendor_id MUST match merchant_product.user_id (STRICT - no fallback)
     * 3. Product data is READONLY - price/stock/qty come ONLY from MerchantProduct
     * 4. NO best_merchant_product or merchantProducts()->first() allowed
     * ==========================================================================
     */
    public function showByMerchantProduct(Request $request, $slug, $vendor_id = null, $merchant_product_id = null)
    {
        $gs = $this->gs;

        // ======================================================================
        // STEP 1: PARSE ROUTE PARAMETERS
        // ======================================================================
        $routeParams = $request->route()->parameters();

        // Handle old short route format: /item/{slug}/{merchant_product_id}
        if (count($routeParams) == 2) {
            $merchant_product_id = $vendor_id;
            $vendor_id = null;
        }

        // ======================================================================
        // STEP 2: STRICT GUARD - MerchantProduct MUST exist
        // ======================================================================
        $merchantProduct = MerchantProduct::with([
            'user',
            'qualityBrand',
            'product.galleries',
            'product.brand',
            'product.category',
            'product.subcategory',
        ])->find($merchant_product_id);

        if (!$merchantProduct) {
            abort(404, 'Merchant product not found');
        }

        // ======================================================================
        // STEP 3: STRICT GUARD - Vendor ID MUST match (when provided)
        // ======================================================================
        if ($vendor_id !== null) {
            $vendor_id = (int) $vendor_id;
            $actualVendorId = (int) $merchantProduct->user_id;

            if ($vendor_id !== $actualVendorId) {
                // Log security violation attempt
                \Log::warning('ProductDetails: vendor_id mismatch', [
                    'requested_vendor_id' => $vendor_id,
                    'actual_vendor_id' => $actualVendorId,
                    'merchant_product_id' => $merchant_product_id,
                    'ip' => $request->ip(),
                ]);

                abort(403, 'Vendor ID does not match merchant product owner');
            }
        }

        // ======================================================================
        // STEP 4: STRICT GUARD - MerchantProduct MUST be active
        // ======================================================================
        if ((int) $merchantProduct->status !== 1) {
            abort(404, 'Merchant product is not active');
        }

        // ======================================================================
        // STEP 5: STRICT GUARD - Vendor MUST be active (is_vendor = 2)
        // ======================================================================
        if (!$merchantProduct->user || (int) $merchantProduct->user->is_vendor !== 2) {
            abort(404, 'Vendor is not active');
        }

        // ======================================================================
        // STEP 6: Get Product (READONLY - catalog data only)
        // ======================================================================
        $productt = $merchantProduct->product;

        if (!$productt) {
            abort(404, 'Product not found for this merchant listing');
        }

        // Load ratings (catalog-level data) - count, avg, and full list for reviews
        $productt->loadCount('ratings');
        $productt->loadAvg('ratings', 'rating');
        $productt->load(['ratings' => fn($q) => $q->with('user')->orderBy('review_date', 'desc')]);

        // ======================================================================
        // STEP 7: Verify slug matches (SEO redirect if changed)
        // ======================================================================
        if ($productt->slug !== $slug) {
            return redirect()->route('front.product', [
                'slug' => $productt->slug,
                'vendor_id' => $merchantProduct->user_id,
                'merchant_product_id' => $merchantProduct->id
            ], 301);
        }

        // ======================================================================
        // STEP 8: Affiliate tracking (optional)
        // ======================================================================
        $affilate_user = 0;
        if ($gs->product_affilate == 1 && $request->has('ref') && !empty($request->ref)) {
            $userRef = User::where('affilate_code', $request->ref)->first();
            if ($userRef && (!Auth::check() || Auth::id() != $userRef->id)) {
                $affilate_user = $userRef->id;
            }
        }

        // ======================================================================
        // STEP 9: Other sellers (same product, different vendors)
        // ======================================================================
        $otherSellers = MerchantProduct::query()
            ->where('product_id', $productt->id)
            ->where('status', 1)
            ->where('id', '<>', $merchantProduct->id)
            ->whereHas('user', fn($q) => $q->where('is_vendor', 2))
            ->with(['user', 'qualityBrand'])
            ->orderBy('price')
            ->get();

        // ======================================================================
        // STEP 10: Other listings from same vendor
        // ======================================================================
        $vendorListings = MerchantProduct::query()
            ->where('user_id', $merchantProduct->user_id)
            ->where('status', 1)
            ->where('id', '<>', $merchantProduct->id)
            ->with(['product' => fn($q) => $q->withCount('ratings')->withAvg('ratings', 'rating'), 'user', 'qualityBrand'])
            ->take(12)
            ->get();

        // ======================================================================
        // STEP 10b: Related Products (same type/product_type, different product)
        // Optimized: Query MerchantProduct directly with product filters
        // ======================================================================
        // product_type is now on merchant_products, not products
        $relatedMerchantProducts = MerchantProduct::where('status', 1)
            ->where('stock', '>', 0)
            ->where('product_type', $merchantProduct->product_type)
            ->whereHas('product', function($q) use ($productt) {
                $q->where('type', $productt->type)
                  ->where('id', '!=', $productt->id);
            })
            ->whereHas('user', fn($q) => $q->where('is_vendor', 2))
            ->with(['product' => fn($q) => $q->withCount('ratings')->withAvg('ratings', 'rating'), 'user', 'qualityBrand'])
            ->limit(50) // Get more than needed, then shuffle in PHP
            ->get()
            ->shuffle() // Randomize in PHP (much faster than MySQL ORDER BY RAND())
            ->take(12); // Take only 12

        // ======================================================================
        // STEP 11: Track product click
        // ======================================================================
        if (!session()->has('click_' . $productt->id)) {
            ProductClick::create([
                'product_id' => $productt->id,
                'date' => Carbon::now()->format('Y-m-d'),
                'clicks' => 1,
            ]);
            session()->put('click_' . $productt->id, 1);
        }

        // ======================================================================
        // STEP 12: PASS TO VIEW
        // $merchant is the AUTHORITATIVE source for price/stock/qty
        // $productt is READONLY catalog data (name, description, images)
        // ======================================================================
        $merchant = $merchantProduct;
        $vendorId = $merchantProduct->user_id;

        return view('frontend.product', compact(
            'productt',               // Product (READONLY - catalog only)
            'merchant',               // MerchantProduct (AUTHORITATIVE - price/stock/qty)
            'vendorId',               // Verified vendor ID
            'otherSellers',           // Alternative sellers
            'vendorListings',         // More from this vendor
            'relatedMerchantProducts', // Related products (pre-loaded, optimized)
            'affilate_user',          // Affiliate tracking
            'gs'                      // General settings
        ));
    }

    /**
     * Legacy method: /item/{slug}/{user}
     * Pick deterministic default mp for that user, then redirect to front.product
     */
    public function showByUser($slug, $user)
    {
        // Load product by slug
        $productt = Product::where('slug', $slug)->first();
        if (!$productt) {
            return response()->view('errors.404')->setStatusCode(404);
        }

        // Find merchant product for this user (in-stock then lowest price)
        $merchantProduct = MerchantProduct::where('product_id', $productt->id)
            ->where('user_id', $user)
            ->where('status', 1)
            ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
            ->orderBy('price')
            ->first();

        if (!$merchantProduct) {
            return response()->view('errors.404')->setStatusCode(404);
        }

        // Redirect to new preferred route
        return redirect()->route('front.product', [
            'slug' => $slug,
            'vendor_id' => $merchantProduct->user_id,
            'merchant_product_id' => $merchantProduct->id
        ], 302);
    }

    /**
     * Very legacy method: /item/{slug}
     * Resolve default mp globally, then redirect to front.product
     */
    public function show($slug)
    {
        // Load product by slug
        $productt = Product::where('slug', $slug)->first();
        if (!$productt) {
            return response()->view('errors.404')->setStatusCode(404);
        }

        // Find best merchant product globally (in-stock then lowest price)
        $merchantProduct = MerchantProduct::where('product_id', $productt->id)
            ->where('status', 1)
            ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
            ->orderBy('price')
            ->first();

        if (!$merchantProduct) {
            return response()->view('errors.404')->setStatusCode(404);
        }

        // Redirect to new preferred route
        return redirect()->route('front.product', [
            'slug' => $slug,
            'vendor_id' => $merchantProduct->user_id,
            'merchant_product_id' => $merchantProduct->id
        ], 302);
    }

    /**
     * Optional alternative: /item/{slug}/{user}/{brand_quality_id}
     */
    public function showByUserQuality($slug, $user, $brand_quality_id)
    {
        // Load product by slug
        $productt = Product::where('slug', $slug)->first();
        if (!$productt) {
            return response()->view('errors.404')->setStatusCode(404);
        }

        // Find merchant product by user and brand quality
        $merchantProduct = MerchantProduct::where('product_id', $productt->id)
            ->where('user_id', $user)
            ->where('brand_quality_id', $brand_quality_id)
            ->where('status', 1)
            ->first();

        if (!$merchantProduct) {
            return response()->view('errors.404')->setStatusCode(404);
        }

        // Redirect to new preferred route
        return redirect()->route('front.product', [
            'slug' => $slug,
            'vendor_id' => $merchantProduct->user_id,
            'merchant_product_id' => $merchantProduct->id
        ], 302);
    }

    // public function quickFragment(int $id)
    // {
    //     $product = \App\Models\Product::findOrFail($id);
    //     return response()->view('quick', compact('product'));
    // }
    public function quickFragment(int $id)
    {
        $product = \App\Models\Product::findOrFail($id);
        $mp = null;

        // البائع من ?user=
        $vendorId = (int) request()->query('user', 0);
        if ($vendorId > 0) {
            $mp = \App\Models\MerchantProduct::with(['qualityBrand', 'user'])
                ->where('product_id', $product->id)
                ->where('user_id', $vendorId)
                ->first();

            if ($mp) {
                // Use ProductContextHelper for consistency
                ProductContextHelper::apply($product, $mp);
            }
        }

        // جلب البراند من المنتج
        $brand = null;
        if ($product->brand_id) {
            $brand = \App\Models\Brand::find($product->brand_id);
        }

        return response()->view('partials.product', compact('product', 'mp', 'brand'));
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

        // affiliate_link is now on merchant_products, not products
        $merchantProduct = $product->merchantProducts()
            ->whereNotNull('affiliate_link')
            ->first();

        if ($merchantProduct && $merchantProduct->affiliate_link) {
            return redirect($merchantProduct->affiliate_link);
        }

        // Fallback to product page if no affiliate link found
        return redirect()->route('front.product', $product->slug);
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
