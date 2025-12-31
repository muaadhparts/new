{{--
    =====================================================================
    CART PAGE V2 - Modern Design
    =====================================================================
    Uses $productsByVendor passed from CartController.
    - Responsive card-based layout
    - RTL support
    =====================================================================
--}}

@php
    use Illuminate\Support\Facades\Storage;
    use App\Models\CatalogItem;

    // Use variables passed from CartController::cart()
    // $productsByVendor, $products, $totalPrice are already available

    $currValue = $curr->value ?? 1;
    $currSign = $curr->sign ?? '$';
    $currPos = $gs->currency_format ?? 0;

    $showPrice = function($price) use ($currValue, $currSign, $currPos) {
        $converted = round($price * $currValue, 2);
        return $currPos == 0 ? $currSign . $converted : $converted . $currSign;
    };
@endphp

<div class="m-cart">
    <div class="container">
        @if (empty($products) || empty($productsByVendor))
            {{-- Empty Cart --}}
            <div class="m-cart__empty">
                <div class="m-cart__empty-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3>@lang('Your cart is empty')</h3>
                <p>@lang('Looks like you haven\'t added any items yet')</p>
                <a href="{{ route('front.index') }}" class="m-btn m-btn--primary">
                    <i class="fas fa-arrow-left"></i>
                    @lang('Continue Shopping')
                </a>
            </div>
        @else
            @foreach ($productsByVendor as $vendorId => $vendorGroup)
            <div class="m-cart__vendor" data-vendor-id="{{ $vendorId }}">
                {{-- Vendor Header --}}
                <div class="m-cart__vendor-header">
                    <div class="m-cart__vendor-info">
                        <i class="fas fa-store"></i>
                        <span class="m-cart__vendor-name">{{ $vendorGroup['vendor_name'] ?? __('Vendor') }}</span>
                        <span class="m-cart__vendor-count">{{ $vendorGroup['count'] ?? 0 }} @lang('Items')</span>
                    </div>
                </div>

                <div class="m-cart__body">
                    {{-- Products List --}}
                    <div class="m-cart__items">
                        @foreach ($vendorGroup['products'] as $rowKey => $product)
                            @php
                                $domKey = str_replace([':', '#', '.', ' ', '/', '\\'], '_', (string)$rowKey);

                                $itemName = data_get($product, 'item.name', '');
                                $itemNameAr = data_get($product, 'item.name_ar', '');
                                $displayName = app()->getLocale() == 'ar' && !empty($itemNameAr) ? $itemNameAr : $itemName;

                                $itemSlug = data_get($product, 'item.slug');
                                $itemSku = data_get($product, 'item.sku');
                                $itemPhoto = data_get($product, 'item.photo');
                                $photoUrl = $itemPhoto ? Storage::url($itemPhoto) : asset('assets/images/noimage.png');

                                $itemVendorId = $product['user_id'] ?? data_get($product, 'item.user_id') ?? 0;
                                $itemMpId = $product['merchant_item_id'] ?? data_get($product, 'item.merchant_item_id') ?? 0;
                                $hasAllParams = $itemSlug && $itemVendorId && $itemMpId;

                                $productUrl = $hasAllParams
                                    ? route('front.catalog-item', ['slug' => $itemSlug, 'vendor_id' => $itemVendorId, 'merchant_item_id' => $itemMpId])
                                    : '#';

                                $itemPrice = $product['item_price'] ?? 0;
                                $totalItemPrice = $product['price'] ?? 0;
                                $qty = (int)($product['qty'] ?? 1);
                                $discount = $product['discount'] ?? 0;

                                // Brand with logo
                                $brand = data_get($product, 'item.brand');
                                $brandName = $brand ? getLocalizedBrandName($brand) : null;
                                $brandLogo = $brand?->photo_url ?? null;

                                // Quality Brand with logo + Real-time stock from database
                                $qualityBrand = null;
                                $qualityName = null;
                                $qualityLogo = null;
                                $stock = 0;
                                $minQty = 1;
                                $preordered = 0;

                                if ($itemMpId) {
                                    $mp = \App\Models\MerchantItem::with('qualityBrand')->find($itemMpId);
                                    if ($mp) {
                                        // Real-time stock (المخزون المتبقي الفعلي)
                                        $sizeVal = $product['size'] ?? '';
                                        $sizeStr = is_array($sizeVal) ? ($sizeVal[0] ?? '') : (string)$sizeVal;
                                        if ($sizeStr && !empty($mp->size) && !empty($mp->size_qty)) {
                                            $sizes = array_map('trim', explode(',', $mp->size));
                                            $qtys = array_map('trim', explode(',', $mp->size_qty));
                                            $idx = array_search(trim($sizeStr), $sizes, true);
                                            $stock = ($idx !== false && isset($qtys[$idx])) ? (int)$qtys[$idx] : 0;
                                        } else {
                                            $stock = (int)($mp->stock ?? 0);
                                        }
                                        $minQty = max(1, (int)($mp->minimum_qty ?? 1));
                                        $preordered = (int)($mp->preordered ?? 0);

                                        if ($mp->qualityBrand) {
                                            $qualityBrand = $mp->qualityBrand;
                                            $qualityName = getLocalizedQualityName($qualityBrand);
                                            $qualityLogo = $qualityBrand->logo_url ?? null;
                                        }
                                    }
                                }

                                $size = $product['size'] ?? '';
                                $size = is_array($size) ? implode(', ', $size) : (string)$size;

                                $color = $product['color'] ?? '';
                                $color = is_array($color) ? ($color[0] ?? '') : (string)$color;
                            @endphp

                            <div class="m-cart__item" id="cart-row-{{ $domKey }}" data-row-key="{{ $rowKey }}">
                                {{-- Product Image --}}
                                <div class="m-cart__item-image">
                                    <a href="{{ $productUrl }}">
                                        <img src="{{ $photoUrl }}" alt="{{ $displayName }}" loading="lazy"
                                             onerror="this.onerror=null; this.src='{{ asset('assets/images/noimage.png') }}';">
                                    </a>
                                </div>

                                {{-- Product Details --}}
                                <div class="m-cart__item-details">
                                    <a href="{{ $productUrl }}" class="m-cart__item-name">
                                        {{ Str::limit($displayName, 60) }}
                                    </a>

                                    <div class="m-cart__item-meta">
                                        @if($itemSku)
                                            <span class="m-cart__item-sku">{{ $itemSku }}</span>
                                        @endif
                                        @if($brandName)
                                            <span class="m-cart__item-brand">
                                                @if($brandLogo)
                                                    <img src="{{ $brandLogo }}" alt="" class="m-cart__brand-logo">
                                                @endif
                                                {{ $brandName }}
                                            </span>
                                        @endif
                                        @if($qualityName)
                                            <span class="m-cart__item-quality">
                                                @if($qualityLogo)
                                                    <img src="{{ $qualityLogo }}" alt="" class="m-cart__quality-logo">
                                                @endif
                                                {{ $qualityName }}
                                            </span>
                                        @endif
                                    </div>

                                    @if ((!empty($size) && $size !== '_') || (!empty($color) && $color !== '_'))
                                        <div class="m-cart__item-variants">
                                            @if (!empty($color) && $color !== '_')
                                                <span class="m-cart__item-color" style="--swatch-color: #{{ ltrim($color, '#') }};"></span>
                                            @endif
                                            @if (!empty($size) && $size !== '_')
                                                <span class="m-cart__item-size">{{ $size }}</span>
                                            @endif
                                        </div>
                                    @endif

                                    @if ($preordered)
                                        <span class="m-cart__item-preorder">
                                            <i class="fas fa-clock"></i> @lang('Preorder')
                                        </span>
                                    @endif

                                    {{-- Reservation Timer --}}
                                    @if (!empty($product['reservation']) && !$product['reservation']['is_expired'])
                                        <div class="m-cart__reservation-timer"
                                             data-expires="{{ $product['reservation']['expires_at'] }}"
                                             data-remaining="{{ $product['reservation']['remaining_seconds'] }}">
                                            <i class="fas fa-hourglass-half"></i>
                                            <span class="timer-text">
                                                @lang('Reserved for') <span class="timer-value">{{ $product['reservation']['remaining_minutes'] }}</span> @lang('min')
                                            </span>
                                        </div>
                                    @endif

                                    {{-- Mobile Price --}}
                                    <div class="m-cart__item-price-mobile">
                                        {{ $showPrice($itemPrice) }}
                                    </div>
                                </div>

                                {{-- Price Column (Desktop) --}}
                                <div class="m-cart__item-price">
                                    <span class="m-cart__price-label">@lang('Price')</span>
                                    <span class="m-cart__price-value">{{ $showPrice($itemPrice) }}</span>
                                </div>

                                {{-- Quantity --}}
                                <div class="m-cart__item-qty">
                                    @if (data_get($product, 'item.type') == 'Physical')
                                        <div class="m-cart__qty-control" data-min="{{ $minQty }}" data-stock="{{ $stock }}" data-preordered="{{ $preordered }}">
                                            <button type="button" class="m-cart__qty-btn quantity-down"
                                                data-min-qty="{{ $minQty }}">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="text" id="qty{{ $domKey }}"
                                                   value="{{ $qty }}"
                                                   class="m-cart__qty-input" readonly>
                                            <button type="button" class="m-cart__qty-btn quantity-up"
                                                data-stock="{{ $stock }}"
                                                data-preordered="{{ $preordered }}">
                                                <i class="fas fa-plus"></i>
                                            </button>

                                            {{-- Inline limit message --}}
                                            <div class="m-cart__qty-hint"></div>

                                            {{-- Hidden inputs for JS --}}
                                            <input type="hidden" class="prodid" value="{{ data_get($product, 'item.id') }}">
                                            <input type="hidden" class="itemid" value="{{ $rowKey }}">
                                            <input type="hidden" class="domkey" value="{{ $domKey }}">
                                            <input type="hidden" class="size_qty" value="{{ $product['size_qty'] ?? '' }}">
                                            <input type="hidden" class="size_price" value="{{ $product['size_price'] ?? 0 }}">
                                            <input type="hidden" class="minimum_qty" value="{{ $minQty }}">
                                            <input type="hidden" class="stock_val" value="{{ $stock }}">
                                            <input type="hidden" class="preordered_val" value="{{ $preordered }}">
                                        </div>
                                    @else
                                        <span class="m-cart__qty-static">1</span>
                                    @endif
                                </div>

                                {{-- Subtotal --}}
                                <div class="m-cart__item-subtotal">
                                    <span class="m-cart__price-label">@lang('Subtotal')</span>
                                    <span class="m-cart__price-value" id="prc{{ $domKey }}">
                                        {{ $showPrice($totalItemPrice) }}
                                    </span>
                                    @if ($discount > 0)
                                        <span class="m-cart__discount">-{{ $discount }}%</span>
                                    @endif
                                </div>

                                {{-- Remove Button --}}
                                <div class="m-cart__item-remove">
                                    <button type="button"
                                       class="m-cart__remove-btn cart-remove-ajax"
                                       data-url="{{ route('product.cart.remove', $rowKey) }}"
                                       data-row-key="{{ $rowKey }}"
                                       data-dom-key="{{ $domKey }}"
                                       data-vendor-id="{{ $vendorId }}"
                                       title="@lang('Remove')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Cart Summary --}}
                    <div class="m-cart__summary">
                        @php
                            $vendorDiscount = 0;
                            $vendorTotal = $vendorGroup['total'] ?? 0;
                            foreach ($vendorGroup['products'] as $product) {
                                if (!empty($product['discount'])) {
                                    $total_itemprice = (float)($product['item_price'] ?? 0) * (int)($product['qty'] ?? 1);
                                    $tdiscount = ($total_itemprice * (float)$product['discount']) / 100;
                                    $vendorDiscount += $tdiscount;
                                }
                            }
                            $vendorSubtotal = $vendorTotal + $vendorDiscount;
                        @endphp

                        <h5 class="m-cart__summary-title">@lang('Order Summary')</h5>

                        <div class="m-cart__summary-row">
                            <span>@lang('Subtotal') ({{ $vendorGroup['count'] ?? 0 }})</span>
                            <span>{{ $showPrice($vendorSubtotal) }}</span>
                        </div>

                        @if($vendorDiscount > 0)
                        <div class="m-cart__summary-row m-cart__summary-row--discount">
                            <span>@lang('Discount')</span>
                            <span>- {{ $showPrice($vendorDiscount) }}</span>
                        </div>
                        @endif

                        <div class="m-cart__summary-row m-cart__summary-row--total">
                            <span>@lang('Total')</span>
                            <span class="total-cart-price">{{ $showPrice($vendorTotal) }}</span>
                        </div>

                        <div class="m-cart__summary-actions">
                            @auth
                                <a href="{{ route('front.checkout.vendor', $vendorId) }}" class="m-btn m-btn--primary m-btn--block">
                                    <i class="fas fa-lock"></i>
                                    @lang('Checkout')
                                </a>
                            @else
                                <a href="{{ route('user.login', ['redirect' => 'cart']) }}" class="m-btn m-btn--primary m-btn--block">
                                    <i class="fas fa-sign-in-alt"></i>
                                    @lang('Login to Checkout')
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        @endif
    </div>
