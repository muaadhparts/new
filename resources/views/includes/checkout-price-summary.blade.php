{{--
    ============================================================================
    CHECKOUT PRICE SUMMARY COMPONENT - UNIFIED FOR ALL STEPS
    ============================================================================

    A centralized, professional price summary component for all checkout steps.

    Required Variables:
    - $step: Current step (1, 2, or 3)
    - $curr: Currency object
    - $gs: General settings

    Data Sources:
    - Step 1: $productsTotal from controller
    - Step 2: $step1 session + dynamic shipping/packing via JS
    - Step 3: $step2 session (read-only)

    Price Structure:
    ================
    products_total      = Sum of all product prices (NEVER changes)
    coupon_discount     = Discount from coupon
    tax_amount          = Tax on (products - coupon)
    shipping_cost       = Selected shipping
    packing_cost        = Selected packaging
    grand_total         = products - coupon + tax + shipping + packing

    ============================================================================
--}}

@php
    $currentStep = $step ?? 1;
    $isDigital = $digital ?? false;
    $currencySign = $curr->sign ?? 'SAR';
    $currencyFormat = $gs->currency_format ?? 0;
    $vendorId = $vendor_id ?? Session::get('checkout_vendor_id');

    // ========================================================================
    // INITIALIZE ALL PRICE VARIABLES
    // Use existing passed values if available, otherwise default to 0
    // ========================================================================
    $passedProductsTotal = $productsTotal ?? $totalPrice ?? 0;
    $productsTotal = 0;
    $couponDiscount = 0;
    $couponCode = '';
    $couponPercentage = '';
    $hasCoupon = false;
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
    $grandTotal = 0;
    $subtotalBeforeCoupon = 0;

    // ========================================================================
    // STEP 1: Products Total from Controller (Tax calculated via JS)
    // ========================================================================
    if ($currentStep == 1) {
        $productsTotal = $passedProductsTotal;

        // Check for existing coupon in session
        if ($vendorId) {
            $couponDiscount = Session::get('coupon_vendor_' . $vendorId, 0);
            $couponCode = Session::get('coupon_code_vendor_' . $vendorId, '');
            $couponPercentage = Session::get('coupon_percentage_vendor_' . $vendorId, '');
        } else {
            $couponDiscount = Session::get('coupon', 0);
            $couponCode = Session::get('coupon_code', '');
            $couponPercentage = Session::get('coupon_percentage', '');
        }
        $hasCoupon = $couponDiscount > 0;

        // Grand total for step 1 (tax added via JS)
        $grandTotal = $productsTotal - $couponDiscount;
        $subtotalBeforeCoupon = $productsTotal;
    }

    // ========================================================================
    // STEP 2: Products + Tax from Step1, Shipping/Packing via JS
    // ========================================================================
    elseif ($currentStep == 2) {
        // Get products total from step1
        if (isset($step1)) {
            $productsTotal = $step1->products_total ?? $passedProductsTotal;
            $taxRate = $step1->tax_rate ?? 0;
            $taxAmount = $step1->tax_amount ?? 0;
            $taxLocation = $step1->tax_location ?? '';
        } else {
            $productsTotal = $passedProductsTotal;
        }

        // Check for coupon
        if ($vendorId) {
            $couponDiscount = Session::get('coupon_vendor_' . $vendorId, 0);
            $couponCode = Session::get('coupon_code_vendor_' . $vendorId, '');
            $couponPercentage = Session::get('coupon_percentage_vendor_' . $vendorId, '');
        } else {
            $couponDiscount = Session::get('coupon', 0);
            $couponCode = Session::get('coupon_code', '');
            $couponPercentage = Session::get('coupon_percentage', '');
        }
        $hasCoupon = $couponDiscount > 0;

        // Grand total for step 2 (shipping/packing added via JS)
        $subtotal = $productsTotal - $couponDiscount;
        $grandTotal = $subtotal + $taxAmount;
        $subtotalBeforeCoupon = $productsTotal + $taxAmount; // + shipping/packing via JS
    }

    // ========================================================================
    // STEP 3: ALL DATA FROM STEP2 SESSION (Read-Only)
    // ========================================================================
    elseif ($currentStep == 3 && isset($step2)) {
        // Products
        $productsTotal = $step2->products_total ?? ($step1->products_total ?? $passedProductsTotal);

        // Coupon
        $couponDiscount = $step2->coupon_discount ?? 0;
        $couponCode = $step2->coupon_code ?? '';
        $couponPercentage = $step2->coupon_percentage ?? '';
        $hasCoupon = $couponDiscount > 0;

        // Tax
        $taxRate = $step2->tax_rate ?? 0;
        $taxAmount = $step2->tax_amount ?? 0;
        $taxLocation = $step2->tax_location ?? '';

        // Shipping
        $shippingCost = $step2->shipping_cost ?? 0;
        $originalShippingCost = $step2->original_shipping_cost ?? $shippingCost;
        $isFreeShipping = $step2->is_free_shipping ?? false;
        $freeShippingDiscount = $step2->free_shipping_discount ?? 0;
        $shippingCompany = $step2->shipping_company ?? '';

        // Packing
        $packingCost = $step2->packing_cost ?? 0;
        $packingCompany = $step2->packing_company ?? '';

        // Totals
        $grandTotal = $step2->grand_total ?? $step2->total ?? $step2->final_total ?? 0;
        $subtotalBeforeCoupon = $step2->subtotal_before_coupon ?? ($productsTotal + $taxAmount + $shippingCost + $packingCost);
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
            ROW 1: Products Total (Original - NEVER changes)
        ================================================================= --}}
        <div class="price-details">
            <span>@lang('Total MRP')</span>
            <span class="right-side products-total-display" id="products-total-display">
                {{ $formatPrice($productsTotal) }}
            </span>
        </div>

        {{-- ================================================================
            ROW 2: Coupon Discount (if applied)
        ================================================================= --}}
        <div class="price-details coupon-row {{ $hasCoupon ? '' : 'd-none' }}" id="coupon-row">
            <span class="d-flex align-items-center gap-2">
                <i class="fas fa-tag text-success"></i>
                @lang('Coupon Discount')
                <span class="coupon-percentage-display text-success" id="coupon-percentage-display">
                    {{ $couponPercentage ? '(' . $couponPercentage . ')' : '' }}
                </span>
                <span class="badge bg-success coupon-code-badge coupon-code-display" id="coupon-code-display">
                    {{ $couponCode }}
                </span>
            </span>
            <span class="right-side d-flex align-items-center gap-2">
                <span class="text-success coupon-amount-display" id="coupon-amount-display">
                    -{{ $formatPrice($couponDiscount) }}
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
            {{-- Step 1: Tax calculated via JavaScript --}}
            <div class="price-details tax-row d-none" id="tax-row">
                <span>
                    @lang('Tax')
                    <span class="tax-rate-display" id="tax-rate-display"></span>
                </span>
                <span class="right-side tax-amount-display" id="tax-amount-display">{{ $formatPrice(0) }}</span>
            </div>
        @elseif($currentStep == 2)
            {{-- Step 2: Tax from step1 session --}}
            <div class="price-details tax-row {{ $taxRate > 0 ? '' : 'd-none' }}" id="tax-row">
                <span>
                    @lang('Tax')
                    <span class="tax-rate-display" id="tax-rate-display">({{ $taxRate }}%)</span>
                </span>
                <span class="right-side tax-amount-display" id="tax-amount-display">{{ $formatPrice($taxAmount) }}</span>
            </div>
        @else
            {{-- Step 3: Tax from step2 session --}}
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
            ROW 4: Shipping (Step 2 & 3 only, physical products)
        ================================================================= --}}
        {{-- DEBUG: isDigital={{ $isDigital ? 'true' : 'false' }}, currentStep={{ $currentStep }} --}}
        @if($currentStep >= 2)
            @if($currentStep == 2)
                {{-- Step 2: Dynamic Shipping via JavaScript --}}
                <div class="price-details" id="shipping-row">
                    <span>@lang('Shipping Cost')</span>
                    <span class="right-side shipping-cost-display" id="shipping-cost-display">{{ $formatPrice(0) }}</span>
                </div>
                <div class="price-details free-shipping-row d-none" id="free-shipping-row">
                    <span class="text-success">
                        <i class="fas fa-gift"></i> @lang('Free Shipping')
                    </span>
                    <span class="right-side text-success free-shipping-discount-display" id="free-shipping-discount-display">-{{ $formatPrice(0) }}</span>
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
                    <span class="right-side text-success">-{{ $formatPrice($freeShippingDiscount) }}</span>
                </div>
                @endif
                @if($shippingCompany)
                <div class="price-details">
                    <small class="text-muted">{{ $shippingCompany }}</small>
                </div>
                @endif
            @endif
        @endif

        {{-- ================================================================
            ROW 5: Packing (Step 2 & 3 only, physical products)
        ================================================================= --}}
        @if($currentStep >= 2)
            @if($currentStep == 2)
                {{-- Step 2: Dynamic Packing via JavaScript --}}
                <div class="price-details" id="packing-row">
                    <span>@lang('Packaging Cost')</span>
                    <span class="right-side packing-cost-display" id="packing-cost-display">{{ $formatPrice(0) }}</span>
                </div>
            @else
                {{-- Step 3: Packing from session --}}
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
        <span class="grand-total-display" id="grand-total-display"
              style="font-size: 18px; font-weight: 700; color: var(--theme-primary);">
            {{ $formatPrice($grandTotal) }}
        </span>
    </div>

    {{-- Note for Step 1 --}}
    @if($currentStep == 1)
    <div class="text-muted text-center mt-2" style="font-size: 12px;">
        <small>* @lang('Tax and shipping costs will be calculated in next steps')</small>
    </div>
    @endif
