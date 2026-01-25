{{--
    Shipping Quote Button
    =====================
    <x-shipping-quote-button :mp="$merchantItem" />

    يستخرج من MerchantItem:
    - user_id → merchant_id
    - merchant_branch_id → branch_id
    - catalogItem->weight → weight
--}}

@props(['mp' => null, 'class' => ''])

@php
    if (!$mp) {
        $render = false;
    } else {
        $merchantId = $mp->user_id;
        $branchId = $mp->merchant_branch_id;
        $weight = $mp->catalogItem?->weight;
        $name = getLocalizedCatalogItemName($mp->catalogItem) ?? '';
        $render = $merchantId && $branchId && $weight && $weight > 0;
    }
@endphp

@if($render)
<button type="button"
    class="m-shipping-quote-btn {{ $class }}"
    data-shipping-quote
    data-merchant-id="{{ $merchantId }}"
    data-branch-id="{{ $branchId }}"
    data-weight="{{ $weight }}"
    title="@lang('احسب الشحن')">
    <i class="fas fa-truck"></i>
    <span>@lang('احسب الشحن')</span>
</button>
@endif
