<?php

namespace App\Http\Controllers\Api\Front;

use App\Models\CatalogItem;
use App\Http\Controllers\Controller;
use App\Http\Resources\CatalogItemListResource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * SearchController
 *
 * Note: Old category/subcategory/childcategory methods are deprecated.
 * The new system uses TreeCategories instead.
 */
class SearchController extends Controller
{
    /**
     * @deprecated Category system removed - returns empty collection
     */
    public function categories() {
        return response()->json([
            'status' => true,
            'data' => [],
            'error' => [],
            'message' => 'Category system deprecated. Use TreeCategories API instead.'
        ]);
    }

    /**
     * @deprecated Category system removed - returns empty collection
     */
    public function category($id) {
        return response()->json([
            'status' => true,
            'data' => [],
            'error' => [],
            'message' => 'Category system deprecated. Use TreeCategories API instead.'
        ]);
    }

    /**
     * @deprecated Subcategory system removed - returns empty collection
     */
    public function subcategories($id) {
        return response()->json([
            'status' => true,
            'data' => [],
            'error' => [],
            'message' => 'Subcategory system deprecated. Use TreeCategories API instead.'
        ]);
    }

    /**
     * @deprecated Childcategory system removed - returns empty collection
     */
    public function childcategories($id) {
        return response()->json([
            'status' => true,
            'data' => [],
            'error' => [],
            'message' => 'Childcategory system deprecated. Use TreeCategories API instead.'
        ]);
    }

    /**
     * @deprecated Attributes for old categories removed - returns empty collection
     */
    public function attributes(Request $request, $id) {
        return response()->json([
            'status' => true,
            'data' => [],
            'error' => [],
            'message' => 'Attribute system deprecated. Use TreeCategories API instead.'
        ]);
    }

    /**
     * @deprecated AttributeOptions removed - returns empty collection
     */
    public function attributeoptions($id) {
        return response()->json([
            'status' => true,
            'data' => [],
            'error' => [],
            'message' => 'AttributeOptions system deprecated.'
        ]);
    }

    /**
     * Basic search by name/price only
     * Category filtering removed - use front.catalog.category route instead
     */
    public function search(Request $request)
    {
        try {
            $minprice = $request->min;
            $maxprice = $request->max;
            $sort = $request->sort;
            $search = $request->search;

            $prods = CatalogItem::query()
                ->when($search, function ($query, $search) {
                    return $query->where('name', 'like', '%' . $search . '%');
                })
                ->when($minprice, function($query, $minprice) {
                    return $query->where('price', '>=', $minprice);
                })
                ->when($maxprice, function($query, $maxprice) {
                    return $query->where('price', '<=', $maxprice);
                })
                ->when($sort, function ($query, $sort) {
                    if ($sort == 'date_desc') {
                        return $query->orderBy('id', 'DESC');
                    } elseif ($sort == 'date_asc') {
                        return $query->orderBy('id', 'ASC');
                    } elseif ($sort == 'price_desc') {
                        return $query->orderBy('price', 'DESC');
                    } elseif ($sort == 'price_asc') {
                        return $query->orderBy('price', 'ASC');
                    }
                })
                ->when(empty($sort), function ($query) {
                    return $query->orderBy('id', 'DESC');
                })
                ->where('status', 1)
                ->get();

            $prods = (new Collection(CatalogItem::filterProducts($prods)));

            return response()->json([
                'status' => true,
                'data' => CatalogItemListResource::collection($prods->flatten(1)),
                'error' => []
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status' => false,
                'data' => [],
                'error' => ['message' => $e->getMessage()]
            ]);
        }
    }
}
