<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\{
    Models\Product,
    Models\Wishlist
};


class WishlistController extends UserBaseController
{
    
    public function wishlists(Request $request)
    {
        $gs = $this->gs;
        $sort = '';
        $pageby = $request->pageby;
        $user = $this->user;
        $wishes = Wishlist::where('user_id','=',$user->id)->pluck('product_id');
        $page_count = isset($pageby) ? $pageby : $gs->wishlist_count;


        // Build base query: only include products that have at least one active merchant listing
        $query = Product::status(1)
            ->whereIn('id', $wishes);

        // Apply sorting options.  Since pricing is stored per vendor on merchant_products,
        // use subqueries to order by the minimum vendor price.  If no sort option
        // provided, default to latest products by ID.
        if (!empty($request->sort)) {
            $sort = $request->sort;
            if ($sort == "id_desc") {
                $query = $query->latest('products.id');
            } elseif ($sort == "id_asc") {
                $query = $query->oldest('products.id');
            } elseif ($sort == "price_asc") {
                // Order by ascending minimum vendor price for each product
                $query = $query->orderByRaw('(select min(price) from merchant_products where merchant_products.product_id = products.id and merchant_products.status = 1) asc');
            } elseif ($sort == "price_desc") {
                // Order by descending minimum vendor price for each product
                $query = $query->orderByRaw('(select min(price) from merchant_products where merchant_products.product_id = products.id and merchant_products.status = 1) desc');
            } else {
                // Unknown sort option, fallback to latest
                $query = $query->latest('products.id');
            }
        } else {
            // Default sort: latest products by ID
            $query = $query->latest('products.id');
        }

        // Paginate the results
        $wishlists = $query->paginate($page_count);

        // Transform each product to compute the vendor price on the fly.  The price and
        // stock are vendor specific, so we use vendorSizePrice() from the Product model
        // to calculate the price including commissions and attribute adjustments.
        $wishlists->getCollection()->transform(function ($item) {
            $item->price = $item->vendorSizePrice();
            return $item;
        });

        if ($request->ajax()) {
            // Use consistent view path for AJAX wishlist loading.  front.ajax.wishlist
            // and frontend.ajax.wishlist both refer to partials; choose one.
            return view('frontend.ajax.wishlist', compact('user', 'wishlists', 'sort', 'pageby'));
        }
        return view('user.wishlist', compact('user', 'wishlists', 'sort', 'pageby'));
    }

    public function addwish($id)
    {
        $user = $this->user;

        $data[0] = 0;
        $ck = Wishlist::where('user_id','=',$user->id)->where('product_id','=',$id)->get()->count();
        if($ck > 0)
        {
            $data['error'] = __('Already Added To The Wishlist.');
            return response()->json($data);
        }
        $wish = new Wishlist();
        $wish->user_id = $user->id;
        $wish->product_id = $id;
        $wish->save();
        $data[0] = 1;
        $data[1] = count($user->wishlists);

        $data['success'] = __('Successfully Added To The Wishlist.');
        return response()->json($data);

    }

    public function removewish($id)
    {
        $user = $this->user;
        $wish = Wishlist::findOrFail($id);
        $wish->delete();
        $data[0] = 1;
        $data[1] = count($user->wishlists);
        $data['success'] = __('Successfully Removed From Wishlist.');
        return response()->json($data);

    }

}
