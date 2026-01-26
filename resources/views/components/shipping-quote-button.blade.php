{{--
    Shipping Quote Button
    =====================
    Usage with explicit values (recommended):
    <x-shipping-quote-button
        :merchant-id="$merchantId"
        :branch-id="$branchId"
        :weight="$weight"
        :item-name="$itemName" />

    Usage with MerchantItem (requires eager loaded catalogItem):
    <x-shipping-quote-button :mp="$merchantItem" />

    DATA FLOW POLICY: Values should be pre-computed in controller.
    If using :mp, ensure catalogItem relationship is eager loaded.
--}}

@props([
    'mp' => null,
    'merchantId' => null,
    'branchId' => null,
    'weight' => null,
    'itemName' => '',
    'class' => ''
])

@php $mId = $merchantId ?? ($mp->user_id ?? null); $bId = $branchId ?? ($mp->merchant_branch_id ?? null); @endphp
@php $w = $weight ?? ($mp && $mp->relationLoaded('catalogItem') ? $mp->catalogItem?->weight : null); @endphp
@php $name = $itemName ?: ($mp && $mp->relationLoaded('catalogItem') ? getLocalizedCatalogItemName($mp->catalogItem) : ''); $canRender = $mId && $bId && $w && $w > 0; @endphp

@if($canRender)
<button type="button"
    class="m-shipping-quote-btn {{ $class }}"
    data-shipping-quote
    data-merchant-id="{{ $mId }}"
    data-branch-id="{{ $bId }}"
    data-weight="{{ $w }}"
    data-catalog-item-name="{{ $name }}"
    title="@lang('احسب الشحن')">
    <i class="fas fa-truck"></i>
    <span>@lang('احسب الشحن')</span>
</button>
@endif
