<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\SkuAlternative;
use App\Models\User;
use App\Models\QualityBrand;
use Livewire\Component;
use Livewire\Attributes\Url;

class SearchResultsPage extends Component
{
    public $sku;
    public $alternatives;
    public $prods;

    #[Url(keep: true)]
    public $storeFilter = 'all';

    #[Url(keep: true)]
    public $qualityFilter = 'all';

    #[Url(keep: true)]
    public $sortBy = 'sku_asc';

    public function mount($sku)
    {
        $this->sku = $sku;

        // المنتج الأساسي
        $this->prods = Product::where('sku', $sku)->get();

        // جلب السطر من جدول sku_alternatives
        $skuAlternative = SkuAlternative::where('sku', $this->sku)->first();

        if ($skuAlternative && $skuAlternative->group_id) {
            // جلب كل الـ SKUs في نفس القروب مع استثناء نفسه
            $alternativeSkus = SkuAlternative::where('group_id', $skuAlternative->group_id)
                ->where('sku', '<>', $this->sku)
                ->pluck('sku')
                ->toArray();

            if (!empty($alternativeSkus)) {
                $this->alternatives = Product::whereIn('sku', $alternativeSkus)->get();
            } else {
                $this->alternatives = collect();
            }
        } else {
            $this->alternatives = collect();
        }
    }

    public function applyFilters()
    {
        // Method to trigger re-render when filters change
        $this->dispatch('filtersApplied');
    }

    public function render()
    {
        // جمع كل المنتجات (الأساسية + البدائل)
        $allProducts = $this->prods->merge($this->alternatives);

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
            if ($this->storeFilter !== 'all') {
                $query->where('user_id', $this->storeFilter);
            }

            // تطبيق فلتر الكوالتي
            if ($this->qualityFilter !== 'all') {
                $query->where('brand_quality_id', $this->qualityFilter);
            }

            $merchants = $query->get();

            foreach ($merchants as $merchant) {
                $filteredMerchants->push([
                    'product' => $product,
                    'merchant' => $merchant,
                    'is_alternative' => !$this->prods->contains('id', $product->id)
                ]);
            }
        }

        // تطبيق الترتيب
        $filteredMerchants = $this->applySorting($filteredMerchants);

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

        return view('livewire.search-results-page', [
            'alternatives' => $this->alternatives,
            'prods' => $this->prods,
            'filteredMerchants' => $filteredMerchants,
            'availableStores' => $availableStores,
            'availableQualities' => $availableQualities,
        ]);
    }

    private function applySorting($merchants)
    {
        switch ($this->sortBy) {
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
