{{--
    =====================================================================
    MERCHANT CART PAGE - v4 (Clean Implementation)
    =====================================================================
    Controller: App\Http\Controllers\Front\MerchantCartController@index
    Service: App\Services\Cart\MerchantCartManager

    Variables passed:
    - $cart: Full cart data
    - $byMerchant: Items grouped by merchant
    - $issues: Validation issues (stock, price changes, etc.)
    - $isEmpty: Whether cart is empty
    =====================================================================
--}}
@extends('layouts.front')

@section('content')
    {{-- Breadcrumb Section --}}
    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-name">@lang('Cart')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="{{ route('merchant-cart.index') }}">@lang('Cart')</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Main Cart Section --}}
    <section class="gs-cart-section muaadh-section-gray py-4">
        <div class="container">
            @if ($isEmpty)
                {{-- Empty Cart --}}
                @include('merchant.cart.partials.empty')
            @else
                {{-- Cart has issues notification --}}
                @if (!empty($issues))
                    <div class="m-alert m-alert--warning mb-4" id="cart-issues-alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>@lang('Some items in your cart have issues. Please review before checkout.')</span>
                        <button type="button" class="m-alert__close" onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif

                <div class="row">
                    {{-- Cart Items --}}
                    <div class="col-lg-8">
                        @foreach ($byMerchant as $merchantId => $merchantGroup)
                            <div class="m-cart__merchant" data-merchant-id="{{ $merchantId }}">
                                {{-- Merchant Header --}}
                                <div class="m-cart__merchant-header">
                                    <div class="m-cart__merchant-info">
                                        <i class="fas fa-store"></i>
                                        @php
                                            $merchantName = app()->getLocale() === 'ar' && !empty($merchantGroup['merchant_name_ar'])
                                                ? $merchantGroup['merchant_name_ar']
                                                : ($merchantGroup['merchant_name'] ?? __('Merchant'));
                                        @endphp
                                        <span class="m-cart__merchant-name">{{ $merchantName }}</span>
                                        <span class="m-cart__merchant-count">
                                            <span class="count-value">{{ $merchantGroup['qty'] ?? 0 }}</span> @lang('Items')
                                        </span>
                                    </div>
                                    <div class="m-cart__merchant-actions">
                                        <a href="{{ route('merchant.checkout.index', ['merchantId' => $merchantId]) }}"
                                           class="m-btn m-btn--primary m-btn--sm">
                                            <i class="fas fa-shopping-bag me-1"></i>
                                            @lang('Checkout')
                                        </a>
                                    </div>
                                </div>

                                {{-- Items Table --}}
                                <div class="m-cart__body">
                                    {{-- Table Header (Desktop) --}}
                                    <div class="m-cart__table-header d-none d-lg-flex">
                                        <div class="m-cart__col m-cart__col--product">@lang('Item')</div>
                                        <div class="m-cart__col m-cart__col--price">@lang('Price')</div>
                                        <div class="m-cart__col m-cart__col--qty">@lang('Quantity')</div>
                                        <div class="m-cart__col m-cart__col--total">@lang('Total')</div>
                                        <div class="m-cart__col m-cart__col--actions"></div>
                                    </div>

                                    {{-- Items --}}
                                    <div class="m-cart__items" id="merchant-items-{{ $merchantId }}">
                                        @foreach ($merchantGroup['items'] as $key => $item)
                                            @include('merchant.cart.partials.item', [
                                                'item' => $item,
                                                'issue' => $issues[$key] ?? null
                                            ])
                                        @endforeach
                                    </div>

                                    {{-- Merchant Subtotal --}}
                                    <div class="m-cart__merchant-footer">
                                        <div class="m-cart__merchant-subtotal">
                                            <span>@lang('Subtotal'):</span>
                                            <strong class="merchant-subtotal-value">
                                                {{ monetaryUnit()->convertAndFormat($merchantGroup['subtotal'] ?? 0) }}
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Cart Summary Sidebar --}}
                    <div class="col-lg-4">
                        <div class="m-cart__sidebar">
                            @include('merchant.cart.partials.summary', [
                                'totals' => $cart['totals'] ?? [],
                                'merchantId' => count($byMerchant) === 1 ? array_key_first($byMerchant) : null
                            ])
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
<script src="{{ asset('assets/front/js/merchant-cart.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize cart
        if (typeof MerchantCart !== 'undefined') {
            MerchantCart.init({
                endpoints: {
                    add: '{{ route('merchant-cart.add') }}',
                    update: '{{ route('merchant-cart.update') }}',
                    increase: '{{ route('merchant-cart.increase') }}',
                    decrease: '{{ route('merchant-cart.decrease') }}',
                    remove: '{{ route('merchant-cart.remove.post') }}',
                    clear: '{{ route('merchant-cart.clear') }}',
                    summary: '{{ route('merchant-cart.summary') }}',
                    count: '{{ route('merchant-cart.count') }}',
                },
                csrfToken: '{{ csrf_token() }}',
            });
        }
    });
