{{--
    Shipping Quote Button Component
    ================================
    تمرير MerchantItem مباشرة - البيانات من مصدرها

    Usage:
    <x-shipping-quote-button :mp="$mp" />

    البيانات المستخرجة من MerchantItem:
    - user_id → merchant_id
    - merchant_branch_id → branch_id
    - catalogItem->weight → weight
--}}

@props([
    'mp' => null,
    'class' => '',
])

@php
    // كل البيانات من MerchantItem مباشرة - لا فلوباك
    if (!$mp) {
        $canRender = false;
    } else {
        $merchantId = $mp->user_id;
        $branchId = $mp->merchant_branch_id;
        $weight = $mp->catalogItem?->weight;
        $catalogItemName = getLocalizedCatalogItemName($mp->catalogItem) ?? '';

        // يجب أن تكون كل القيم موجودة
        $canRender = $merchantId && $branchId && $weight && $weight > 0;
    }
@endphp

@if($canRender)
<button type="button"
    class="m-shipping-quote-btn {{ $class }}"
    data-shipping-quote
    data-merchant-id="{{ $merchantId }}"
    data-branch-id="{{ $branchId }}"
    data-weight="{{ $weight }}"
    data-catalog-item-name="{{ $catalogItemName }}"
>
    <i class="fas fa-truck m-shipping-quote-btn__icon"></i>
    <span class="m-shipping-quote-btn__text">@lang('احسب الشحن')</span>
</button>
@elseif($mp)
{{-- MerchantItem موجود لكن بيانات ناقصة --}}
<button type="button"
    class="m-shipping-quote-btn m-shipping-quote-btn--disabled {{ $class }}"
    disabled
    title="@lang(!($mp->merchant_branch_id ?? null) ? 'فرع التاجر غير محدد' : 'وزن المنتج غير محدد')"
>
    <i class="fas fa-truck m-shipping-quote-btn__icon"></i>
    <span class="m-shipping-quote-btn__text">@lang(!($mp->merchant_branch_id ?? null) ? 'الفرع غير محدد' : 'الوزن غير محدد')</span>
</button>
@endif
