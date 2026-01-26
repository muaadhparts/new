<?php

namespace App\Http\Controllers\Api\User;

use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Commerce\Models\FavoriteSeller;
use App\Http\Controllers\Controller;
use App\Http\Resources\CatalogItemListResource;
use App\View\Composers\HeaderComposer;

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

            $sort = $request->sort ?? 'price_asc';

            $isArabic = app()->getLocale() === 'ar';

            if ($sort === 'name_asc') {
                if ($isArabic) {
                    $productsQuery->orderByRaw("CASE WHEN label_ar IS NOT NULL AND label_ar != '' THEN 0 ELSE 1 END ASC")
                                  ->orderByRaw("COALESCE(NULLIF(label_ar, ''), NULLIF(label_en, ''), name) ASC");
                } else {
                    $productsQuery->orderByRaw("CASE WHEN label_en IS NOT NULL AND label_en != '' THEN 0 ELSE 1 END ASC")
                                  ->orderByRaw("COALESCE(NULLIF(label_en, ''), NULLIF(label_ar, ''), name) ASC");
                }
            } else {
                match ($sort) {
                    'price_desc' => $productsQuery->orderBy('price', 'desc'),
                    'part_number' => $productsQuery->orderBy('part_number', 'asc'),
                    default => $productsQuery->orderBy('price', 'asc'),
                };
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
