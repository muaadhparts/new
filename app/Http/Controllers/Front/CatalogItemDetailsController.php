<?php

namespace App\Http\Controllers\Front;

use App\Helpers\CatalogItemContextHelper;
use App\Models\Comment;
use App\Models\Purchase;
use App\Models\CatalogItem;
use App\Models\MerchantItem;
use App\Models\CatalogItemClick;
use App\Models\CatalogReview;
use App\Models\Reply;
use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CatalogItemDetailsController extends FrontBaseController
{
    /**
     * ==========================================================================
     * STRICT MERCHANT ITEM ROUTE
     * ==========================================================================
     * /item/{slug}/store/{vendor_id}/merchant_items/{merchant_item_id}
     *
     * SECURITY RULES:
     * 1. MerchantItem MUST exist and be active
     * 2. vendor_id MUST match merchant_item.user_id (STRICT - no fallback)
     * 3. CatalogItem data is READONLY - price/stock/qty come ONLY from MerchantItem
     * 4. NO best_merchant_item or merchantItems()->first() allowed
     * ==========================================================================
     */
    public function showByMerchantItem(Request $request, $slug, $merchant_id = null, $merchant_item_id = null)
    {
        $gs = $this->gs;

        // ======================================================================
        // STEP 1: PARSE ROUTE PARAMETERS
        // ======================================================================
        $routeParams = $request->route()->parameters();

        // Handle old short route format: /item/{slug}/{merchant_item_id}
        if (count($routeParams) == 2) {
            $merchant_item_id = $merchant_id;
            $merchant_id = null;
        }

        // ======================================================================
        // STEP 2: STRICT GUARD - MerchantItem MUST exist
        // ======================================================================
        $merchantItem = MerchantItem::with([
            'user',
            'qualityBrand',
            'catalogItem.galleries',
            'catalogItem.brand',
        ])->find($merchant_item_id);

        if (!$merchantItem) {
            abort(404, 'Merchant item not found');
        }

        // ======================================================================
        // STEP 3: STRICT GUARD - Vendor ID MUST match (when provided)
        // ======================================================================
        if ($merchant_id !== null) {
            $merchant_id = (int) $merchant_id;
            $actualVendorId = (int) $merchantItem->user_id;

            if ($merchant_id !== $actualVendorId) {
                // Log security violation attempt
                \Log::warning('CatalogItemDetails: vendor_id mismatch', [
                    'requested_vendor_id' => $merchant_id,
                    'actual_vendor_id' => $actualVendorId,
                    'merchant_item_id' => $merchant_item_id,
                    'ip' => $request->ip(),
                ]);

                abort(403, 'Vendor ID does not match merchant item owner');
            }
        }

        // ======================================================================
        // STEP 4: STRICT GUARD - MerchantItem MUST be active
        // ======================================================================
        if ((int) $merchantItem->status !== 1) {
            abort(404, 'Merchant item is not active');
        }

        // ======================================================================
        // STEP 5: STRICT GUARD - Vendor MUST be active (is_merchant = 2)
        // ======================================================================
        if (!$merchantItem->user || (int) $merchantItem->user->is_merchant !== 2) {
            abort(404, 'Vendor is not active');
        }

        // ======================================================================
        // STEP 6: Get CatalogItem (READONLY - catalog data only)
        // ======================================================================
        $productt = $merchantItem->catalogItem;

        if (!$productt) {
            abort(404, 'Catalog item not found for this merchant listing');
        }

        // Load catalogReviews (catalog-level data) - count, avg, and full list for reviews
        $productt->loadCount('catalogReviews');
        $productt->loadAvg('catalogReviews', 'rating');
        $productt->load(['catalogReviews' => fn($q) => $q->with('user')->orderBy('review_date', 'desc')]);

        // ======================================================================
        // STEP 7: Verify slug matches (SEO redirect if changed)
        // ======================================================================
        if ($productt->slug !== $slug) {
            return redirect()->route('front.catalog-item', [
                'slug' => $productt->slug,
                'merchant_id' => $merchantItem->user_id,
                'merchant_item_id' => $merchantItem->id
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
        // STEP 9: Other sellers (same catalog item, different vendors)
        // ======================================================================
        $otherSellers = MerchantItem::query()
            ->where('catalog_item_id', $productt->id)
            ->where('status', 1)
            ->where('id', '<>', $merchantItem->id)
            ->whereHas('user', fn($q) => $q->where('is_merchant', 2))
            ->with(['user', 'qualityBrand'])
            ->orderBy('price')
            ->get();

        // ======================================================================
        // STEP 10: Other listings from same vendor
        // ======================================================================
        $vendorListings = MerchantItem::query()
            ->where('user_id', $merchantItem->user_id)
            ->where('status', 1)
            ->where('id', '<>', $merchantItem->id)
            ->with(['catalogItem' => fn($q) => $q->withCount('catalogReviews')->withAvg('catalogReviews', 'rating'), 'user', 'qualityBrand'])
            ->take(12)
            ->get();

        // ======================================================================
        // STEP 10b: Related Items (same type/product_type, different catalog item)
        // Optimized: Query MerchantItem directly with catalog item filters
        // ======================================================================
        // product_type is now on merchant_items, not catalog_items
        $relatedMerchantItems = MerchantItem::where('status', 1)
            ->where('stock', '>', 0)
            ->where('product_type', $merchantItem->product_type)
            ->whereHas('catalogItem', function($q) use ($productt) {
                $q->where('type', $productt->type)
                  ->where('id', '!=', $productt->id);
            })
            ->whereHas('user', fn($q) => $q->where('is_merchant', 2))
            ->with(['catalogItem' => fn($q) => $q->withCount('catalogReviews')->withAvg('catalogReviews', 'rating'), 'user', 'qualityBrand'])
            ->limit(50) // Get more than needed, then shuffle in PHP
            ->get()
            ->shuffle() // Randomize in PHP (much faster than MySQL ORDER BY RAND())
            ->take(12); // Take only 12

        // ======================================================================
        // STEP 11: Track catalog item click
        // ======================================================================
        if (!session()->has('click_' . $productt->id)) {
            CatalogItemClick::create([
                'catalog_item_id' => $productt->id,
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
        $merchant = $merchantItem;
        $merchantId = $merchantItem->user_id;

        return view('frontend.catalog-item', compact(
            'productt',               // CatalogItem (READONLY - catalog only)
            'merchant',               // MerchantItem (AUTHORITATIVE - price/stock/qty)
            'merchantId',             // Verified merchant user ID
            'otherSellers',           // Alternative sellers
            'vendorListings',         // More from this vendor
            'relatedMerchantItems',   // Related items (pre-loaded, optimized)
            'affilate_user',          // Affiliate tracking
            'gs'                      // General settings
        ));
    }

    /**
     * Legacy method: /item/{slug}/{user}
     * Pick deterministic default mp for that user, then redirect to front.catalog-item
     */
    public function showByUser($slug, $user)
    {
        // Load catalog item by slug
        $productt = CatalogItem::where('slug', $slug)->first();
        if (!$productt) {
            return response()->view('errors.404')->setStatusCode(404);
        }

        // Find merchant item for this user (in-stock then lowest price)
        $merchantItem = MerchantItem::where('catalog_item_id', $productt->id)
            ->where('user_id', $user)
            ->where('status', 1)
            ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
            ->orderBy('price')
            ->first();

        if (!$merchantItem) {
            return response()->view('errors.404')->setStatusCode(404);
        }

        // Redirect to new preferred route
        return redirect()->route('front.catalog-item', [
            'slug' => $slug,
            'merchant_id' => $merchantItem->user_id,
            'merchant_item_id' => $merchantItem->id
        ], 302);
    }

    /**
     * Very legacy method: /item/{slug}
     * Resolve default mp globally, then redirect to front.catalog-item
     */
    public function show($slug)
    {
        // Load catalog item by slug
        $productt = CatalogItem::where('slug', $slug)->first();
        if (!$productt) {
            return response()->view('errors.404')->setStatusCode(404);
        }

        // Find best merchant item globally (in-stock then lowest price)
        $merchantItem = MerchantItem::where('catalog_item_id', $productt->id)
            ->where('status', 1)
            ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
            ->orderBy('price')
            ->first();

        if (!$merchantItem) {
            return response()->view('errors.404')->setStatusCode(404);
        }

        // Redirect to new preferred route
        return redirect()->route('front.catalog-item', [
            'slug' => $slug,
            'merchant_id' => $merchantItem->user_id,
            'merchant_item_id' => $merchantItem->id
        ], 302);
    }

    /**
     * Optional alternative: /item/{slug}/{user}/{brand_quality_id}
     */
    public function showByUserQuality($slug, $user, $brand_quality_id)
    {
        // Load catalog item by slug
        $productt = CatalogItem::where('slug', $slug)->first();
        if (!$productt) {
            return response()->view('errors.404')->setStatusCode(404);
        }

        // Find merchant item by user and brand quality
        $merchantItem = MerchantItem::where('catalog_item_id', $productt->id)
            ->where('user_id', $user)
            ->where('brand_quality_id', $brand_quality_id)
            ->where('status', 1)
            ->first();

        if (!$merchantItem) {
            return response()->view('errors.404')->setStatusCode(404);
        }

        // Redirect to new preferred route
        return redirect()->route('front.catalog-item', [
            'slug' => $slug,
            'merchant_id' => $merchantItem->user_id,
            'merchant_item_id' => $merchantItem->id
        ], 302);
    }

    public function quickFragment(int $id)
    {
        $product = CatalogItem::findOrFail($id);
        $mp = null;

        // البائع من ?user=
        $merchantId = (int) request()->query('user', 0);
        if ($merchantId > 0) {
            $mp = MerchantItem::with(['qualityBrand', 'user'])
                ->where('catalog_item_id', $product->id)
                ->where('user_id', $merchantId)
                ->first();

            if ($mp) {
                // Use CatalogItemContextHelper for consistency
                CatalogItemContextHelper::apply($product, $mp);
            }
        }

        // جلب البراند من المنتج
        $brand = null;
        if ($product->brand_id) {
            $brand = \App\Models\Brand::find($product->brand_id);
        }

        return response()->view('partials.catalog-item', compact('product', 'mp', 'brand'));
    }


    public function catalogItemFragment(string $key)
    {
        $product = CatalogItem::where('sku', $key)->first()
                ?: CatalogItem::where('slug', $key)->firstOrFail();

        return response()->view('partials.catalog-item', compact('product'));
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
        $product = CatalogItem::findOrFail($id);
        $curr = $this->curr;
        return view('load.quick', compact('product', 'curr'));
    }

    public function affCatalogItemRedirect($slug)
    {
        $catalogItem = CatalogItem::where('slug', '=', $slug)->first();

        // affiliate_link is now on merchant_items, not catalog_items
        $merchantItem = $catalogItem->merchantItems()
            ->whereNotNull('affiliate_link')
            ->first();

        if ($merchantItem && $merchantItem->affiliate_link) {
            return redirect($merchantItem->affiliate_link);
        }

        // Fallback to catalog item page if no affiliate link found
        return redirect()->route('front.catalog-item', $catalogItem->slug);
    }

    // -------------------------------- CATALOG ITEM COMMENT SECTION ----------------------------------------

    public function comment(Request $request)
    {
        $comment = new Comment;
        // Security: Only allow specific fields, set user_id from authenticated user
        $comment->catalog_item_id = $request->input('catalog_item_id');
        $comment->merchant_item_id = $request->input('merchant_item_id');
        $comment->text = $request->input('text');
        $comment->user_id = auth()->id(); // Set from authenticated user, not from request
        $comment->save();

        $data[0] = $comment->user->photo ? url('assets/images/users/' . $comment->user->photo) : url('assets/images/' . $this->gs->user_image);
        $data[1] = $comment->user->name;
        $data[2] = $comment->created_at->diffForHumans();
        $data[3] = $comment->text;
        $data[5] = route('catalog-item.comment.delete', $comment->id);
        $data[6] = route('catalog-item.comment.edit', $comment->id);
        $data[7] = route('catalog-item.reply', $comment->id);
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

    // -------------------------------- CATALOG ITEM REPLY SECTION ----------------------------------------

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
        $resp[4] = route('catalog-item.reply.delete', $reply->id);
        $resp[5] = route('catalog-item.reply.edit', $reply->id);

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

    // ------------------ CatalogReview SECTION --------------------

    public function reviewsubmit(Request $request)
    {
        $ck = 0;
        $purchases = Purchase::where('user_id', $request->user_id)->where('status', 'completed')->get();

        foreach ($purchases as $purchase) {
            $cart = json_decode($purchase->cart, true);
            foreach ($cart['items'] as $item) {
                if ($request->catalog_item_id == $item['item']['id']) { $ck = 1; break; }
            }
        }

        if ($ck == 1) {
            $user = Auth::user();
            $prev = CatalogReview::where('catalog_item_id', $request->catalog_item_id)->where('user_id', $user->id)->first();
            $payload = $request->all();
            $payload['review_date'] = date('Y-m-d H:i:s');

            if ($prev) {
                $prev->update($payload);
            } else {
                $review = new CatalogReview;
                $review->fill($payload);
                $review['review_date'] = date('Y-m-d H:i:s');
                $review->save();
            }
            return back()->with('success', __('Your Review Submitted Successfully.'));
        }

        return back()->with('unsuccess', __('You did not purchase this item.'));
    }

    public function reviews($id)
    {
        $productt = CatalogItem::find($id);
        return view('load.reviews', compact('productt', 'id'));
    }

    public function sideReviews($id)
    {
        $productt = CatalogItem::find($id);
        return view('load.side-load', compact('productt'));
    }

    public function showCrossCatalogItem($id)
    {
        $catalogItem = CatalogItem::findOrFail($id);
        $cross_ids = array_filter(explode(',', (string) $catalogItem->cross_products));
        $cross_catalog_items = CatalogItem::whereIn('id', $cross_ids)
            ->withCount('catalogReviews')
            ->withAvg('catalogReviews', 'rating')
            ->get();

        return view('includes.cross_catalog_item', compact('cross_catalog_items'));
    }
}
