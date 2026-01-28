{{--
    Cart Item Row
    Variables: $item (pre-computed display array from CartItemDTO::toDisplayArray()), $issue (optional validation issue)

    All data is pre-computed in MerchantCartManager - direct array access (DATA_FLOW_POLICY)
--}}
<div class="m-cart__item {{ !empty($issue) ? 'm-cart__item--has-issue' : '' }}"
     id="cart-row-{{ $item['domKey'] ?? '' }}"
     data-cart-key="{{ $item['key'] ?? '' }}"
     data-branch-id="{{ $item['branch_id'] ?? 0 }}">

    {{-- Issue Banner --}}
    @if(!empty($issue))
        <div class="m-cart__item-issue m-cart__item-issue--{{ $issue['type'] ?? '' }}">
            <i class="fas fa-exclamation-triangle"></i>
            <span>{{ $issue['message'] ?? '' }}</span>
        </div>
    @endif

    {{-- Item Image --}}
    <div class="m-cart__item-image">
        <a href="{{ $item['itemUrl'] ?? '#' }}">
            <img src="{{ $item['photoUrl'] ?? asset('assets/images/noimage.png') }}"
                 alt="{{ $item['localizedName'] ?? '' }}"
                 loading="lazy"
                 onerror="this.onerror=null; this.src='{{ asset('assets/images/noimage.png') }}';">
        </a>
    </div>

    {{-- Item Details --}}
    <div class="m-cart__item-details">
        <a href="{{ $item['itemUrl'] ?? '#' }}" class="m-cart__item-name">
            {{ Str::limit($item['localizedName'] ?? '', 60) }}
        </a>

        {{-- Part Number --}}
        <div class="m-cart__item-meta">
            @if($item['part_number'] ?? null)
                <span class="m-cart__item-part_number">{{ $item['part_number'] }}</span>
            @endif
        </div>

        {{-- Brand & Quality Brand Badges --}}
        <div class="m-cart__item-brands">
            @if($item['localizedBrandName'] ?? null)
                <span class="m-cart__item-brand" title="@lang('Brand')">
                    @if($item['brand_logo'] ?? null)
                        <img src="{{ $item['brand_logo'] }}" alt="{{ $item['localizedBrandName'] }}" class="m-cart__brand-logo">
                    @endif
                    <span>{{ $item['localizedBrandName'] }}</span>
                </span>
            @endif
            @if($item['localizedQualityBrandName'] ?? null)
                <span class="m-cart__item-quality" title="@lang('Quality')">
                    @if($item['quality_brand_logo'] ?? null)
                        <img src="{{ $item['quality_brand_logo'] }}" alt="{{ $item['localizedQualityBrandName'] }}" class="m-cart__quality-logo">
                    @endif
                    <span>{{ $item['localizedQualityBrandName'] }}</span>
                </span>
            @endif
            {{-- Fitment Button --}}
            @if(($item['catalog_item_id'] ?? 0) > 0 && ($item['fitment_count'] ?? 0) > 0)
                <button type="button" class="catalog-btn catalog-btn-outline catalog-btn-sm fitment-details-btn"
                        data-catalog-item-id="{{ $item['catalog_item_id'] }}"
                        data-part-number="{{ $item['part_number'] ?? '' }}"
                        title="@lang('Vehicle Compatibility')">
                    @if(($item['fitment_count'] ?? 0) === 1 && !empty($item['fitment_brands'][0]['logo']))
                        <img src="{{ $item['fitment_brands'][0]['logo'] }}" alt="" class="catalog-btn__logo">
                    @else
                        <i class="fas fa-car"></i>
                    @endif
                    @if(($item['fitment_count'] ?? 0) === 1)
                        <span>{{ $item['fitment_brands'][0]['name'] ?? '' }}</span>
                    @else
                        <span>@lang('Fits')</span>
                        <span class="catalog-badge catalog-badge-sm">{{ $item['fitment_count'] }}</span>
                    @endif
                </button>
            @endif
        </div>

        {{-- Variants --}}
        @if (!empty($item['size']) || !empty($item['color']))
            <div class="m-cart__item-variants">
                @if (!empty($item['color']))
                    <span class="m-cart__item-color" style="--swatch-color: #{{ ltrim($item['color'], '#') }};"></span>
                @endif
                @if (!empty($item['size']))
                    <span class="m-cart__item-size">{{ $item['size'] }}</span>
                @endif
            </div>
        @endif

        {{-- Preorder Badge --}}
        @if ($item['preordered'] ?? false)
            <span class="m-cart__item-preorder">
                <i class="fas fa-clock"></i> @lang('Preorder')
            </span>
        @endif

        {{-- Discount Badge --}}
        @if ($item['discountPercent_formatted'] ?? null)
            <span class="m-cart__item-discount-badge">
                -{{ $item['discountPercent_formatted'] }}%
            </span>
        @endif

        {{-- Mobile Price --}}
        <div class="m-cart__item-price-mobile">
            {{ monetaryUnit()->convertAndFormat($item['effective_price'] ?? $item['unit_price'] ?? 0) }}
        </div>
    </div>

    {{-- Unit Price (Desktop) --}}
    <div class="m-cart__item-price">
        {{ monetaryUnit()->convertAndFormat($item['effective_price'] ?? $item['unit_price'] ?? 0) }}
    </div>

    {{-- Quantity Controls --}}
    <div class="m-cart__item-qty">
        <div class="m-cart__qty-controls">
            <button type="button"
                    class="m-cart__qty-btn m-cart__qty-btn--decrease"
                    data-action="decrease"
                    data-cart-key="{{ $item['key'] ?? '' }}"
                    {{ ($item['qty'] ?? 1) <= ($item['min_qty'] ?? 1) ? 'disabled' : '' }}>
                <i class="fas fa-minus"></i>
            </button>
            <input type="number"
                   class="m-cart__qty-input"
                   value="{{ $item['qty'] ?? 1 }}"
                   min="{{ $item['min_qty'] ?? 1 }}"
                   max="{{ ($item['preordered'] ?? false) || ($item['stock'] ?? 0) <= 0 ? 9999 : $item['stock'] }}"
                   data-cart-key="{{ $item['key'] ?? '' }}"
                   readonly>
            <button type="button"
                    class="m-cart__qty-btn m-cart__qty-btn--increase"
                    data-action="increase"
                    data-cart-key="{{ $item['key'] ?? '' }}"
                    {{ !($item['preordered'] ?? false) && ($item['stock'] ?? 0) > 0 && ($item['qty'] ?? 1) >= ($item['stock'] ?? 0) ? 'disabled' : '' }}>
                <i class="fas fa-plus"></i>
            </button>
        </div>

        {{-- Stock Info --}}
        @if (!($item['preordered'] ?? false) && ($item['stock'] ?? 0) > 0)
            <div class="m-cart__stock-info {{ ($item['stock'] ?? 0) <= 5 ? 'm-cart__stock-info--low' : '' }}">
                @if (($item['stock'] ?? 0) <= 5)
                    <i class="fas fa-exclamation-circle"></i>
                    @lang('Only') {{ $item['stock'] }} @lang('left')
                @else
                    <i class="fas fa-check-circle"></i>
                    @lang('In Stock')
                @endif
            </div>
        @endif
    </div>

    {{-- Total Price --}}
    <div class="m-cart__item-total">
        <span class="m-cart__item-total-value" data-unit-price="{{ $item['effective_price'] ?? $item['unit_price'] ?? 0 }}">
            {{ monetaryUnit()->convertAndFormat($item['total_price'] ?? 0) }}
        </span>
    </div>

    {{-- Remove Button --}}
    <div class="m-cart__item-actions">
        <button type="button"
                class="m-cart__remove-btn"
                data-action="remove"
                data-cart-key="{{ $item['key'] ?? '' }}"
                title="@lang('Remove')">
            <i class="fas fa-trash-alt"></i>
        </button>
    </div>
</div>
