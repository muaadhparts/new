<?php

namespace App\Http\Controllers\Front;

use App\Helpers\CatalogItemContextHelper;
use App\Models\BuyerNote;
use App\Models\Purchase;
use App\Models\CatalogItem;
use App\Models\MerchantItem;
use App\Models\CatalogItemClick;
use App\Models\CatalogReview;
use App\Models\NoteResponse;
use App\Models\AbuseFlag;
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
     * /item/{slug}/merchantitem/{merchant_item_id}
     *
     * SECURITY RULES:
     * 1. MerchantItem MUST exist and be active
     * 2. CatalogItem data is READONLY - price/stock/qty come ONLY from MerchantItem
     * 3. NO best_merchant_item or merchantItems()->first() allowed
     * 4. merchant_item_id is unique - sufficient to identify the listing
     * ==========================================================================
     */
    public function showByMerchantItem(Request $request, $slug, $merchant_item_id)
    {
        $gs = $this->gs;

        // ======================================================================
        // STEP 1: STRICT GUARD - MerchantItem MUST exist
        // ======================================================================
        $merchantItem = MerchantItem::with([
            'user',
            'qualityBrand',
            'merchantBranch',
            'catalogItem.merchantPhotos',
            'catalogItem.brand',
        ])->find($merchant_item_id);

        if (!$merchantItem) {
            abort(404, 'Merchant item not found');
        }

        // ======================================================================
        // STEP 4: STRICT GUARD - MerchantItem MUST be active
        // ======================================================================
        if ((int) $merchantItem->status !== 1) {
            abort(404, 'Merchant item is not active');
        }

        // ======================================================================
        // STEP 5: STRICT GUARD - Merchant MUST be active (is_merchant = 2)
        // ======================================================================
        if (!$merchantItem->user || (int) $merchantItem->user->is_merchant !== 2) {
            abort(404, 'Merchant is not active');
        }

        // ======================================================================
        // STEP 6: Get CatalogItem (READONLY - catalog data only)
        // ======================================================================
        $catalogItem = $merchantItem->catalogItem;

        if (!$catalogItem) {
            abort(404, 'Catalog item not found for this merchant listing');
        }

        // Load catalogReviews (catalog-level data) - count, avg, and full list for reviews
        $catalogItem->loadCount('catalogReviews');
        $catalogItem->loadAvg('catalogReviews', 'rating');
        $catalogItem->load(['catalogReviews' => fn($q) => $q->with('user')->orderBy('review_date', 'desc')]);

        // ======================================================================
        // STEP 7: Verify slug matches (SEO redirect if changed)
        // ======================================================================
        if ($catalogItem->slug !== $slug) {
            return redirect()->route('front.catalog-item', [
                'slug' => $catalogItem->slug,
                'merchant_item_id' => $merchantItem->id
            ], 301);
        }

        // ======================================================================
        // STEP 8: Affiliate tracking (optional)
        // ======================================================================
        $affilate_user = 0;
        if ($gs->item_affilate == 1 && $request->has('ref') && !empty($request->ref)) {
            $userRef = User::where('affilate_code', $request->ref)->first();
            if ($userRef && (!Auth::check() || Auth::id() != $userRef->id)) {
                $affilate_user = $userRef->id;
            }
        }

        // ======================================================================
        // STEP 9: Other sellers (same catalog item, different merchants/branches)
        // ======================================================================
        $otherSellers = MerchantItem::query()
            ->where('catalog_item_id', $catalogItem->id)
            ->where('status', 1)
            ->where('id', '<>', $merchantItem->id)
            ->whereHas('user', fn($q) => $q->where('is_merchant', 2))
            ->with(['user', 'qualityBrand', 'merchantBranch'])
            ->orderBy('price')
            ->get();

        // ======================================================================
        // STEP 10: Other listings from same merchant
        // ======================================================================
        $merchantListings = MerchantItem::query()
            ->where('user_id', $merchantItem->user_id)
            ->where('status', 1)
            ->where('id', '<>', $merchantItem->id)
            ->with(['catalogItem' => fn($q) => $q->withCount('catalogReviews')->withAvg('catalogReviews', 'rating'), 'user', 'qualityBrand'])
            ->take(12)
            ->get();

        // ======================================================================
        // STEP 10b: Related Items (same type/item_type, different catalog item)
        // Optimized: Query MerchantItem directly with catalog item filters
        // ======================================================================
        // item_type is now on merchant_items, not catalog_items
        $relatedMerchantItems = MerchantItem::where('status', 1)
            ->where('stock', '>', 0)
            ->where('item_type', $merchantItem->item_type)
            ->whereHas('catalogItem', function($q) use ($catalogItem) {
                $q->where('id', '!=', $catalogItem->id);
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
        if (!session()->has('click_' . $catalogItem->id)) {
            CatalogItemClick::create([
                'catalog_item_id' => $catalogItem->id,
                'date' => Carbon::now()->format('Y-m-d'),
                'clicks' => 1,
            ]);
            session()->put('click_' . $catalogItem->id, 1);
        }

        // ======================================================================
        // STEP 12: PASS TO VIEW
        // $merchant is the AUTHORITATIVE source for price/stock/qty
        // $catalogItem is READONLY catalog data (name, description, images)
        // ======================================================================
        $merchant = $merchantItem;
        $merchantId = $merchantItem->user_id;

        return view('frontend.catalog-item', compact(
            'catalogItem',            // CatalogItem (READONLY - catalog only)
            'merchant',               // MerchantItem (AUTHORITATIVE - price/stock/qty)
            'merchantId',             // Verified merchant user ID
            'otherSellers',           // Alternative sellers
            'merchantListings',         // More from this merchant
            'relatedMerchantItems',   // Related items (pre-loaded, optimized)
            'affilate_user',          // Affiliate tracking
            'gs'                      // General settings
        ));
    }

    public function quickFragment(int $id)
    {
        $catalogItem = CatalogItem::findOrFail($id);
        $mp = null;

        // Get merchant from ?user= query param
        $merchantId = (int) request()->query('user', 0);
        if ($merchantId > 0) {
            $mp = MerchantItem::with(['qualityBrand', 'user'])
                ->where('catalog_item_id', $catalogItem->id)
                ->where('user_id', $merchantId)
                ->first();

            if ($mp) {
                // Use CatalogItemContextHelper for consistency
                CatalogItemContextHelper::apply($catalogItem, $mp);
            }
        }

        // Get brand from catalog item
        $brand = null;
        if ($catalogItem->brand_id) {
            $brand = \App\Models\Brand::find($catalogItem->brand_id);
        }

        // Note: 'catalogItem' kept for backward compatibility in views
        return response()->view('partials.catalog-item', ['catalogItem' => $catalogItem, 'mp' => $mp, 'brand' => $brand]);
    }


    public function catalogItemFragment(string $key)
    {
        $catalogItem = CatalogItem::where('part_number', $key)->first()
                ?: CatalogItem::where('slug', $key)->firstOrFail();

        // Note: 'catalogItem' kept for backward compatibility in views
        return response()->view('partials.catalog-item', ['catalogItem' => $catalogItem]);
    }

    public function compatibilityFragment(string $key)
    {
        $part_number = $key;
        return response()->view('partials.compatibility', compact('part_number'));
    }

    public function alternativeFragment(string $key)
    {
        $part_number = $key;
        return response()->view('partials.alternative', compact('part_number'));
    }

    public function report(Request $request)
    {
        $rules = ['note' => 'max:400'];
        $customs = ['note.max' => __('Note Must Be Less Than 400 Characters.')];
        $validator = Validator::make($request->all(), $rules, $customs);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->getMessageBag()->toArray()]);
        }

        $data = new AbuseFlag;
        $data->fill($request->all())->save();
        return response()->json(__('Report Sent Successfully.'));
    }

    public function quick($id)
    {
        $catalogItem = CatalogItem::findOrFail($id);
        $curr = $this->curr;
        // Note: 'catalogItem' kept for backward compatibility in views
        return view('load.quick', ['catalogItem' => $catalogItem, 'curr' => $curr]);
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

    // -------------------------------- CATALOG ITEM BUYER NOTE SECTION ----------------------------------------

    /**
     * Submit a buyer note on a catalog item.
     * Security: Only allow specific fields, set user_id from authenticated user.
     */
    public function buyerNoteStore(Request $request)
    {
        $buyerNote = new BuyerNote;
        $buyerNote->catalog_item_id = $request->input('catalog_item_id');
        $buyerNote->merchant_item_id = $request->input('merchant_item_id');
        $buyerNote->text = $request->input('text');
        $buyerNote->user_id = auth()->id(); // Set from authenticated user, not from request
        $buyerNote->save();

        $data[0] = $buyerNote->user->photo ? url('assets/images/users/' . $buyerNote->user->photo) : url('assets/images/' . $this->gs->user_image);
        $data[1] = $buyerNote->user->name;
        $data[2] = $buyerNote->created_at->diffForHumans();
        $data[3] = $buyerNote->text;
        $data[5] = route('catalog-item.buyer-note.delete', $buyerNote->id);
        $data[6] = route('catalog-item.buyer-note.edit', $buyerNote->id);
        $data[7] = route('catalog-item.reply', $buyerNote->id);
        $data[8] = $buyerNote->user->id;

        $newdata = '<li>';
        $newdata .= '<div class="single-buyer-note buyer-note-section">';
        $newdata .= '<div class="left-area"><img src="' . $data[0] . '" alt=""><h5 class="name">' . $data[1] . '</h5><p class="date">' . $data[2] . '</p></div>';
        $newdata .= '<div class="right-area"><div class="buyer-note-body"><p>' . $data[3] . '</p></div>';
        $newdata .= '<div class="buyer-note-footer"><div class="links">';
        $newdata .= '<a href="javascript:;" class="buyer-note-link reply mr-2"><i class="fas fa-reply "></i>' . __('Reply') . '</a>';
        $newdata .= '<a href="javascript:;" class="buyer-note-link edit mr-2"><i class="fas fa-edit "></i>' . __('Edit') . '</a>';
        $newdata .= '<a href="javascript:;" data-href="' . $data[5] . '" class="buyer-note-link buyer-note-delete mr-2"><i class="fas fa-trash"></i>' . __('Delete') . '</a>';
        $newdata .= '</div></div></div></div>';
        $newdata .= '<div class="reply-area edit-area d-none"><form class="update" action="' . $data[6] . '" method="POST">' . csrf_field() . '<textarea placeholder="' . __('Edit Your Note') . '" name="text" required=""></textarea><button type="submit">' . __('Submit') . '</button><a href="javascript:;" class="remove">' . __('Cancel') . '</a></form></div>';
        $newdata .= '<div class="reply-area reply-reply-area d-none"><form class="reply-form" action="' . $data[7] . '" method="POST"><input type="hidden" name="user_id" value="' . $data[8] . '">' . csrf_field() . '<textarea placeholder="' . __('Write Your Reply') . '" name="text" required=""></textarea><button type="submit">' . __('Submit') . '</button><a href="javascript:;" class="remove">' . __('Cancel') . '</a></form></div>';
        $newdata .= '</li>';

        return response()->json($newdata);
    }

    public function buyerNoteEdit(Request $request, $id)
    {
        $buyerNote = BuyerNote::findOrFail($id);
        $buyerNote->text = $request->text;
        $buyerNote->save();

        return response()->json($buyerNote->text);
    }

    public function buyerNoteDelete($id)
    {
        $buyerNote = BuyerNote::findOrFail($id);
        if ($buyerNote->noteResponses->count() > 0) {
            foreach ($buyerNote->noteResponses as $noteResponse) { $noteResponse->delete(); }
        }
        $buyerNote->delete();
    }

    // -------------------------------- CATALOG ITEM REPLY SECTION ----------------------------------------

    public function reply(Request $request, $id)
    {
        $noteResponse = new NoteResponse;
        $data = $request->all();
        $data['buyer_note_id'] = $id;
        $noteResponse->fill($data)->save();

        $resp[0] = $noteResponse->user->photo ? url('assets/images/users/' . $noteResponse->user->photo) : url('assets/images/' . $this->gs->user_image);
        $resp[1] = $noteResponse->user->name;
        $resp[2] = $noteResponse->created_at->diffForHumans();
        $resp[3] = $noteResponse->text;
        $resp[4] = route('catalog-item.reply.delete', $noteResponse->id);
        $resp[5] = route('catalog-item.reply.edit', $noteResponse->id);

        $newdata = '<div class="single-buyer-note replay-buyer-note"><div class="left-area"><img src="' . $resp[0] . '" alt=""><h5 class="name">' . $resp[1] . '</h5><p class="date">' . $resp[2] . '</p></div>';
        $newdata .= '<div class="right-area"><div class="buyer-note-body"><p>' . $resp[3] . '</p></div><div class="buyer-note-footer"><div class="links">';
        $newdata .= '<a href="javascript:;" class="buyer-note-link reply mr-2"><i class="fas fa-reply "></i>' . __('Reply') . '</a>';
        $newdata .= '<a href="javascript:;" class="buyer-note-link edit mr-2"><i class="fas fa-edit "></i>' . __('Edit') . '</a>';
        $newdata .= '<a href="javascript:;" data-href="' . $resp[4] . '" class="buyer-note-link reply-delete mr-2"><i class="fas fa-trash"></i>' . __('Delete') . '</a>';
        $newdata .= '</div></div></div></div>';
        $newdata .= '<div class="reply-area edit-area d-none"><form class="update" action="' . $resp[5] . '" method="POST">' . csrf_field() . '<textarea placeholder="' . __('Edit Your Reply') . '" name="text" required=""></textarea><button type="submit">' . __('Submit') . '</button><a href="javascript:;" class="remove">' . __('Cancel') . '</a></form></div>';

        return response()->json($newdata);
    }

    public function replyedit(Request $request, $id)
    {
        $noteResponse = NoteResponse::findOrFail($id);
        $noteResponse->text = $request->text;
        $noteResponse->save();
        return response()->json($noteResponse->text);
    }

    public function replydelete($id)
    {
        $noteResponse = NoteResponse::findOrFail($id);
        $noteResponse->delete();
    }

    // ------------------ CatalogReview SECTION --------------------

    /**
     * Submit a review for a catalog item.
     * Only allows reviews from users who have purchased the item.
     */
    public function reviewsubmit(Request $request)
    {
        $ck = 0;
        $purchases = Purchase::where('user_id', $request->user_id)->where('status', 'completed')->get();

        foreach ($purchases as $purchase) {
            $cart = $purchase->cart; // Model cast handles decoding
            foreach ($cart['items'] as $cartItem) {
                if ($request->catalog_item_id == $cartItem['item']['id']) { $ck = 1; break; }
            }
        }

        if ($ck == 1) {
            $user = Auth::user();
            $prev = CatalogTestimonial::where('catalog_item_id', $request->catalog_item_id)->where('user_id', $user->id)->first();
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

    /**
     * Load reviews for a catalog item.
     */
    public function reviews($id)
    {
        $catalogItem = CatalogItem::find($id);
        return view('load.reviews', ['catalogItem' => $catalogItem, 'id' => $id]);
    }

    /**
     * Load side reviews for a catalog item.
     */
    public function sideReviews($id)
    {
        $catalogItem = CatalogItem::find($id);
        return view('load.side-load', ['catalogItem' => $catalogItem]);
    }

    public function showCrossCatalogItem($id)
    {
        $catalogItem = CatalogItem::findOrFail($id);
        $cross_ids = array_filter(explode(',', (string) $catalogItem->cross_items));
        $cross_catalog_items = CatalogItem::whereIn('id', $cross_ids)
            ->withCount('catalogReviews')
            ->withAvg('catalogReviews', 'rating')
            ->get();

        return view('includes.cross_catalog_item', compact('cross_catalog_items'));
    }
}
