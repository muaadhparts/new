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
                    return match ($sort) {
                        'price_desc' => $query->orderBy('price', 'DESC'),
                        'price_asc' => $query->orderBy('price', 'ASC'),
                        'part_number' => $query->orderBy('catalog_item_id', 'ASC'),
                        'name_asc' => $query->orderBy('catalog_item_id', 'ASC'),
                        default => $query->orderBy('price', 'ASC'),
                    };
                })
                ->when(empty($sort), function ($query) {
                    return $query->orderBy('price', 'ASC');
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
