<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SkuAlternative;
use Illuminate\Http\Request;

class SearchResultsController extends Controller
{
    public function show(Request $request, $sku)
    {
        // المنتج الأساسي
        $prods = Product::where('sku', $sku)->get();

        // جلب السطر من جدول sku_alternatives
        $skuAlternative = SkuAlternative::where('sku', $sku)->first();

        if ($skuAlternative && $skuAlternative->group_id) {
            // جلب كل الـ SKUs في نفس القروب مع استثناء نفسه
            $alternativeSkus = SkuAlternative::where('group_id', $skuAlternative->group_id)
                ->where('sku', '<>', $sku)
                ->pluck('sku')
                ->toArray();

            if (!empty($alternativeSkus)) {
                $alternatives = Product::whereIn('sku', $alternativeSkus)->get();
            } else {
                $alternatives = collect();
            }
        } else {
            $alternatives = collect();
        }

        // Get filters from request
        $storeFilter = $request->get('store', 'all');
        $qualityFilter = $request->get('quality', 'all');
        $sortBy = $request->get('sort', 'sku_asc');

        // جمع كل المنتجات (الأساسية + البدائل)
        $allProducts = $prods->merge($alternatives);

        // جمع كل merchant products مع الفلاتر
        $filteredMerchants = collect();

        foreach ($allProducts as $product) {
            $query = $product->merchantProducts()
                ->where('status', 1)
                ->with([
                    'user:id,is_vendor,name,shop_name,email',
                    'qualityBrand:id,name_en,name_ar,logo'
                ]);

            // تطبيق فلتر التاجر
            if ($storeFilter !== 'all') {
                $query->where('user_id', $storeFilter);
            }

            // تطبيق فلتر الكوالتي
            if ($qualityFilter !== 'all') {
                $query->where('brand_quality_id', $qualityFilter);
            }

            $merchants = $query->get();

            foreach ($merchants as $merchant) {
                $filteredMerchants->push([
                    'product' => $product,
                    'merchant' => $merchant,
                    'is_alternative' => !$prods->contains('id', $product->id)
                ]);
            }
        }

        // تطبيق الترتيب
        $filteredMerchants = $this->applySorting($filteredMerchants, $sortBy);

        // جمع كل التجار المتاحين للفلترة
        $availableStores = collect();
        $availableQualities = collect();

        foreach ($allProducts as $product) {
            $merchants = $product->merchantProducts()
                ->where('status', 1)
                ->with(['user:id,name,shop_name', 'qualityBrand:id,name_en,name_ar'])
                ->get();

            foreach ($merchants as $merchant) {
                if ($merchant->user) {
                    $availableStores->put($merchant->user_id, $merchant->user);
                }
                if ($merchant->qualityBrand) {
                    $availableQualities->put($merchant->brand_quality_id, $merchant->qualityBrand);
                }
            }
        }

        return view('frontend.search-results', [
            'sku' => $sku,
            'alternatives' => $alternatives,
            'prods' => $prods,
            'filteredMerchants' => $filteredMerchants,
            'availableStores' => $availableStores,
            'availableQualities' => $availableQualities,
            'storeFilter' => $storeFilter,
            'qualityFilter' => $qualityFilter,
            'sortBy' => $sortBy,
        ]);
    }

    private function applySorting($merchants, $sortBy)
    {
        switch ($sortBy) {
            case 'sku_asc':
                return $merchants->sortBy(function ($item) {
                    return $item['product']->sku ?? '';
                });

            case 'sku_desc':
                return $merchants->sortByDesc(function ($item) {
                    return $item['product']->sku ?? '';
                });

            case 'price_asc':
                return $merchants->sortBy(function ($item) {
                    return $item['merchant']->price ?? PHP_INT_MAX;
                });

            case 'price_desc':
                return $merchants->sortByDesc(function ($item) {
                    return $item['merchant']->price ?? 0;
                });

            case 'stock_desc':
                return $merchants->sortByDesc(function ($item) {
                    return $item['merchant']->stock ?? 0;
                });

            case 'newest':
                return $merchants->sortByDesc(function ($item) {
                    return $item['merchant']->created_at ?? '';
                });

            default:
                // Default: in stock first, then by price
                return $merchants->sort(function ($a, $b) {
                    $stockA = ($a['merchant']->stock ?? 0) > 0 ? 1 : 0;
                    $stockB = ($b['merchant']->stock ?? 0) > 0 ? 1 : 0;

                    if ($stockA !== $stockB) {
                        return $stockB - $stockA; // In stock first
                    }

                    return ($a['merchant']->price ?? PHP_INT_MAX) <=> ($b['merchant']->price ?? PHP_INT_MAX);
                });
        }
    }
}
