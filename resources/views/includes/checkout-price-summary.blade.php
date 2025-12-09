{{--
    ============================================================================
    CHECKOUT PRICE SUMMARY COMPONENT
    ============================================================================

    A unified, professional price summary component for all checkout steps.

    Data Flow:
    - Step 1: products_total (from cart), tax (dynamic via JS)
    - Step 2: products_total + tax (from step1), shipping + packing (dynamic via JS)
    - Step 3: ALL data from step2 session (read-only)

    Required Variables:
    - $step: Current step (1, 2, or 3)
    - $curr: Currency object
    - $gs: General settings

    Step-specific:
    - Step 1: $productsTotal (cart total)
    - Step 2 & 3: $step1, $step2 (session objects)

    Optional:
    - $digital: Is cart digital only (default: false)
    - $is_vendor_checkout: Boolean
    - $vendor_id: Vendor ID

    ============================================================================
--}}

@php
    $currentStep = $step ?? 1;
    $isDigital = $digital ?? false;
    $currencySign = $curr->sign ?? 'SAR';
    $currencyFormat = $gs->currency_format ?? 0; // 0 = prefix, 1 = suffix

    // ============================================================================
    // STEP 3: Read ALL data from step2 session (Single Source of Truth)
    // ============================================================================
    if ($currentStep == 3 && isset($step2)) {
        // Products & Tax (from step1 originally, but we use step2 for consistency)
        $productsTotal = $step1->products_total ?? 0;
        $taxRate = $step2->tax_rate ?? ($step1->tax_rate ?? 0);
        $taxAmount = $step2->tax_amount ?? ($step1->tax_amount ?? 0);
        $taxLocation = $step2->tax_location ?? ($step1->tax_location ?? '');

        // Shipping
        $shippingCost = $step2->shipping_cost ?? 0;
        $originalShippingCost = $step2->original_shipping_cost ?? $shippingCost;
        $isFreeShipping = $step2->is_free_shipping ?? false;
        $freeShippingDiscount = $step2->free_shipping_discount ?? 0;
        $shippingCompany = $step2->shipping_company ?? '';

        // Packing
        $packingCost = $step2->packing_cost ?? 0;
        $packingCompany = $step2->packing_company ?? '';

        // Coupon - Read from step2 (already calculated in controller)
        $couponAmount = $step2->coupon_amount ?? 0;
        $couponCode = $step2->coupon_code ?? '';
        $couponPercentage = $step2->coupon_percentage ?? '';
        $couponId = $step2->coupon_id ?? null;
        $hasCoupon = $couponAmount > 0;

        // Totals
        $subtotalBeforeCoupon = $step2->subtotal_before_coupon ?? 0;
        $finalTotal = $step2->final_total ?? $step2->total ?? 0;
    } else {
        // Step 1 & 2: Initialize with defaults
        $productsTotal = $productsTotal ?? 0;
        $taxRate = 0;
        $taxAmount = 0;
        $taxLocation = '';
        $shippingCost = 0;
        $originalShippingCost = 0;
        $isFreeShipping = false;
        $freeShippingDiscount = 0;
        $shippingCompany = '';
        $packingCost = 0;
        $packingCompany = '';
        $couponAmount = 0;
        $couponCode = '';
        $couponPercentage = '';
        $couponId = null;
        $hasCoupon = false;
        $subtotalBeforeCoupon = $productsTotal;
        $finalTotal = $productsTotal;

        // For Step 2, get step1 data
        if ($currentStep == 2 && isset($step1)) {
            $productsTotal = $step1->products_total ?? 0;
            $taxRate = $step1->tax_rate ?? 0;
            $taxAmount = $step1->tax_amount ?? 0;
            $taxLocation = $step1->tax_location ?? '';
        }
    }

    // Helper function for price formatting
    $formatPrice = function($amount) use ($currencySign, $currencyFormat) {
        $formatted = number_format((float)$amount, 2);
        return $currencyFormat == 0 ? $currencySign . $formatted : $formatted . $currencySign;
    };
@endphp

<!-- ============================================================================
     PRICE SUMMARY BOX
     ============================================================================ -->
