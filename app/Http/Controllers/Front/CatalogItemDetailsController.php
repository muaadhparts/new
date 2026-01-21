<?php

namespace App\Http\Controllers\Front;

use App\Helpers\CatalogItemContextHelper;
use App\Models\CatalogItem;
use App\Models\MerchantItem;
use App\Models\AbuseFlag;
use App\Services\CatalogItemOffersService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * CatalogItemDetailsController
 *
 * Handles catalog item fragments and modals.
 * The main catalog item detail page is now handled by PartResultController.
 */
class CatalogItemDetailsController extends FrontBaseController
{
    /**
     * Quick view fragment for modal display
     *
     * @param int $id CatalogItem ID
     * @return \Illuminate\Http\Response
     */
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
                CatalogItemContextHelper::apply($catalogItem, $mp);
            }
        }

        return response()->view('partials.catalog-item', ['catalogItem' => $catalogItem, 'mp' => $mp]);
    }

    /**
     * Catalog item fragment by part number or slug
     * Now returns offers view (grouped by Quality Brand → Merchant → Branch)
     *
     * @param string $key Part number or slug
     * @param CatalogItemOffersService $offersService
     * @return \Illuminate\Http\Response
     */
    public function catalogItemFragment(string $key, CatalogItemOffersService $offersService)
    {
        $catalogItem = CatalogItem::where('part_number', $key)->first()
                ?: CatalogItem::where('slug', $key)->firstOrFail();

        // Return offers view instead of single item view
        $sort = request()->input('sort', 'price_asc');
        $data = $offersService->getGroupedOffers($catalogItem->id, $sort);

        return response()->view('partials.catalog-item-offers', $data);
    }

    /**
     * Vehicle compatibility fragment
     *
     * @param string $key Part number
     * @return \Illuminate\Http\Response
     */
    public function compatibilityFragment(string $key)
    {
        $part_number = $key;
        return response()->view('partials.compatibility', compact('part_number'));
    }

    /**
     * Alternative parts fragment
     *
     * @param string $key Part number
     * @return \Illuminate\Http\Response
     */
    public function alternativeFragment(string $key)
    {
        $part_number = $key;
        return response()->view('partials.alternative', compact('part_number'));
    }

    /**
     * Get offers for a catalog item (grouped by Quality Brand → Merchant → Branch)
     *
     * Returns HTML fragment for modal display
     * API: GET /modal/offers/{catalogItemId}
     *
     * @param int $catalogItemId
     * @param CatalogItemOffersService $offersService
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function offersFragment(int $catalogItemId, CatalogItemOffersService $offersService)
    {
        $sort = request()->input('sort', 'price_asc');
        $data = $offersService->getGroupedOffers($catalogItemId, $sort);

        // Return as JSON for API or HTML for modal
        if (request()->wantsJson() || request()->has('json')) {
            return response()->json($data);
        }

        return response()->view('partials.catalog-item-offers', $data);
    }

    /**
     * Get offers by part number (for parts without alternatives)
     *
     * @param string $part_number
     * @param CatalogItemOffersService $offersService
     * @return \Illuminate\Http\Response
     */
    public function offersByPartNumber(string $part_number, CatalogItemOffersService $offersService)
    {
        $catalogItem = \App\Models\CatalogItem::where('part_number', $part_number)->first();

        if (!$catalogItem) {
            return response()->view('partials.catalog-item-offers', [
                'catalogItem' => null,
                'groupedOffers' => collect(),
                'totalOffers' => 0,
                'message' => __('Part not found'),
            ]);
        }

        $sort = request()->input('sort', 'price_asc');
        $data = $offersService->getGroupedOffers($catalogItem->id, $sort);

        if (request()->wantsJson() || request()->has('json')) {
            return response()->json($data);
        }

        return response()->view('partials.catalog-item-offers', $data);
    }

    /**
     * Report abuse for a catalog item
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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
}
