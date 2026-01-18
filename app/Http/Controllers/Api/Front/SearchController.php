<?php

namespace App\Http\Controllers\Api\Front;

use App\Models\CatalogItem;
use App\Http\Controllers\Controller;
use App\Http\Resources\CatalogItemListResource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SearchController extends Controller
{
    /**
     * Basic search by name/price only
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
