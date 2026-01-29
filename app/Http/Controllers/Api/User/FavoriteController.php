<?php

namespace App\Http\Controllers\Api\User;

use App\Domain\Commerce\Models\FavoriteSeller;
use App\Domain\Catalog\Events\ProductFavoritedEvent;
use App\Domain\Catalog\Services\CatalogItemApiService;
use App\Http\Controllers\Controller;
use App\View\Composers\HeaderComposer;

use Auth;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function __construct(
        private CatalogItemApiService $catalogItemApiService
    ) {}
    public function favorites(Request $request)
    {
        try {
            $user = Auth::guard('api')->user();
            $sort = $request->sort ?? 'price_asc';

            // Get DTOs from service (Clean Architecture)
            $catalogItemCards = $this->catalogItemApiService->getUserFavorites($user->id, $sort);

            return response()->json(['status' => true, 'data' => $catalogItemCards->toArray(), 'error' => []]);
        } catch(\Exception $e) {
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

                // ═══════════════════════════════════════════════════════════════════
                // EVENT-DRIVEN: Dispatch ProductFavoritedEvent (added)
                // ═══════════════════════════════════════════════════════════════════
                event(new ProductFavoritedEvent(
                    catalogItemId: $catalogItemId,
                    customerId: $user->id,
                    added: true
                ));

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

            // ═══════════════════════════════════════════════════════════════════
            // EVENT-DRIVEN: Dispatch ProductFavoritedEvent (removed)
            // ═══════════════════════════════════════════════════════════════════
            event(new ProductFavoritedEvent(
                catalogItemId: (int) $id,
                customerId: $user->id,
                added: false
            ));

            return response()->json(['status' => true, 'data' => ['message' => 'Successfully Removed From Favorites.'], 'error' => []]);
        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => $e->getMessage()]);
        }
    }
}
