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

    DATA FLOW POLICY: All processing in ShippingQuoteButton component class.
--}}

{{-- Component renders only if canRender is true (handled by shouldRender) --}}
<button type="button"
    class="m-shipping-quote-btn {{ $class }}"
    data-shipping-quote
    data-merchant-id="{{ $merchantId }}"
    data-branch-id="{{ $branchId }}"
    data-weight="{{ $weight }}"
    data-catalog-item-name="{{ $itemName }}"
    title="@lang('احسب الشحن')">
    <i class="fas fa-truck"></i>
    <span>@lang('احسب الشحن')</span>
</button>
