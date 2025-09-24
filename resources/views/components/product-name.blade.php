{{-- Product Name Component with SKU and Language Support --}}
@props([
    'product' => null,
    'item' => null, // for cart items format
    'vendorId' => null,
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
    } else {
        // Direct product object format: $product->name
        $name = $productData->name ?? $productData['name'] ?? '';
        $labelAr = $productData->label_ar ?? $productData['label_ar'] ?? '';
        $sku = $productData->sku ?? $productData['sku'] ?? '';
        $slug = $productData->slug ?? $productData['slug'] ?? '';
        $userId = $vendorId ?? $productData->user_id ?? $productData['user_id'] ?? 0;
    }

    // Language-aware name selection
    $displayName = app()->getLocale() == 'ar' && !empty($labelAr) ? $labelAr : $name;

    // SKU display
    $displaySku = !empty($sku) ? $sku : '-';

    // Route generation
    $productRoute = !empty($slug) ? route('front.product', ['slug' => $slug, 'user' => $userId]) : '#';
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