</div>

{{-- ================================================================
    HIDDEN FIELDS FOR JAVASCRIPT
================================================================= --}}
<input type="hidden" id="price-products-total" value="{{ $productsTotal }}">
<input type="hidden" id="price-coupon-discount" value="{{ $couponDiscount }}">
<input type="hidden" id="price-coupon-code" value="{{ $couponCode }}">
<input type="hidden" id="price-tax-rate" value="{{ $taxRate }}">
<input type="hidden" id="price-tax-amount" value="{{ $taxAmount }}">
<input type="hidden" id="price-shipping-cost" value="{{ $shippingCost }}">
<input type="hidden" id="price-packing-cost" value="{{ $packingCost }}">
<input type="hidden" id="price-grand-total" value="{{ $grandTotal }}">
<input type="hidden" id="price-subtotal-before-coupon" value="{{ $subtotalBeforeCoupon }}">
<input type="hidden" id="price-currency-sign" value="{{ $currencySign }}">
<input type="hidden" id="price-currency-format" value="{{ $currencyFormat }}">
<input type="hidden" id="price-vendor-id" value="{{ $vendorId ?? '' }}">
<input type="hidden" id="price-current-step" value="{{ $currentStep }}">

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

{{-- ================================================================
    UNIFIED JAVASCRIPT FOR PRICE UPDATES
================================================================= --}}
<script>
/**
 * ============================================================================
 * UNIFIED PRICE SUMMARY MANAGER
 * ============================================================================
 *
 * Single source of truth for all price display updates.
 * Used by all checkout steps for consistent behavior.
 *
 * Usage:
 * - PriceSummary.updateTax(rate, amount)
 * - PriceSummary.updateShipping(cost, originalCost, isFree)
 * - PriceSummary.updatePacking(cost)
 * - PriceSummary.updateCoupon(discount, code, percentage)
 * - PriceSummary.removeCoupon()
 * - PriceSummary.recalculateTotal()
 *
 * ============================================================================
 */
