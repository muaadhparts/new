{{--
    ✅ UNIFIED & SIMPLIFIED Price Summary Component
    Supports both regular checkout and vendor-specific checkout with coupons

    Usage:
    - Step 1: Shows Products + Tax (dynamic)
    - Step 2: Shows Products + Tax + Shipping + Packing (dynamic)
    - Step 3: Shows Final Total (read-only from session)

    Required Variables:
    - $step: Current step (1, 2, or 3)
    - $productsTotal: Products total (Step 1 only)
    - $step1: Step 1 session data (Step 2 & 3)
    - $step2: Step 2 session data (Step 3 only)
    - $digital: Whether cart is digital only
    - $curr: Currency object
    - $gs: General settings

    Optional (for vendor checkout):
    - $is_vendor_checkout: Boolean
    - $vendor_id: Vendor ID for vendor-specific checkout
--}}

@php
    $currentStep = $step ?? 1;
    $isDigital = $digital ?? false;

    // Vendor checkout detection
    $isVendorCheckout = $is_vendor_checkout ?? false;
    $vendorId = $vendor_id ?? Session::get('checkout_vendor_id');

    // Get coupon data based on checkout type
    if ($isVendorCheckout && $vendorId) {
        $hasCoupon = Session::has('coupon_vendor_' . $vendorId);
        $couponAmount = Session::get('coupon_vendor_' . $vendorId, 0);
        $couponCode = Session::get('coupon_code_vendor_' . $vendorId, '');
        $couponPercentage = Session::get('coupon_percentage_vendor_' . $vendorId, 0);
        $couponId = Session::get('coupon_id_vendor_' . $vendorId, null);
    } else {
        $hasCoupon = Session::has('coupon');
        $couponAmount = Session::get('coupon', 0);
        $couponCode = Session::get('coupon_code', '');
        $couponPercentage = Session::get('coupon_percentage', 0);
        $couponId = Session::get('coupon_id', null);
    }
@endphp

