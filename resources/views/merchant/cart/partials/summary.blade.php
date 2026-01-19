{{--
    Cart Summary
    Variables: $totals (array), $branchId (optional - for branch-specific checkout)
--}}
@php
    $subtotal = (float) ($totals['subtotal'] ?? 0);
    $discount = (float) ($totals['discount'] ?? 0);
    $total = (float) ($totals['total'] ?? 0);
    $qty = (int) ($totals['qty'] ?? 0);
@endphp

<div class="m-cart__summary" id="cart-summary">
    <h4 class="m-cart__summary-title">@lang('Purchase Summary')</h4>

    <div class="m-cart__summary-rows">
        {{-- Subtotal --}}
        <div class="m-cart__summary-row">
            <span class="m-cart__summary-label">
                @lang('Subtotal') <small>({{ $qty }} @lang('items')</small>)
            </span>
            <span class="m-cart__summary-value" id="cart-subtotal">
                {{ monetaryUnit()->convertAndFormat($subtotal) }}
            </span>
        </div>

        {{-- Discount (if any) --}}
        @if ($discount > 0)
            <div class="m-cart__summary-row m-cart__summary-row--discount">
                <span class="m-cart__summary-label">
                    <i class="fas fa-tag"></i> @lang('Wholesale Discount')
                </span>
                <span class="m-cart__summary-value">
                    -{{ monetaryUnit()->convertAndFormat($discount) }}
                </span>
            </div>
        @endif

        {{-- Shipping note --}}
        <div class="m-cart__summary-row m-cart__summary-row--info">
            <span class="m-cart__summary-label">
                <i class="fas fa-truck"></i> @lang('Shipping')
            </span>
            <span class="m-cart__summary-value m-cart__summary-value--info">
                @lang('Calculated at checkout')
            </span>
        </div>

        {{-- Divider --}}
        <hr class="m-cart__summary-divider">

        {{-- Total --}}
        <div class="m-cart__summary-row m-cart__summary-row--total">
            <span class="m-cart__summary-label">@lang('Total')</span>
            <span class="m-cart__summary-value m-cart__summary-value--total" id="cart-total">
                {{ monetaryUnit()->convertAndFormat($total) }}
            </span>
        </div>
    </div>

    {{-- Checkout Button --}}
    @if(isset($branchId) && $branchId > 0)
        {{-- Single Branch Checkout --}}
        <a href="{{ route('branch.checkout.address', ['branchId' => $branchId]) }}"
           class="m-btn m-btn--primary m-btn--lg m-btn--block m-cart__checkout-btn">
            <i class="fas fa-lock me-2"></i>
            @lang('Proceed to Checkout')
        </a>
    @else
        {{-- Multi-Branch: Show checkout per branch group --}}
        <p class="m-cart__summary-note">
            <i class="fas fa-info-circle"></i>
            @lang('Items from different branches will be checked out separately')
        </p>
    @endif

    {{-- Continue Shopping --}}
    <a href="{{ route('front.index') }}" class="m-btn m-btn--outline m-btn--block m-cart__continue-btn">
        <i class="fas fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} me-2"></i>
        @lang('Continue Shopping')
    </a>

    {{-- Clear Cart --}}
    <button type="button"
            class="m-btn m-btn--text m-btn--block m-cart__clear-btn"
            data-action="clear-all">
        <i class="fas fa-trash me-2"></i>
        @lang('Clear Cart')
    </button>
</div>
