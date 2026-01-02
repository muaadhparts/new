{{--
    ============================================================================
    CHECKOUT PRICE SUMMARY COMPONENT - UNIFIED FOR ALL STEPS
    ============================================================================

    A centralized, professional price summary component for all checkout steps.

    Required Variables:
    - $step: Current step (1, 2, or 3)
    - $curr: Currency object
    - $gs: General settings

    Currency Conversion:
    ====================
    All values are converted from SAR to selected currency via CheckoutPriceService.
    This blade file ONLY formats (number_format + sign), NEVER converts.

    ============================================================================
--}}

@php
    use App\Services\CheckoutPriceService;

    $currentStep = $step ?? 1;
    $isDigital = $digital ?? false;
    $merchantId = $vendor_id ?? Session::get('checkout_vendor_id');

    // ========================================================================
    // USE CHECKOUT PRICE SERVICE FOR CURRENCY CONVERSION
    // Single source of truth for all price calculations and conversions
    // ========================================================================
    $priceService = app(CheckoutPriceService::class);

    // Get converted price breakdown (all values in selected currency)
    $prices = $priceService->getConvertedPriceBreakdown(
        $currentStep,
        $step1 ?? null,
        $step2 ?? null,
        $merchantId
    );

    // Extract values from service response
    $productsTotal = $prices['products_total'];
    $discountAmount = $prices['discount_amount'];
    $discountCode = $prices['discount_code'];
    $discountPercentage = $prices['discount_percentage'];
    $hasDiscount = $prices['has_discount'];
    $taxRate = $prices['tax_rate'];
    $taxAmount = $prices['tax_amount'];
    $taxLocation = $prices['tax_location'];
    $shippingCost = $prices['shipping_cost'];
    $originalShippingCost = $prices['original_shipping_cost'];
    $isFreeShipping = $prices['is_free_shipping'];
    $freeShippingDiscount = $prices['free_shipping_discount'];
    $shippingCompany = $prices['shipping_company'];
    $packingCost = $prices['packing_cost'];
    $packingCompany = $prices['packing_company'];
    $grandTotal = $prices['grand_total'];
    $subtotalBeforeDiscount = $prices['subtotal_before_discount'];

    // Currency info from service
    $currencySign = $prices['currency_sign'];
    $currencyFormat = $prices['currency_format'];
    $currencyValue = $prices['currency_value'];

    // Helper function for price formatting ONLY (values are already converted)
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
            ROW 2: Discount Code (if applied)
        ================================================================= --}}
        <div class="price-details discount-row {{ $hasDiscount ? '' : 'd-none' }}" id="discount-row">
            <span class="d-flex align-items-center gap-2">
                <i class="fas fa-tag text-success"></i>
                @lang('Discount')
                <span class="discount-percentage-display text-success" id="discount-percentage-display">
                    {{ $discountPercentage ? '(' . $discountPercentage . ')' : '' }}
                </span>
                <span class="badge bg-success discount-code-badge discount-code-display" id="discount-code-display">
                    {{ $discountCode }}
                </span>
            </span>
            <span class="right-side d-flex align-items-center gap-2">
                <span class="text-success discount-amount-display" id="discount-amount-display">
                    -{{ $formatPrice($discountAmount) }}
                </span>
                @if($currentStep == 3)
                <button type="button" class="btn btn-link btn-sm text-danger p-0 remove-discount-btn"
                        title="@lang('Remove Discount')">
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
<input type="hidden" id="price-discount-amount" value="{{ $discountAmount }}">
<input type="hidden" id="price-discount-code" value="{{ $discountCode }}">
<input type="hidden" id="price-tax-rate" value="{{ $taxRate }}">
<input type="hidden" id="price-tax-amount" value="{{ $taxAmount }}">
<input type="hidden" id="price-shipping-cost" value="{{ $shippingCost }}">
<input type="hidden" id="price-packing-cost" value="{{ $packingCost }}">
<input type="hidden" id="price-grand-total" value="{{ $grandTotal }}">
<input type="hidden" id="price-subtotal-before-discount" value="{{ $subtotalBeforeDiscount }}">
<input type="hidden" id="price-currency-sign" value="{{ $currencySign }}">
<input type="hidden" id="price-currency-format" value="{{ $currencyFormat }}">
<input type="hidden" id="price-vendor-id" value="{{ $merchantId ?? '' }}">
<input type="hidden" id="price-current-step" value="{{ $currentStep }}">