<!-- Price Details -->
<div class="summary-inner-box">
    <h6 class="summary-title">@lang('Price Details')</h6>
    <div class="details-wrapper">

        {{-- ========================================
            PRODUCTS TOTAL (All Steps)
        ======================================== --}}
        <div class="price-details">
            <span>@lang('Total MRP')</span>
            @if($currentStep == 1)
                <span class="right-side" id="products-total-display">{{ App\Models\Product::convertPrice($productsTotal) }}</span>
            @else
                <span class="right-side">{{ App\Models\Product::convertPrice($step1->products_total ?? 0) }}</span>
            @endif
        </div>

        {{-- ========================================
            COUPON/DISCOUNT (All Steps) - Enhanced
        ======================================== --}}
        {{-- Applied Coupon Display --}}
        <div class="price-details coupon-applied-row {{ $hasCoupon ? '' : 'd-none' }}" id="coupon-applied-section">
            <span class="d-flex align-items-center gap-2">
                <i class="fas fa-tag text-success"></i>
                @lang('Discount')
                @if($couponPercentage && $couponPercentage != '0' && $couponPercentage != 0)
                    <span class="dpercent text-success">({{ $couponPercentage }})</span>
                @endif
                @if($couponCode)
                    <span class="badge bg-success coupon-code-badge" id="applied-coupon-code">{{ $couponCode }}</span>
                @endif
            </span>
            <span class="right-side d-flex align-items-center gap-2">
                <span class="text-success" id="discount-amount-display">
                    @if ($gs->currency_format == 0)
                        -{{ $curr->sign }}{{ number_format($couponAmount, 2) }}
                    @else
                        -{{ number_format($couponAmount, 2) }}{{ $curr->sign }}
                    @endif
                </span>
                {{-- Remove Coupon Button --}}
                <button type="button" class="btn btn-link btn-sm text-danger p-0 remove-coupon-btn"
                        id="remove-coupon-btn"
                        title="@lang('Remove Coupon')"
                        style="font-size: 14px; line-height: 1;">
                    <i class="fas fa-times-circle"></i>
                </button>
            </span>
        </div>

        {{-- Discount row for dynamic updates (hidden by default, shown after applying coupon via AJAX) --}}
        <div class="price-details discount-bar d-none" id="discount-bar-dynamic">
            <span>
                @lang('Discount')
                <span class="dpercent"></span>
            </span>
            <span class="right-side text-success" id="discount"></span>
        </div>

        {{-- ========================================
            TAX
        ======================================== --}}

        {{-- Step 1: Tax (Dynamic - Hidden initially, shown after location selection) --}}
        @if($currentStep == 1)
            <div class="price-details tax-display-wrapper d-none" id="tax-display">
                <span>
                    @lang('Tax')
                    <span class="tax-rate-text"></span>
                </span>
                <span class="right-side tax-amount-value">{{ App\Models\Product::convertPrice(0) }}</span>
            </div>

            <div class="price-details tax-location-wrapper d-none" id="tax-location-display">
                <small class="text-muted tax-location-text"></small>
            </div>
        @endif

        {{-- Step 2 & 3: Tax (From Session) --}}
        @if($currentStep >= 2 && isset($step1) && isset($step1->tax_rate) && $step1->tax_rate > 0)
            <div class="price-details">
                <span>@lang('Tax') ({{ $step1->tax_rate }}%)</span>
                <span class="right-side">{{ App\Models\Product::convertPrice($step1->tax_amount) }}</span>
            </div>

            @if(isset($step1->tax_location) && $step1->tax_location)
                <div class="price-details">
                    <small class="text-muted">{{ $step1->tax_location }}</small>
                </div>
            @endif
        @endif

        {{-- ========================================
            SHIPPING & PACKING
        ======================================== --}}

        {{-- Step 2: Shipping & Packing (Dynamic) --}}
        @if($currentStep == 2 && !$isDigital)
            <div class="price-details">
                <span>@lang('Shipping Cost')</span>
                <span class="right-side shipping_cost_view">{{ App\Models\Product::convertPrice(0) }}</span>
            </div>

            {{-- Free Shipping Discount (will be shown by JavaScript if applicable) --}}
            <div class="price-details free-shipping-discount-row d-none">
                <span class="text-success">
                    <i class="fas fa-gift"></i> @lang('Free Shipping Discount')
                </span>
                <span class="right-side text-success free_shipping_discount_view">-{{ $curr->sign }}0.00</span>
            </div>

            <div class="price-details">
                <span>@lang('Packaging Cost')</span>
                <span class="right-side packing_cost_view">{{ App\Models\Product::convertPrice(0) }}</span>
            </div>
        @endif

        {{-- Step 3: Shipping & Packing (From Session) --}}
        @if($currentStep == 3 && !$isDigital && isset($step2))
            @php
                $isFreeShipping = $step2->is_free_shipping ?? false;
                $originalShippingCost = $step2->original_shipping_cost ?? 0;
                $freeShippingDiscount = $step2->free_shipping_discount ?? 0;
                $actualShippingCost = $step2->shipping_cost ?? 0;
            @endphp

            <div class="price-details">
                <span>@lang('Shipping Cost')</span>
                <span class="right-side">
                    @if($isFreeShipping && $originalShippingCost > 0)
                        {{-- Show original price with strikethrough --}}
                        <span class="text-decoration-line-through text-muted me-2">
                            {{ App\Models\Product::convertPrice($originalShippingCost) }}
                        </span>
                        <span class="badge bg-success">
                            <i class="fas fa-gift"></i> @lang('Free!')
                        </span>
                    @else
                        {{ App\Models\Product::convertPrice($actualShippingCost) }}
                    @endif
                </span>
            </div>

            {{-- ✅ Free Shipping Discount Row --}}
            @if($isFreeShipping && $freeShippingDiscount > 0)
                <div class="price-details">
                    <span class="text-success">
                        <i class="fas fa-gift"></i> @lang('Free Shipping Discount')
                    </span>
                    <span class="right-side text-success">
                        -{{ App\Models\Product::convertPrice($freeShippingDiscount) }}
                    </span>
                </div>
            @endif

            @if(isset($step2->shipping_company) && $step2->shipping_company)
                <div class="price-details">
                    <small class="text-muted">{{ $step2->shipping_company }}</small>
                </div>
            @endif

            <div class="price-details">
                <span>@lang('Packaging Cost')</span>
                <span class="right-side">{{ App\Models\Product::convertPrice($step2->packing_cost ?? 0) }}</span>
            </div>

            @if(isset($step2->packing_company) && $step2->packing_company)
                <div class="price-details">
                    <small class="text-muted">{{ $step2->packing_company }}</small>
                </div>
            @endif
        @endif

    </div>

    {{-- ========================================
        FINAL PRICE
    ======================================== --}}
    <hr>
    <div class="final-price">
        <span style="font-size: 16px; font-weight: 700;">
            @if($currentStep == 1)
                @lang('Total Amount')
            @else
                @lang('Final Price')
            @endif
        </span>

        {{-- Step 1 & 2: Dynamic Total (Updated by JavaScript) --}}
        @if($currentStep == 1 || $currentStep == 2)
            @php
                // Initial display value - subtract coupon if applied
                $initialTotal = $productsTotal ?? ($step1->total_with_tax ?? 0);
                if ($hasCoupon && $couponAmount > 0) {
                    $initialTotal = $initialTotal - $couponAmount;
                }
            @endphp

            @if ($gs->currency_format == 0)
                <span class="total-amount" id="final-cost" style="font-size: 18px; font-weight: 700; color: var(--theme-primary);">
                    {{ $curr->sign }}{{ number_format($initialTotal, 2) }}
                </span>
            @else
                <span class="total-amount" id="final-cost" style="font-size: 18px; font-weight: 700; color: var(--theme-primary);">
                    {{ number_format($initialTotal, 2) }}{{ $curr->sign }}
                </span>
            @endif
        @endif

        {{-- Step 3: Final Total (From Session - Read Only) --}}
        @if($currentStep == 3 && isset($step2))
            @php
                // Use final_total if available, fallback to total for backward compatibility
                $finalTotal = $step2->final_total ?? $step2->total ?? 0;
                // Subtract coupon if applied and not already subtracted
                if ($hasCoupon && $couponAmount > 0 && !isset($step2->coupon_applied)) {
                    $finalTotal = $finalTotal - $couponAmount;
                }
            @endphp

            @if ($gs->currency_format == 0)
                <span class="total-amount" id="total-cost" style="font-size: 18px; font-weight: 700; color: var(--theme-primary);">
                    {{ $curr->sign }}{{ number_format($finalTotal, 2) }}
                </span>
            @else
                <span class="total-amount" id="total-cost" style="font-size: 18px; font-weight: 700; color: var(--theme-primary);">
                    {{ number_format($finalTotal, 2) }}{{ $curr->sign }}
                </span>
            @endif
        @endif
    </div>

    {{-- Helper Text for Step 1 --}}
    @if($currentStep == 1)
        <div class="text-muted" style="margin-top: 5px; font-size: 12px; text-align: center;">
            <small>* @lang('Tax and shipping costs will be calculated in next steps')</small>
        </div>
    @endif
</div>

{{-- Hidden fields for coupon data --}}
<input type="hidden" id="coupon-vendor-id" value="{{ $vendorId ?? '' }}">
<input type="hidden" id="is-vendor-checkout" value="{{ $isVendorCheckout ? '1' : '0' }}">
<input type="hidden" id="current-coupon-amount" value="{{ $couponAmount }}">
<input type="hidden" id="current-coupon-code" value="{{ $couponCode }}">

<style>
.coupon-applied-row {
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
}
.remove-coupon-btn:hover {
    opacity: 1;
}
</style>