<div class="summary-inner-box" id="price-summary-box">
    <h6 class="summary-title">@lang('Price Details')</h6>
    <div class="details-wrapper">

        {{-- ================================================================
            ROW 1: Products Total (Original Price - Never changes)
        ================================================================= --}}
        <div class="price-details">
            <span>@lang('Total MRP')</span>
            <span class="right-side" id="products-total-display">
                {{ $formatPrice($productsTotal) }}
            </span>
        </div>

        {{-- ================================================================
            ROW 2: Coupon Discount (if applied)
        ================================================================= --}}
        <div class="price-details coupon-row {{ $hasCoupon ? '' : 'd-none' }}" id="coupon-row">
            <span class="d-flex align-items-center gap-2">
                <i class="fas fa-tag text-success"></i>
                @lang('Discount')
                <span class="coupon-percentage text-success" id="coupon-percentage-display">
                    {{ $couponPercentage ? '(' . $couponPercentage . ')' : '' }}
                </span>
                <span class="badge bg-success coupon-code-badge" id="coupon-code-display">
                    {{ $couponCode }}
                </span>
            </span>
            <span class="right-side d-flex align-items-center gap-2">
                <span class="text-success" id="coupon-amount-display">
                    -{{ $formatPrice($couponAmount) }}
                </span>
                @if($currentStep == 3)
                <button type="button" class="btn btn-link btn-sm text-danger p-0 remove-coupon-btn"
                        title="@lang('Remove Coupon')">
                    <i class="fas fa-times-circle"></i>
                </button>
                @endif
            </span>
        </div>

        {{-- ================================================================
            ROW 3: Tax
        ================================================================= --}}
        @if($currentStep == 1)
            {{-- Step 1: Dynamic Tax (shown via JavaScript after location selection) --}}
            <div class="price-details tax-row d-none" id="tax-row">
                <span>
                    @lang('Tax')
                    <span class="tax-rate-text" id="tax-rate-display"></span>
                </span>
                <span class="right-side" id="tax-amount-display">{{ $formatPrice(0) }}</span>
            </div>
            <div class="price-details d-none" id="tax-location-row">
                <small class="text-muted" id="tax-location-display"></small>
            </div>
        @elseif($currentStep == 2)
            {{-- Step 2: Tax from step1 session (dynamic display) --}}
            <div class="price-details tax_show {{ $taxRate > 0 ? '' : 'd-none' }}" id="tax-row">
                <span>
                    @lang('Tax')
                    <span class="original_tax">({{ $taxRate }}%)</span>
                </span>
                <span class="right-side tax_amount_view">{{ $formatPrice($taxAmount) }}</span>
            </div>
        @else
            {{-- Step 3: Tax from session (read-only) --}}
            @if($taxRate > 0)
            <div class="price-details">
                <span>@lang('Tax') ({{ $taxRate }}%)</span>
                <span class="right-side">{{ $formatPrice($taxAmount) }}</span>
            </div>
            @if($taxLocation)
            <div class="price-details">
                <small class="text-muted">{{ $taxLocation }}</small>
            </div>
            @endif
            @endif
        @endif

        {{-- ================================================================
            ROW 4 & 5: Shipping & Packing (Step 2 & 3 only, physical products)
        ================================================================= --}}
        @if(!$isDigital && $currentStep >= 2)
            @if($currentStep == 2)
                {{-- Step 2: Dynamic Shipping (updated via JavaScript) --}}
                <div class="price-details">
                    <span>@lang('Shipping Cost')</span>
                    <span class="right-side shipping_cost_view" id="shipping-cost-display">{{ $formatPrice(0) }}</span>
                </div>
                <div class="price-details free-shipping-row free-shipping-discount-row d-none" id="free-shipping-row">
                    <span class="text-success">
                        <i class="fas fa-gift"></i> @lang('Free Shipping Discount')
                    </span>
                    <span class="right-side text-success free_shipping_discount_view" id="free-shipping-discount-display">-{{ $formatPrice(0) }}</span>
                </div>
                <div class="price-details">
                    <span>@lang('Packaging Cost')</span>
                    <span class="right-side packing_cost_view" id="packing-cost-display">{{ $formatPrice(0) }}</span>
                </div>
            @else
                {{-- Step 3: Shipping from session --}}
                <div class="price-details">
                    <span>@lang('Shipping Cost')</span>
                    <span class="right-side">
                        @if($isFreeShipping && $originalShippingCost > 0)
                            <span class="text-decoration-line-through text-muted me-2">
                                {{ $formatPrice($originalShippingCost) }}
                            </span>
                            <span class="badge bg-success">
                                <i class="fas fa-gift"></i> @lang('Free!')
                            </span>
                        @else
                            {{ $formatPrice($shippingCost) }}
                        @endif
                    </span>
                </div>

                @if($isFreeShipping && $freeShippingDiscount > 0)
                <div class="price-details">
                    <span class="text-success">
                        <i class="fas fa-gift"></i> @lang('Free Shipping Discount')
                    </span>
                    <span class="right-side text-success">
                        -{{ $formatPrice($freeShippingDiscount) }}
                    </span>
                </div>
                @endif

                @if($shippingCompany)
                <div class="price-details">
                    <small class="text-muted">{{ $shippingCompany }}</small>
                </div>
                @endif

                {{-- Packing --}}
                <div class="price-details">
                    <span>@lang('Packaging Cost')</span>
                    <span class="right-side">{{ $formatPrice($packingCost) }}</span>
                </div>

                @if($packingCompany)
                <div class="price-details">
                    <small class="text-muted">{{ $packingCompany }}</small>
                </div>
                @endif
            @endif
        @endif

    </div>

    {{-- ================================================================
        FINAL PRICE
    ================================================================= --}}
    <hr>
    <div class="final-price d-flex justify-content-between align-items-center">
        <span style="font-size: 16px; font-weight: 700;">
            @if($currentStep == 1)
                @lang('Total Amount')
            @else
                @lang('Final Price')
            @endif
        </span>
        <span class="total-amount" id="final-cost"
              style="font-size: 18px; font-weight: 700; color: var(--theme-primary);">
            {{ $formatPrice($finalTotal) }}
        </span>
    </div>

    {{-- Helper text for Step 1 --}}
    @if($currentStep == 1)
    <div class="text-muted text-center mt-2" style="font-size: 12px;">
        <small>* @lang('Tax and shipping costs will be calculated in next steps')</small>
    </div>
    @endif