<style>
.discount-row {
    background: rgba(25, 135, 84, 0.1);
    padding: 8px 10px;
    border-radius: 6px;
    margin: 5px 0;
}
.discount-code-badge {
    font-size: 11px;
    padding: 2px 6px;
}
.remove-discount-btn {
    opacity: 0.7;
    transition: opacity 0.2s;
    font-size: 14px;
    line-height: 1;
}
.remove-discount-btn:hover {
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
 * - PriceSummary.updateDiscount(discount, code, percentage)
 * - PriceSummary.removeDiscount()
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

        // Format price ONLY (values must arrive pre-converted from API)
        function formatPrice(amount) {
            var formatted = parseFloat(amount || 0).toFixed(2);
            return currencyFormat == 0 ? currencySign + formatted : formatted + currencySign;
        }

    // Get current values from hidden fields
    function getValues() {
        return {
            productsTotal: parseFloat($('#price-products-total').val()) || 0,
            discountAmount: parseFloat($('#price-discount-amount').val()) || 0,
            taxRate: parseFloat($('#price-tax-rate').val()) || 0,
            taxAmount: parseFloat($('#price-tax-amount').val()) || 0,
            shippingCost: parseFloat($('#price-shipping-cost').val()) || 0,
            packingCost: parseFloat($('#price-packing-cost').val()) || 0,
            subtotalBeforeDiscount: parseFloat($('#price-subtotal-before-discount').val()) || 0
        };
    }

    // Calculate and update grand total
    function recalculateTotal() {
        var v = getValues();
        var subtotal = v.productsTotal - v.discountAmount;
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
            discount: v.discountAmount,
            tax: v.taxAmount,
            shipping: v.shippingCost,
            packing: v.packingCost,
            total: grandTotal
        });

        return grandTotal;
    }

    var PriceSummary = {
        // Format helper only (no conversion - values must be pre-converted)
        formatPrice: formatPrice,

        // Get current values
        getValues: getValues,

        // Update tax display (amount must be pre-converted by API)
        updateTax: function(rate, amount) {
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

        // Update shipping display (costs must be pre-converted by API)
        updateShipping: function(cost, originalCost, isFree) {
            originalCost = originalCost || cost;
            isFree = isFree || false;

            $('#price-shipping-cost').val(cost);

            if (isFree && originalCost > 0) {
                var html = '<span class="text-decoration-line-through text-muted">' + formatPrice(originalCost) + '</span> ' +
                           '<span class="badge bg-success"><i class="fas fa-gift"></i> @lang("Free!")</span>';
                $('#shipping-cost-display, .shipping-cost-display').html(html);
                $('#free-shipping-row, .free-shipping-row').removeClass('d-none');
                $('#free-shipping-discount-display, .free-shipping-discount-display').html('-' + formatPrice(originalCost));
            } else {
                $('#shipping-cost-display, .shipping-cost-display').html(formatPrice(cost));
                $('#free-shipping-row, .free-shipping-row').addClass('d-none');
            }

            recalculateTotal();
        },

        // Update packing display (cost must be pre-converted by API)
        updatePacking: function(cost) {
            $('#price-packing-cost').val(cost);
            $('#packing-cost-display, .packing-cost-display').html(formatPrice(cost));
            recalculateTotal();
        },

        // Update discount display (discount must be pre-converted by API)
        updateDiscount: function(discount, code, percentage) {
            $('#price-discount-amount').val(discount);
            $('#price-discount-code').val(code);

            if (discount > 0) {
                $('#discount-row').removeClass('d-none');
                $('#discount-code-display, .discount-code-display').text(code);
                $('#discount-amount-display, .discount-amount-display').html('-' + formatPrice(discount));
                $('#discount-percentage-display, .discount-percentage-display').html(percentage ? '(' + percentage + ')' : '');
            } else {
                $('#discount-row').addClass('d-none');
            }

            recalculateTotal();
        },

        // Remove discount
        removeDiscount: function() {
            $('#price-discount-amount').val(0);
            $('#price-discount-code').val('');
            $('#discount-row').addClass('d-none');
            recalculateTotal();
        },

        // Manual recalculation
        recalculateTotal: recalculateTotal,

        // Get subtotal before discount (for discount calculations)
        getSubtotalBeforeDiscount: function() {
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
