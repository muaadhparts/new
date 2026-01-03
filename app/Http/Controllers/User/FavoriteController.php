<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\{
    Models\CatalogItem,
    Models\FavoriteSeller,
    View\Composers\HeaderComposer
};


class FavoriteController extends UserBaseController
{

    public function favorites(Request $request)
    {
        $gs = $this->gs;
        $sort = '';
        $pageby = $request->pageby;
        $user = $this->user;
        $page_count = isset($pageby) ? $pageby : $gs->favorite_count;

        $favoriteQuery = FavoriteSeller::where('user_id', $user->id)
            ->with([
                'catalogItem.merchantItems' => fn($q) => $q->where('status', 1)->with(['qualityBrand', 'user'])->orderBy('price'),
                'catalogItem.brand',
                'merchantItem',
                'merchantItem.user',
                'merchantItem.qualityBrand'
            ]);

        if (!empty($request->sort)) {
            $sort = $request->sort;
            if ($sort == "id_desc") {
                $favoriteQuery = $favoriteQuery->latest('favorite_sellers.id');
            } elseif ($sort == "id_asc") {
                $favoriteQuery = $favoriteQuery->oldest('favorite_sellers.id');
            } elseif ($sort == "price_asc") {
                $favoriteQuery = $favoriteQuery->join('merchant_items as mp', function($join) {
                    $join->on('mp.id', '=', 'favorite_sellers.merchant_item_id')
                         ->orWhere(function($query) {
                             $query->whereNull('favorite_sellers.merchant_item_id')
                                   ->on('mp.catalog_item_id', '=', 'favorite_sellers.catalog_item_id')
                                   ->where('mp.status', 1);
                         });
                })->orderBy('mp.price', 'asc');
            } elseif ($sort == "price_desc") {
                $favoriteQuery = $favoriteQuery->join('merchant_items as mp', function($join) {
                    $join->on('mp.id', '=', 'favorite_sellers.merchant_item_id')
                         ->orWhere(function($query) {
                             $query->whereNull('favorite_sellers.merchant_item_id')
                                   ->on('mp.catalog_item_id', '=', 'favorite_sellers.catalog_item_id')
                                   ->where('mp.status', 1);
                         });
                })->orderBy('mp.price', 'desc');
            } else {
                $favoriteQuery = $favoriteQuery->latest('favorite_sellers.id');
            }
        } else {
            $favoriteQuery = $favoriteQuery->latest('favorite_sellers.id');
        }

        $favoriteItems = $favoriteQuery->paginate($page_count);

        $favoriteItems->getCollection()->transform(function ($favoriteItem) {
            $effectiveMerchantItem = $favoriteItem->getEffectiveMerchantItem();

            if ($effectiveMerchantItem) {
                $favoriteItem->effective_merchant_item = $effectiveMerchantItem;
                $favoriteItem->effective_price = $effectiveMerchantItem->price;
                $favoriteItem->effective_merchant = $effectiveMerchantItem->user;
            }

            return $favoriteItem;
        });

        $favorites = $favoriteItems;

        if ($request->ajax()) {
            return view('frontend.ajax.favorite', compact('user', 'favorites', 'sort', 'pageby'));
        }

        return view('user.favorite', compact('user', 'favorites', 'sort', 'pageby'));
    }

    /**
     * Add merchant catalogItem to favorites
     */
    public function addMerchantFavorite($merchantProductId)
    {
        return $this->add($merchantProductId);
    }

    /**
     * Remove merchant catalogItem from favorites
     */
    public function removeMerchantFavorite($merchantProductId)
    {
        $user = $this->user;

        $favorite = FavoriteSeller::where('user_id', $user->id)
            ->where('merchant_item_id', $merchantProductId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            HeaderComposer::invalidateFavoriteCache($user->id);
            $data[0] = 1;
            $data[1] = FavoriteSeller::where('user_id', $user->id)->count();
            $data['success'] = __('Successfully Removed From Favorites.');
        } else {
            $data[0] = 0;
            $data['error'] = __('Item not found in favorite_sellers.');
        }

        return response()->json($data);
    }

    /**
     * Add merchant catalogItem to favorites
     */
    public function add($merchantProductId)
    {
        $user = $this->user;
        $data[0] = 0;

        $ck = FavoriteSeller::where('user_id', $user->id)
            ->where('merchant_item_id', $merchantProductId)
            ->exists();

        if ($ck) {
            $data['error'] = __('Already Added To Favorites.');
            return response()->json($data);
        }

        $merchantItem = \App\Models\MerchantItem::findOrFail($merchantProductId);

        $favorite = new FavoriteSeller();
        $favorite->user_id = $user->id;
        $favorite->catalog_item_id = $merchantItem->catalog_item_id;
        $favorite->merchant_item_id = $merchantProductId;
        $favorite->save();

        HeaderComposer::invalidateFavoriteCache($user->id);
        $data[0] = 1;
        $data[1] = FavoriteSeller::where('user_id', $user->id)->count();
        $data['success'] = __('Successfully Added To Favorites.');
        return response()->json($data);
    }

    /**
     * Legacy method for backward compatibility
     */
    public function addLegacy(Request $request, $catalogItemId)
    {
        $user = $this->user;
        $data[0] = 0;

        $userId = $request->get('user');

        if ($userId) {
            $merchantItem = \App\Models\MerchantItem::where('catalog_item_id', $catalogItemId)
                ->where('user_id', $userId)
                ->where('status', 1)
                ->first();
        } else {
            $merchantItem = \App\Models\MerchantItem::where('catalog_item_id', $catalogItemId)
                ->where('status', 1)
                ->orderBy('price')
                ->first();
        }

        if (!$merchantItem) {
            $data['error'] = __('CatalogItem not available from any merchant.');
            return response()->json($data);
        }

        $ck = FavoriteSeller::where('user_id', $user->id)
            ->where('merchant_item_id', $merchantItem->id)
            ->exists();

        if ($ck) {
            $data['error'] = __('Already Added To Favorites.');
            return response()->json($data);
        }

        $favorite = new FavoriteSeller();
        $favorite->user_id = $user->id;
        $favorite->catalog_item_id = $catalogItemId;
        $favorite->merchant_item_id = $merchantItem->id;
        $favorite->save();

        HeaderComposer::invalidateFavoriteCache($user->id);
        $data[0] = 1;
        $data[1] = FavoriteSeller::where('user_id', $user->id)->count();
        $data['success'] = __('Successfully Added To Favorites.');
        return response()->json($data);
    }

    public function remove($id)
    {
        $user = $this->user;
        $favorite = FavoriteSeller::where('user_id', $user->id)->findOrFail($id);
        $favorite->delete();

        HeaderComposer::invalidateFavoriteCache($user->id);
        $data[0] = 1;
        $data[1] = FavoriteSeller::where('user_id', $user->id)->count();
        $data['success'] = __('Successfully Removed From Favorites.');
        return response()->json($data);
    }

}
