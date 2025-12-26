{{--
    Shipping Quote Button Component
    ================================
    A reusable button that calculates and displays shipping costs.

    Usage:
    <x-shipping-quote-button :vendor-id="$vendorId" :weight="$weight" />

    Or with product:
    <x-shipping-quote-button :product="$product" />

    Props:
    - vendor-id (required): The vendor's user ID
    - weight (optional): Product weight in kg (default: 0.5)
    - product (optional): Product object (will extract vendor_id and weight)
    - product-name (optional): Product name for modal title
    - class (optional): Additional CSS classes
--}}

@props([
    'vendorId' => null,
    'weight' => 0.5,
    'product' => null,
    'productName' => '',
    'class' => '',
])

@php
    // Extract from product if provided
    if ($product) {
        $vendorId = $vendorId ?? ($product->user_id ?? $product->vendor_id ?? null);
        $weight = $product->weight ?? $weight;
        $productName = $productName ?: (getLocalizedProductName($product) ?? '');
    }
@endphp

@if($vendorId)
<button type="button"
    class="m-shipping-quote-btn {{ $class }}"
    data-shipping-quote
    data-vendor-id="{{ $vendorId }}"
    data-weight="{{ $weight }}"
    data-product-name="{{ $productName }}"
>
    <i class="fas fa-truck m-shipping-quote-btn__icon"></i>
    <span class="m-shipping-quote-btn__text">@lang('احسب الشحن')</span>
</button>
@endif