</div>

{{-- ================================================================
    HIDDEN FIELDS (for JavaScript and form submission)
================================================================= --}}
<input type="hidden" id="original-products-total" value="{{ $productsTotal }}">
<input type="hidden" id="subtotal-before-coupon" value="{{ $subtotalBeforeCoupon }}">
<input type="hidden" id="current-coupon-amount" value="{{ $couponAmount }}">
<input type="hidden" id="current-coupon-code" value="{{ $couponCode }}">
<input type="hidden" id="current-coupon-id" value="{{ $couponId }}">
<input type="hidden" id="has-coupon" value="{{ $hasCoupon ? '1' : '0' }}">
<input type="hidden" id="currency-sign" value="{{ $currencySign }}">
<input type="hidden" id="currency-format" value="{{ $currencyFormat }}">

{{-- Vendor checkout data --}}
@php
    $vendorId = $vendor_id ?? Session::get('checkout_vendor_id');
    $isVendorCheckout = $is_vendor_checkout ?? !empty($vendorId);
@endphp
<input type="hidden" id="checkout-vendor-id" value="{{ $vendorId ?? '' }}">
<input type="hidden" id="is-vendor-checkout" value="{{ $isVendorCheckout ? '1' : '0' }}">

<style>
.coupon-row {
    background: rgba(25, 135, 84, 0.1);
    padding: 8px 10px;
    border-radius: 6px;
    margin: 5px 0;
}
.coupon-code-badge {
    font-size: 11px;
    padding: 2px 6px;
}
.remove-coupon-btn {
    opacity: 0.7;
    transition: opacity 0.2s;
    font-size: 14px;
    line-height: 1;
}
.remove-coupon-btn:hover {
    opacity: 1;
}
.price-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 0;
}
</style>
