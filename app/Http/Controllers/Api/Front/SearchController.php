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

            $productsQuery = CatalogItem::query()
                ->when($search, function ($query, $search) {
                    return $query->where('name', 'like', '%' . $search . '%');
                })
                ->when($minprice, function($query, $minprice) {
                    return $query->where('price', '>=', $minprice);
                })
                ->when($maxprice, function($query, $maxprice) {
                    return $query->where('price', '<=', $maxprice);
                })
                ->where('status', 1);

            $sort = $sort ?? 'price_asc';

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