// Initialize when DOM is ready
(function initPriceSummary() {
    // Wait for jQuery
    if (typeof jQuery === 'undefined') {
        setTimeout(initPriceSummary, 50);
        return;
    }

    // Wait for DOM ready
    $(function() {
        // Get currency settings from hidden fields
        var currencySign = $('#price-currency-sign').val() || 'SAR';
        var currencyFormat = parseInt($('#price-currency-format').val()) || 0;
        var currentStep = parseInt($('#price-current-step').val()) || 1;

        console.log('🎯 PriceSummary initialized:', {
            currencySign: currencySign,
            currencyFormat: currencyFormat,
            currentStep: currentStep,
            productsTotal: $('#price-products-total').val()
        });

        // Format price with currency
        function formatPrice(amount) {
            var formatted = parseFloat(amount || 0).toFixed(2);
            return currencyFormat == 0 ? currencySign + formatted : formatted + currencySign;
        }

    // Get current values from hidden fields
    function getValues() {
        return {
            productsTotal: parseFloat($('#price-products-total').val()) || 0,
            couponDiscount: parseFloat($('#price-coupon-discount').val()) || 0,
            taxRate: parseFloat($('#price-tax-rate').val()) || 0,
            taxAmount: parseFloat($('#price-tax-amount').val()) || 0,
            shippingCost: parseFloat($('#price-shipping-cost').val()) || 0,
            packingCost: parseFloat($('#price-packing-cost').val()) || 0,
            subtotalBeforeCoupon: parseFloat($('#price-subtotal-before-coupon').val()) || 0
        };
    }

    // Calculate and update grand total
    function recalculateTotal() {
        var v = getValues();
        var subtotal = v.productsTotal - v.couponDiscount;
        var grandTotal = subtotal + v.taxAmount + v.shippingCost + v.packingCost;

        // Update hidden field
        $('#price-grand-total').val(grandTotal.toFixed(2));

        // Update display
        $('#grand-total-display, .grand-total-display').html(formatPrice(grandTotal));

        // Also update legacy selectors for backward compatibility
        $('#final-cost, #final-total-display, .total-amount').html(formatPrice(grandTotal));
        $('#grandtotal').val(grandTotal.toFixed(2));

        console.log('PriceSummary: Total updated', {
            products: v.productsTotal,
            coupon: v.couponDiscount,
            tax: v.taxAmount,
            shipping: v.shippingCost,
            packing: v.packingCost,
            total: grandTotal
        });

        return grandTotal;
    }

    var PriceSummary = {
        // Format price helper
        formatPrice: formatPrice,

        // Get current values
        getValues: getValues,

        // Update tax display
        updateTax: function(rate, amount) {
            console.log('💰 PriceSummary.updateTax called:', { rate: rate, amount: amount });

            $('#price-tax-rate').val(rate);
            $('#price-tax-amount').val(amount);

            if (rate > 0) {
                $('#tax-row, .tax-row').removeClass('d-none');
                $('#tax-rate-display, .tax-rate-display').html('(' + rate + '%)');
                $('#tax-amount-display, .tax-amount-display').html(formatPrice(amount));
            } else {
                $('#tax-row, .tax-row').addClass('d-none');
            }

            recalculateTotal();
        },

        // Update shipping display
        updateShipping: function(cost, originalCost, isFree) {
            console.log('📦 PriceSummary.updateShipping called:', { cost: cost, originalCost: originalCost, isFree: isFree });

            originalCost = originalCost || cost;
            isFree = isFree || false;

            $('#price-shipping-cost').val(cost);

            if (isFree && originalCost > 0) {
                // Show original price crossed out + Free badge
                var html = '<span class="text-decoration-line-through text-muted">' + formatPrice(originalCost) + '</span> ' +
                           '<span class="badge bg-success"><i class="fas fa-gift"></i> @lang("Free!")</span>';
                $('#shipping-cost-display, .shipping-cost-display').html(html);

                // Show free shipping discount row
                $('#free-shipping-row, .free-shipping-row').removeClass('d-none');
                $('#free-shipping-discount-display, .free-shipping-discount-display').html('-' + formatPrice(originalCost));
            } else {
                $('#shipping-cost-display, .shipping-cost-display').html(formatPrice(cost));
                $('#free-shipping-row, .free-shipping-row').addClass('d-none');
            }

            recalculateTotal();
        },

        // Update packing display
        updatePacking: function(cost) {
            console.log('🎁 PriceSummary.updatePacking called:', { cost: cost });

            $('#price-packing-cost').val(cost);
            $('#packing-cost-display, .packing-cost-display').html(formatPrice(cost));
            recalculateTotal();
        },

        // Update coupon display
        updateCoupon: function(discount, code, percentage) {
            $('#price-coupon-discount').val(discount);
            $('#price-coupon-code').val(code);

            if (discount > 0) {
                $('#coupon-row').removeClass('d-none');
                $('#coupon-code-display, .coupon-code-display').text(code);
                $('#coupon-amount-display, .coupon-amount-display').html('-' + formatPrice(discount));
                $('#coupon-percentage-display, .coupon-percentage-display').html(percentage ? '(' + percentage + ')' : '');
            } else {
                $('#coupon-row').addClass('d-none');
            }

            recalculateTotal();
        },

        // Remove coupon
        removeCoupon: function() {
            $('#price-coupon-discount').val(0);
            $('#price-coupon-code').val('');
            $('#coupon-row').addClass('d-none');
            recalculateTotal();
        },

        // Manual recalculation
        recalculateTotal: recalculateTotal,

        // Get subtotal before coupon (for coupon calculations)
        getSubtotalBeforeCoupon: function() {
            var v = getValues();
            return v.productsTotal + v.taxAmount + v.shippingCost + v.packingCost;
        }
    };

    // Make it globally available
    window.PriceSummary = PriceSummary;

    console.log('✅ PriceSummary attached to window');
    });
})();
</script>