</div>

@push('scripts')
<script>
// Set global messages for qty-control.js
window.QTY_MESSAGES = {
    stockLimit: '{{ __("Stock limit reached") }}',
    minLimit: '{{ __("Minimum quantity is") }}'
};

// Currency formatting helper
var currSign = '{{ $currSign }}';
var currPos = {{ $currPos }};
var currValue = {{ $currValue }};

function formatPrice(price) {
    var converted = Math.round(price * currValue * 100) / 100;
    return currPos == 0 ? currSign + converted : converted + currSign;
}

jQuery(document).ready(function($) {

    // ========================================
    // AJAX Delete Item
    // ========================================
    $(document).on('click', '.cart-remove-ajax', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var $btn = $(this);
        var url = $btn.data('url');
        var domKey = $btn.data('dom-key');
        var vendorId = $btn.data('vendor-id');
        var $item = $('#cart-row-' + domKey);
        var $vendor = $btn.closest('.m-cart__vendor');

        if (!url) {
            console.error('Cart remove: No URL found');
            return;
        }

        // Disable button and add loading state
        $btn.prop('disabled', true);
        $item.css('opacity', '0.5');

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(resp) {
                console.log('Cart remove response:', resp);

                if (resp && resp.success) {
                    // Animate and remove item
                    $item.slideUp(300, function() {
                        $(this).remove();

                        // Check if vendor has any items left
                        var vendorItemsCount = resp.vendorCounts ? (resp.vendorCounts[vendorId] || 0) : 0;
                        if (vendorItemsCount === 0) {
                            // Remove entire vendor section
                            $vendor.slideUp(300, function() {
                                $(this).remove();

                                // Check if cart is now empty
                                if (resp.itemCount === 0) {
                                    location.reload();
                                }
                            });
                        } else {
                            // Update vendor count and total
                            $vendor.find('.m-cart__vendor-count').text(vendorItemsCount + ' {{ __("Items") }}');
                            var vendorTotal = resp.vendorTotals ? (resp.vendorTotals[vendorId] || 0) : 0;
                            $vendor.find('.total-cart-price').text(formatPrice(vendorTotal));

                            // Update summary subtotal (items count)
                            $vendor.find('.m-cart__summary-row:first span:first').text('{{ __("Subtotal") }} (' + vendorItemsCount + ')');
                        }
                    });

                    // Update header cart icon count
                    if (typeof updateCartCount === 'function') {
                        updateCartCount(resp.itemCount);
                    }
                    // Update cart icons
                    $('.cart-total, .cart-count, .cart_total').text(resp.itemCount);
                    // صامت - بدون رسائل
                } else {
                    // صامت
                    $item.css('opacity', '1');
                    $btn.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Cart remove error:', status, error, xhr.responseText);
                // صامت
                $item.css('opacity', '1');
                $btn.prop('disabled', false);
            }
        });
    });

    // ========================================
    // Quantity Controls
    // ========================================

    // ========================================
    // Helper: Show elegant inline hint
    // ========================================
    function showQtyHint($wrapper, message, type) {
        var $hint = $wrapper.find('.m-cart__qty-hint');
        $hint.removeClass('m-cart__qty-hint--min m-cart__qty-hint--max').addClass('m-cart__qty-hint--' + type);
        $hint.html('<i class="fas fa-info-circle"></i> ' + message).fadeIn(200);

        // Auto hide after 3 seconds
        clearTimeout($hint.data('hideTimer'));
        $hint.data('hideTimer', setTimeout(function() {
            $hint.fadeOut(200);
        }, 3000));
    }

    // Increase quantity - AJAX call (step = minimum_qty for bundles)
    $(document).on('click', '.quantity-up', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var $btn = $(this);
        var $wrapper = $btn.closest('.m-cart__qty-control');
        var $qtyInput = $wrapper.find('.m-cart__qty-input');
        var domKey = $wrapper.find('.domkey').val();
        var stock = parseInt($wrapper.find('.stock_val').val()) || 0;
        var preordered = parseInt($wrapper.find('.preordered_val').val()) || 0;
        var minQty = parseInt($wrapper.find('.minimum_qty').val()) || 1; // Step size
        var currentQty = parseInt($qtyInput.val()) || minQty;

        // Check stock limit - need at least minQty available
        if (!preordered && stock < minQty) {
            var maxAllowed = currentQty + stock;
            showQtyHint($wrapper, '{{ __("Available stock") }}: ' + maxAllowed, 'max');
            return;
        }

        var prodId = $wrapper.find('.prodid').val();
        var itemId = $wrapper.find('.itemid').val();
        var sizeQty = $wrapper.find('.size_qty').val() || '';
        var sizePrice = $wrapper.find('.size_price').val() || 0;

        $btn.prop('disabled', true);

        $.ajax({
            url: '/addbyone',
            type: 'GET',
            dataType: 'json',
            data: { id: prodId, itemid: itemId, size_qty: sizeQty, size_price: sizePrice },
            success: function(resp) {
                if (resp === 0 || resp === '0') {
                    // Server rejected - show stock limit message
                    showQtyHint($wrapper, '{{ __("Available stock") }}: ' + currentQty, 'max');
                    return;
                }
                $qtyInput.val(resp[1]);
                $('#prc' + domKey).html(resp[2]);
                $wrapper.closest('.m-cart__vendor').find('.total-cart-price').html(resp[0]);

                // Update stock value from server response
                if (resp.stock !== undefined) {
                    $wrapper.find('.stock_val').val(resp.stock);
                }

                // Hide any existing hint
                $wrapper.find('.m-cart__qty-hint').fadeOut(200);
            },
            error: function() {
                console.log('Qty increase failed');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // Decrease quantity - AJAX call
    $(document).on('click', '.quantity-down', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var $btn = $(this);
        var $wrapper = $btn.closest('.m-cart__qty-control');
        var $qtyInput = $wrapper.find('.m-cart__qty-input');
        var domKey = $wrapper.find('.domkey').val();
        var minQty = parseInt($wrapper.find('.minimum_qty').val()) || 1;
        var currentQty = parseInt($qtyInput.val()) || 1;

        // Check minimum limit - show elegant message
        if (currentQty <= minQty) {
            showQtyHint($wrapper, '{{ __("Minimum order") }}: ' + minQty, 'min');
            return;
        }

        var prodId = $wrapper.find('.prodid').val();
        var itemId = $wrapper.find('.itemid').val();
        var sizeQty = $wrapper.find('.size_qty').val() || '';
        var sizePrice = $wrapper.find('.size_price').val() || 0;

        $btn.prop('disabled', true);

        $.ajax({
            url: '/reducebyone',
            type: 'GET',
            dataType: 'json',
            data: { id: prodId, itemid: itemId, size_qty: sizeQty, size_price: sizePrice },
            success: function(resp) {
                if (resp === 0 || resp === '0') {
                    // Server rejected - show min limit message
                    showQtyHint($wrapper, '{{ __("Minimum order") }}: ' + minQty, 'min');
                    return;
                }
                $qtyInput.val(resp[1]);
                $('#prc' + domKey).html(resp[2]);
                $wrapper.closest('.m-cart__vendor').find('.total-cart-price').html(resp[0]);

                // Update stock value from server response
                if (resp.stock !== undefined) {
                    $wrapper.find('.stock_val').val(resp.stock);
                }

                // Hide any existing hint
                $wrapper.find('.m-cart__qty-hint').fadeOut(200);
            },
            error: function() {
                console.log('Qty decrease failed');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // ========================================
    // Reservation Timer Countdown
    // ========================================
    function updateReservationTimers() {
        $('.m-cart__reservation-timer').each(function() {
            var $timer = $(this);
            var remaining = parseInt($timer.data('remaining')) || 0;

            if (remaining <= 0) {
                $timer.html('<i class="fas fa-exclamation-triangle"></i> <span class="timer-text">{{ __("Expired") }}</span>');
                $timer.removeClass('m-cart__reservation-timer--warning').addClass('m-cart__reservation-timer--danger');
                return;
            }

            // Decrease remaining time
            remaining--;
            $timer.data('remaining', remaining);

            // Calculate minutes and seconds
            var mins = Math.floor(remaining / 60);
            var secs = remaining % 60;

            // Update display
            var display = mins > 0
                ? '{{ __("Reserved for") }} <span class="timer-value">' + mins + '</span> {{ __("min") }}'
                : '{{ __("Reserved for") }} <span class="timer-value">' + secs + '</span> {{ __("sec") }}';
            $timer.find('.timer-text').html(display);

            // Change color based on remaining time
            if (remaining <= 60) {
                $timer.removeClass('m-cart__reservation-timer--warning').addClass('m-cart__reservation-timer--danger');
            } else if (remaining <= 300) {
                $timer.addClass('m-cart__reservation-timer--warning');
            }
        });
    }

    // Update timers every second
    if ($('.m-cart__reservation-timer').length > 0) {
        setInterval(updateReservationTimers, 1000);
    }
});
</script>
@endpush
