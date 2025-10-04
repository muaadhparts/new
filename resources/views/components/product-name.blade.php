{{-- Product Name Component with SKU and Language Support --}}
@props([
    'product' => null,
    'item' => null, // for cart items format
    'vendorId' => null,
    'merchantProductId' => null,
    'showSku' => true,
    'target' => '_blank',
    'class' => '',
    'nameClass' => '',
    'skuClass' => 'text-muted small'
])

@php
    // Handle different data formats (direct product object vs cart item format)
    $productData = $product ?? $item;

    if (!$productData) {
        return;
    }

    // Extract data based on format
    if (isset($productData['item'])) {
        // Cart item format: $product['item']['name']
        $name = $productData['item']['name'] ?? '';
        $labelAr = $productData['item']['label_ar'] ?? '';
        $sku = $productData['item']['sku'] ?? '';
        $slug = $productData['item']['slug'] ?? '';
        $userId = $vendorId ?? $productData['item']['user_id'] ?? $productData['user_id'] ?? 0;
        // For merchant_product_id: prioritize explicit prop, then try to infer
        $mpId = $merchantProductId;
        if (!$mpId) {
            // Try to get MP ID if productData is actually a MerchantProduct
            $mpId = ($productData instanceof \App\Models\MerchantProduct)
                ? $productData->id
                : ($productData['item']['id'] ?? $productData['id'] ?? null);
        }
    } else {
        // Direct product object format: $product->name
        $name = $productData->name ?? $productData['name'] ?? '';
        $labelAr = $productData->label_ar ?? $productData['label_ar'] ?? '';
        $sku = $productData->sku ?? $productData['sku'] ?? '';
        $slug = $productData->slug ?? $productData['slug'] ?? '';
        $userId = $vendorId ?? $productData->user_id ?? $productData['user_id'] ?? 0;
        // For merchant_product_id: prioritize explicit prop
        $mpId = $merchantProductId;
        if (!$mpId) {
            // Check if this is a MerchantProduct or need to fetch one
            if ($productData instanceof \App\Models\MerchantProduct) {
                $mpId = $productData->id;
            } elseif ($productData instanceof \App\Models\Product && $userId) {
                // Fetch first active MP for this product and vendor
                $mp = $productData->merchantProducts()->where('user_id', $userId)->where('status', 1)->first();
                $mpId = $mp->id ?? null;
            }
        }
    }

    // Language-aware name selection
    $displayName = app()->getLocale() == 'ar' && !empty($labelAr) ? $labelAr : $name;

    // SKU display
    $displaySku = !empty($sku) ? $sku : '-';

    // Route generation with new parameter structure
    $productRoute = !empty($slug) && $userId && $mpId
        ? route('front.product', ['slug' => $slug, 'vendor_id' => $userId, 'merchant_product_id' => $mpId])
        : '#';
@endphp

<div class="{{ $class }}">
    @if(!empty($slug))
        <a href="{{ $productRoute }}" target="{{ $target }}" class="{{ $nameClass }}">
            {{ $displayName }}
        </a>
    @else
        <span class="{{ $nameClass }}">{{ $displayName }}</span>
    @endif

    @if($showSku)
        <br>
        <small class="{{ $skuClass }}">
            @if(!empty($slug))
                <a href="{{ $productRoute }}" target="{{ $target }}">
                    @lang('SKU'): {{ $displaySku }}
                </a>
            @else
                @lang('SKU'): {{ $displaySku }}
            @endif
        </small>
    @endif
</div>

<style>
/* Product Name Component Styles */
.product-name-component a {
    color: var(--dark-color);
    text-decoration: none;
    transition: color var(--transition-fast);
    font-weight: 600;
}

.product-name-component a:hover {
    color: var(--primary-color);
}

.product-name-component small a {
    color: #6c757d;
    font-weight: 500;
}

.product-name-component small a:hover {
    color: var(--primary-color);
    text-decoration: underline;
}

/* RTL Support */
[dir="rtl"] .product-name-component {
    text-align: right;
}
</style>