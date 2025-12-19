<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\{
    Models\Product,
    Models\Wishlist,
    View\Composers\HeaderComposer
};


class WishlistController extends UserBaseController
{
    
    public function wishlists(Request $request)
    {
        $gs = $this->gs;
        $sort = '';
        $pageby = $request->pageby;
        $user = $this->user;
        $page_count = isset($pageby) ? $pageby : $gs->wishlist_count;

        // Get wishlist items with their effective merchant products
        // ✅ N+1 FIX: Eager load product.merchantProducts for legacy items without merchant_product_id
        $wishlistQuery = Wishlist::where('user_id', $user->id)
            ->with([
                'product.merchantProducts' => fn($q) => $q->where('status', 1)->with(['qualityBrand', 'user'])->orderBy('price'),
                'product.brand',
                'merchantProduct',
                'merchantProduct.user',
                'merchantProduct.qualityBrand'
            ]);

        // Apply sorting
        if (!empty($request->sort)) {
            $sort = $request->sort;
            if ($sort == "id_desc") {
                $wishlistQuery = $wishlistQuery->latest('wishlists.id');
            } elseif ($sort == "id_asc") {
                $wishlistQuery = $wishlistQuery->oldest('wishlists.id');
            } elseif ($sort == "price_asc") {
                $wishlistQuery = $wishlistQuery->join('merchant_products as mp', function($join) {
                    $join->on('mp.id', '=', 'wishlists.merchant_product_id')
                         ->orWhere(function($query) {
                             $query->whereNull('wishlists.merchant_product_id')
                                   ->on('mp.product_id', '=', 'wishlists.product_id')
                                   ->where('mp.status', 1);
                         });
                })->orderBy('mp.price', 'asc');
            } elseif ($sort == "price_desc") {
                $wishlistQuery = $wishlistQuery->join('merchant_products as mp', function($join) {
                    $join->on('mp.id', '=', 'wishlists.merchant_product_id')
                         ->orWhere(function($query) {
                             $query->whereNull('wishlists.merchant_product_id')
                                   ->on('mp.product_id', '=', 'wishlists.product_id')
                                   ->where('mp.status', 1);
                         });
                })->orderBy('mp.price', 'desc');
            } else {
                $wishlistQuery = $wishlistQuery->latest('wishlists.id');
            }
        } else {
            $wishlistQuery = $wishlistQuery->latest('wishlists.id');
        }

        $wishlistItems = $wishlistQuery->paginate($page_count);

        // Transform items to include effective merchant product data
        $wishlistItems->getCollection()->transform(function ($wishlistItem) {
            $effectiveMerchantProduct = $wishlistItem->getEffectiveMerchantProduct();

            if ($effectiveMerchantProduct) {
                $wishlistItem->effective_merchant_product = $effectiveMerchantProduct;
                $wishlistItem->effective_price = $effectiveMerchantProduct->price;
                $wishlistItem->effective_vendor = $effectiveMerchantProduct->user;
            }

            return $wishlistItem;
        });

        // Use $wishlists for consistency with the view
        $wishlists = $wishlistItems;

        if ($request->ajax()) {
            return view('frontend.ajax.wishlist', compact('user', 'wishlists', 'sort', 'pageby'));
        }

        return view('user.wishlist', compact('user', 'wishlists', 'sort', 'pageby'));
    }

    /**
     * Add merchant product to wishlist (New standardized method)
     */
    public function addMerchantWishlist($merchantProductId)
    {
        return $this->addwish($merchantProductId);
    }

    /**
     * Remove merchant product from wishlist (New standardized method)
     */
    public function removeMerchantWishlist($merchantProductId)
    {
        $user = $this->user;

        $wishlist = Wishlist::where('user_id', $user->id)
            ->where('merchant_product_id', $merchantProductId)
            ->first();

        if ($wishlist) {
            $wishlist->delete();
            HeaderComposer::invalidateWishlistCache($user->id);
            $data[0] = 1;
            $data[1] = Wishlist::where('user_id', $user->id)->count();
            $data['success'] = __('Successfully Removed From The Wishlist.');
        } else {
            $data[0] = 0;
            $data['error'] = __('Item not found in wishlist.');
        }

        return response()->json($data);
    }

    /**
     * Add merchant product to wishlist
     * Expects merchant_product_id as parameter
     */
    public function addwish($merchantProductId)
    {
        $user = $this->user;
        $data[0] = 0;

        // Check if this specific merchant product is already in wishlist
        $ck = Wishlist::where('user_id', $user->id)
            ->where('merchant_product_id', $merchantProductId)
            ->exists();

        if ($ck) {
            $data['error'] = __('Already Added To The Wishlist.');
            return response()->json($data);
        }

        // Get merchant product to also store product_id for backward compatibility
        $merchantProduct = \App\Models\MerchantProduct::findOrFail($merchantProductId);

        $wish = new Wishlist();
        $wish->user_id = $user->id;
        $wish->product_id = $merchantProduct->product_id;
        $wish->merchant_product_id = $merchantProductId;
        $wish->save();

        HeaderComposer::invalidateWishlistCache($user->id);
        $data[0] = 1;
        $data[1] = Wishlist::where('user_id', $user->id)->count();
        $data['success'] = __('Successfully Added To The Wishlist.');
        return response()->json($data);
    }

    /**
     * Legacy method for backward compatibility
     * Converts product_id to merchant_product_id
     * Can optionally accept user parameter to specify vendor
     */
    public function addwishLegacy(Request $request, $productId)
    {
        $user = $this->user;
        $data[0] = 0;

        $userId = $request->get('user');

        // If user parameter is provided, find specific merchant product for that vendor
        if ($userId) {
            $merchantProduct = \App\Models\MerchantProduct::where('product_id', $productId)
                ->where('user_id', $userId)
                ->where('status', 1)
                ->first();
        } else {
            // Fallback: find the first active merchant product
            $merchantProduct = \App\Models\MerchantProduct::where('product_id', $productId)
                ->where('status', 1)
                ->orderBy('price')
                ->first();
        }

        if (!$merchantProduct) {
            $data['error'] = __('Product not available from any vendor.');
            return response()->json($data);
        }

        // Check if this specific merchant product is already in wishlist
        $ck = Wishlist::where('user_id', $user->id)
            ->where('merchant_product_id', $merchantProduct->id)
            ->exists();

        if ($ck) {
            $data['error'] = __('Already Added To The Wishlist.');
            return response()->json($data);
        }

        $wish = new Wishlist();
        $wish->user_id = $user->id;
        $wish->product_id = $productId;
        $wish->merchant_product_id = $merchantProduct->id;
        $wish->save();

        HeaderComposer::invalidateWishlistCache($user->id);
        $data[0] = 1;
        $data[1] = Wishlist::where('user_id', $user->id)->count();
        $data['success'] = __('Successfully Added To The Wishlist.');
        return response()->json($data);
    }

    public function removewish($id)
    {
        $user = $this->user;
        $wish = Wishlist::where('user_id', $user->id)->findOrFail($id);
        $wish->delete();

        HeaderComposer::invalidateWishlistCache($user->id);
        $data[0] = 1;
        $data[1] = Wishlist::where('user_id', $user->id)->count();
        $data['success'] = __('Successfully Removed From Wishlist.');
        return response()->json($data);
    }

}
