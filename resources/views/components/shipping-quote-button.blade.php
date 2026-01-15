{{--
    Shipping Quote Button Component
    ================================
    A reusable button that calculates and displays shipping costs.

    Usage:
    <x-shipping-quote-button :merchant-user-id="$merchantUserId" :weight="$weight" />

    Or with merchant item:
    <x-shipping-quote-button :merchant-item="$merchantItem" />

    Props:
    - merchant-user-id (required): The merchant's user ID
    - weight (optional): Catalog item weight in kg (default: 0.5)
    - merchant-item (optional): MerchantItem object (will extract user_id and weight)
    - catalog-item-name (optional): Catalog item name for modal name
    - class (optional): Additional CSS classes
--}}

@props([
    'merchantUserId' => null,
    'weight' => 0.5,
    'merchantItem' => null,
    'catalogItemName' => '',
    'class' => '',
])

@php
    // Extract from merchant item if provided
    if ($merchantItem) {
        $merchantUserId = $merchantUserId ?? ($merchantItem->user_id ?? null);
        $weight = $merchantItem->catalogItem?->weight ?? $weight;
        $catalogItemName = $catalogItemName ?: (getLocalizedCatalogItemName($merchantItem->catalogItem) ?? '');
    }
@endphp

@if($merchantUserId)
<button type="button"
    class="m-shipping-quote-btn {{ $class }}"
    data-shipping-quote
    data-merchant-user-id="{{ $merchantUserId }}"
    data-weight="{{ $weight }}"
    data-catalog-item-name="{{ $catalogItemName }}"
>
    <i class="fas fa-truck m-shipping-quote-btn__icon"></i>
    <span class="m-shipping-quote-btn__text">@lang('احسب الشحن')</span>
</button>
@endif