</script>
@endpush

@push('styles')
<style>
/* Cart Page Specific Styles */
.m-cart {
    padding: 1.5rem 0;
}

.m-cart__merchant {
    background: var(--surface-primary, #fff);
    border-radius: var(--radius-lg, 12px);
    box-shadow: var(--shadow-sm);
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.m-cart__merchant-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: var(--surface-secondary, #f8f9fa);
    border-bottom: 1px solid var(--border-default, #e9ecef);
}

.m-cart__merchant-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.m-cart__merchant-info i {
    color: var(--action-primary);
    font-size: 1.25rem;
}

.m-cart__merchant-name {
    font-weight: 600;
    color: var(--text-primary);
}

.m-cart__merchant-count {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.m-cart__body {
    padding: 1rem 1.5rem;
}

.m-cart__table-header {
    display: flex;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-default);
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.m-cart__col--product { flex: 3; }
.m-cart__col--price { flex: 1; text-align: center; }
.m-cart__col--qty { flex: 1.5; text-align: center; }
.m-cart__col--total { flex: 1; text-align: center; }
.m-cart__col--actions { width: 40px; }

.m-cart__item {
    display: flex;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border-light, #f0f0f0);
    gap: 1rem;
}

.m-cart__item:last-child {
    border-bottom: none;
}

.m-cart__item--has-issue {
    background: rgba(var(--warning-rgb, 255, 193, 7), 0.05);
    margin: 0 -1.5rem;
    padding: 1rem 1.5rem;
}

.m-cart__item-issue {
    width: 100%;
    padding: 0.5rem 1rem;
    background: var(--warning-light, #fff3cd);
    color: var(--warning-dark, #856404);
    border-radius: var(--radius-sm);
    font-size: 0.875rem;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.m-cart__item-image {
    width: 80px;
    height: 80px;
    flex-shrink: 0;
}

.m-cart__item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: var(--radius-md, 8px);
}

.m-cart__item-details {
    flex: 3;
    min-width: 0;
}

.m-cart__item-name {
    display: block;
    font-weight: 500;
    color: var(--text-primary);
    text-decoration: none;
    margin-bottom: 0.25rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m-cart__item-name:hover {
    color: var(--action-primary);
}

.m-cart__item-meta {
    display: flex;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: var(--text-secondary);
    margin-bottom: 0.25rem;
}

.m-cart__item-variants {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.25rem;
}

.m-cart__item-color {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--swatch-color);
    border: 2px solid var(--border-default);
}

.m-cart__item-size {
    padding: 0.125rem 0.5rem;
    background: var(--surface-secondary);
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
}

.m-cart__item-preorder {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.125rem 0.5rem;
    background: var(--info-light, #cce5ff);
    color: var(--info-dark, #004085);
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.m-cart__item-discount-badge {
    display: inline-flex;
    padding: 0.125rem 0.5rem;
    background: var(--danger-light, #f8d7da);
    color: var(--danger-dark, #721c24);
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 600;
    margin-top: 0.25rem;
}

.m-cart__item-price {
    flex: 1;
    text-align: center;
    font-weight: 500;
    color: var(--text-primary);
}

.m-cart__item-price-mobile {
    display: none;
    font-weight: 600;
    color: var(--action-primary);
    margin-top: 0.5rem;
}

.m-cart__item-qty {
    flex: 1.5;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.m-cart__qty-controls {
    display: flex;
    align-items: center;
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    overflow: hidden;
}

.m-cart__qty-btn {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--surface-secondary);
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.m-cart__qty-btn:hover:not(:disabled) {
    background: var(--action-primary);
    color: #fff;
}

.m-cart__qty-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.m-cart__qty-input {
    width: 50px;
    height: 36px;
    text-align: center;
    border: none;
    font-weight: 500;
    background: transparent;
}

.m-cart__stock-info {
    font-size: 0.75rem;
    color: var(--success-dark, #155724);
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.m-cart__stock-info--low {
    color: var(--warning-dark, #856404);
}

.m-cart__item-total {
    flex: 1;
    text-align: center;
    font-weight: 600;
    color: var(--action-primary);
}

.m-cart__item-actions {
    width: 40px;
}

.m-cart__remove-btn {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.2s;
}

.m-cart__remove-btn:hover {
    background: var(--danger-light, #f8d7da);
    border-color: var(--action-danger);
    color: var(--action-danger);
}

.m-cart__merchant-footer {
    padding-top: 1rem;
    border-top: 1px solid var(--border-default);
    text-align: end;
}

.m-cart__merchant-subtotal {
    font-size: 1rem;
}

.m-cart__merchant-subtotal strong {
    font-size: 1.125rem;
    color: var(--action-primary);
    margin-inline-start: 0.5rem;
}

/* Summary Sidebar */
.m-cart__sidebar {
    position: sticky;
    top: 100px;
}

.m-cart__summary {
    background: var(--surface-primary, #fff);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    padding: 1.5rem;
}

.m-cart__summary-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--border-default);
}

.m-cart__summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.m-cart__summary-label {
    color: var(--text-secondary);
}

.m-cart__summary-value {
    font-weight: 500;
    color: var(--text-primary);
}

.m-cart__summary-row--discount .m-cart__summary-value {
    color: var(--action-success);
}

.m-cart__summary-value--info {
    font-weight: 400;
    font-size: 0.875rem;
}

.m-cart__summary-divider {
    margin: 1rem 0;
    border-color: var(--border-default);
}

.m-cart__summary-row--total {
    font-size: 1.125rem;
}

.m-cart__summary-row--total .m-cart__summary-label {
    color: var(--text-primary);
    font-weight: 600;
}

.m-cart__summary-value--total {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--action-primary);
}

.m-cart__checkout-btn {
    margin-top: 1.5rem;
}

.m-cart__continue-btn {
    margin-top: 0.75rem;
}

.m-cart__clear-btn {
    margin-top: 0.5rem;
    color: var(--text-secondary);
}

.m-cart__clear-btn:hover {
    color: var(--action-danger);
}

.m-cart__summary-note {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-top: 1rem;
    padding: 0.75rem;
    background: var(--surface-secondary);
    border-radius: var(--radius-md);
}

.m-cart__summary-note i {
    color: var(--info-dark);
}

/* Empty Cart */
.m-cart__empty {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--surface-primary, #fff);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
}

.m-cart__empty-icon {
    width: 100px;
    height: 100px;
    margin: 0 auto 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--surface-secondary);
    border-radius: 50%;
}

.m-cart__empty-icon i {
    font-size: 3rem;
    color: var(--text-secondary);
}

.m-cart__empty h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.m-cart__empty p {
    color: var(--text-secondary);
    margin-bottom: 1.5rem;
}

/* Mobile Responsive */
@media (max-width: 991.98px) {
    .m-cart__item {
        flex-wrap: wrap;
    }

    .m-cart__item-details {
        flex: 1;
    }

    .m-cart__item-price {
        display: none;
    }

    .m-cart__item-price-mobile {
        display: block;
    }

    .m-cart__item-qty {
        order: 4;
        flex: auto;
        width: 50%;
        align-items: flex-start;
    }

    .m-cart__item-total {
        order: 5;
        flex: auto;
        width: 40%;
        text-align: end;
    }

    .m-cart__item-actions {
        position: absolute;
        top: 1rem;
        right: 1rem;
    }

    .m-cart__item {
        position: relative;
        padding-right: 50px;
    }

    .m-cart__sidebar {
        position: static;
        margin-top: 1rem;
    }
}

/* RTL Support */
[dir="rtl"] .m-cart__item-actions {
    right: auto;
    left: 1rem;
}

[dir="rtl"] .m-cart__item {
    padding-right: 0;
    padding-left: 50px;
}
</style>
@endpush
