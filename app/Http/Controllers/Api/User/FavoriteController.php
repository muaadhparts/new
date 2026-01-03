<?php

namespace App\Http\Controllers\Api\User;

use App\{
    Models\CatalogItem,
    Models\FavoriteSeller,
    Http\Controllers\Controller,
    Http\Resources\CatalogItemListResource,
    View\Composers\HeaderComposer
};

use Auth;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function favorites(Request $request)
    {
        try{

            $sort = '';
            $user = Auth::guard('api')->user();
            $catalogItemIds = FavoriteSeller::where('user_id','=',$user->id)->pluck('catalog_item_id');

            $productsQuery = CatalogItem::status(1)->whereIn('id', $catalogItemIds);

            if(!empty($request->sort))
            {
                $sort = $request->sort;

                if($sort == "date_desc")
                {
                    $productsQuery->orderBy('id','desc');
                }
                else if($sort == "date_asc")
                {
                    $productsQuery->orderBy('id','asc');
                }
                else if($sort == "price_asc")
                {
                    $productsQuery->orderBy('price','asc');
                }
                else if($sort == "price_desc")
                {
                    $productsQuery->orderBy('price','desc');
                }
            }

            $prods = $productsQuery->get();

            return response()->json(['status' => true, 'data' => CatalogItemListResource::collection($prods), 'error' => []]);

        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => $e->getMessage()]);
        }


    }

    public function add(Request $request)
    {
        try{
            $input = $request->all();
            $user = Auth::guard('api')->user();
            $catalogItemId = $input['catalog_item_id'];
            $ck = FavoriteSeller::where('user_id',$user->id)->where('catalog_item_id',$catalogItemId)->exists();
            if(!$ck){
                $favorite = new FavoriteSeller();
                $favorite->user_id = $user->id;
                $favorite->catalog_item_id = $catalogItemId;
                $favorite->save();
                HeaderComposer::invalidateFavoriteCache($user->id);
                return response()->json(['status' => true, 'data' => ['message' => 'Successfully Added To Favorites.'], 'error' => []]);
            }else{
                return response()->json(['status' => true, 'data' => [], 'error' => ['message' => 'Already Added To Favorites.']]);
            }
        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function remove($id)
    {
        try{
            $user = Auth::user();
            $favorite = FavoriteSeller::where('catalog_item_id',$id)->where('user_id',$user->id)->first();
            $favorite->delete();
            HeaderComposer::invalidateFavoriteCache($user->id);
            return response()->json(['status' => true, 'data' => ['message' => 'Successfully Removed From Favorites.'], 'error' => []]);
        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => $e->getMessage()]);
        }
    }
}
