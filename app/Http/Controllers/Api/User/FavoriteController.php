<?php

namespace App\Http\Controllers\Api\User;

use App\{
    Models\Product,
    Models\Favorite,
    Http\Controllers\Controller,
    Http\Resources\ProductlistResource,
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
            $productIds = Favorite::where('user_id','=',$user->id)->pluck('product_id');

            $productsQuery = Product::status(1)->whereIn('id', $productIds);

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

            return response()->json(['status' => true, 'data' => ProductlistResource::collection($prods), 'error' => []]);

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
            $ck = Favorite::where('user_id',$user->id)->where('product_id',$input['product_id'])->exists();
            if(!$ck){
                $favorite = new Favorite();
                $favorite->user_id = $user->id;
                $favorite->product_id = $input['product_id'];
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
            $favorite = Favorite::where('product_id',$id)->where('user_id',$user->id)->first();
            $favorite->delete();
            HeaderComposer::invalidateFavoriteCache($user->id);
            return response()->json(['status' => true, 'data' => ['message' => 'Successfully Removed From Favorites.'], 'error' => []]);
        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => $e->getMessage()]);
        }
    }
}
