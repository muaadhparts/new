{{--
    Unified Cart Button Component
    =============================
    Single source of truth for all cart operations.

    REQUIRED: merchant_item_id (everything else derived from MerchantItem)

    Usage:
    <x-merchant-cart-button :mp="$merchantItem" />
    <x-merchant-cart-button :mp="$merchantItem" mode="compact" />
    <x-merchant-cart-button :mp="$merchantItem" mode="full" :show-qty="true" />

    DATA FLOW POLICY: All processing in MerchantCartButton component class.
--}}

<div class="m-cart-button {{ $class }}"
     id="{{ $uniqueId }}"
     data-mp-id="{{ $mpId }}"
     data-merchant-user-id="{{ $merchantUserId }}"
     data-catalog-item-id="{{ $catalogItemId }}"
     data-price="{{ $price }}"
     data-stock="{{ $stock }}"
     data-preordered="{{ $preordered ? '1' : '0' }}"
     data-min-qty="{{ $minQty }}"
     data-max-qty="{{ $maxQty }}"
     data-weight="{{ $weight }}"
     data-sizes="{{ json_encode($sizes) }}"
     data-size-qtys="{{ json_encode($sizeQtys) }}"
     data-size-prices="{{ json_encode($sizePrices) }}"
     data-colors="{{ json_encode($colors) }}"
     data-color-prices="{{ json_encode($colorPrices) }}"
     data-in-stock="{{ $inStock ? '1' : '0' }}">

    @if (!$inStock)
        {{-- Out of Stock State --}}
        <button type="button" class="m-cart-button__btn m-cart-button__btn--disabled" disabled>
            <i class="fas fa-ban"></i>
            <span>@lang('Out of Stock')</span>
        </button>
    @else
        {{-- Size Selector --}}
        @if ($hasSizes && $mode === 'full')
            <div class="m-cart-button__sizes">
                <label class="m-cart-button__label">@lang('Size'):</label>
                <div class="m-cart-button__size-options">
                    @foreach ($sizeData as $sizeItem)
                        <button type="button"
                                class="m-cart-button__size {{ $sizeItem['isDefault'] ? 'active' : '' }} {{ !$sizeItem['available'] ? 'disabled' : '' }}"
                                data-size="{{ $sizeItem['size'] }}"
                                data-size-qty="{{ $sizeItem['stock'] }}"
                                data-size-price="{{ $sizeItem['price'] }}"
                                {{ !$sizeItem['available'] ? 'disabled' : '' }}>
                            {{ $sizeItem['size'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Color Selector --}}
        @if ($hasColors && $mode === 'full')
            <div class="m-cart-button__colors">
                <label class="m-cart-button__label">@lang('Color'):</label>
                <div class="m-cart-button__color-options">
                    @foreach ($colorData as $colorItem)
                        <button type="button"
                                class="m-cart-button__color {{ $colorItem['isDefault'] ? 'active' : '' }}"
                                data-color="{{ $colorItem['color'] }}"
                                data-color-price="{{ $colorItem['price'] }}"
                                style="background-color: #{{ $colorItem['color'] }};"
                                name="#{{ $colorItem['color'] }}">
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Quantity Selector --}}
        @if ($showQty)
            <div class="m-cart-button__qty">
                <label class="m-cart-button__label">@lang('Qty'):</label>
                <div class="m-cart-button__qty-control">
                    <button type="button" class="m-cart-button__qty-btn m-cart-button__qty-minus" data-action="decrease">
                        <i class="fas fa-minus"></i>
                    </button>
                    <input type="text"
                           class="m-cart-button__qty-input"
                           value="{{ $defaultQty }}"
                           data-min="{{ $minQty }}"
                           data-max="{{ $maxQty }}"
                           readonly>
                    <button type="button" class="m-cart-button__qty-btn m-cart-button__qty-plus" data-action="increase">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                @if ($minQty > 1)
                    <small class="m-cart-button__min-notice">@lang('Min'): {{ $minQty }}</small>
                @endif
            </div>
        @endif

        {{-- Add to Cart Button --}}
        <button type="button" class="m-cart-button__btn m-cart-button__add m-cart-add">
            @if ($mode === 'icon-only')
                <i class="fas fa-shopping-cart"></i>
            @elseif ($mode === 'compact')
                <i class="fas fa-cart-plus"></i>
                <span>@lang('Add')</span>
            @else
                <i class="fas fa-cart-plus"></i>
                <span>@lang('Add to Cart')</span>
            @endif
        </button>

        {{-- Loading State --}}
        <div class="m-cart-button__loading" style="display: none;">
            <i class="fas fa-spinner fa-spin"></i>
        </div>
    @endif
</div>
