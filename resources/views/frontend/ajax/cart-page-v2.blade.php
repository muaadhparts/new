{{--
    =====================================================================
    CART PAGE V2 - نظام السلة الموحد الجديد
    =====================================================================
    يستخدم CartHelper الجديد مع:
    - cartKey موحد: mp{merchant_product_id}:{size}:{color}:{values}
    - dom_key آمن للـ DOM
    - بيانات كاملة من merchant_products
    =====================================================================
--}}

@php
    use App\Helpers\CartHelper;
    use Illuminate\Support\Facades\Storage;

    $cart = CartHelper::getCart();
    $products = $cart['items'] ?? [];
    $totalPrice = $cart['totalPrice'] ?? 0;
    $totalQty = $cart['totalQty'] ?? 0;
    $productsByVendor = CartHelper::groupByVendor();

    // العملة
    $curr = \App\Models\Currency::where('is_default', 1)->first();
    $currValue = $curr->value ?? 1;
    $currSign = $curr->sign ?? '$';
    $currPos = $gs->currency_format ?? 0;

    // دالة مساعدة لعرض السعر
    $showPrice = function($price) use ($currValue, $currSign, $currPos) {
        $converted = round($price * $currValue, 2);
        return $currPos == 0 ? $currSign . $converted : $converted . $currSign;
    };
@endphp

<div class="cart-page-v2">

    @if (empty($products))
        {{-- السلة فارغة --}}
        <div class="empty-cart text-center py-5">
            <div class="mb-4">
                <i class="fas fa-shopping-cart fa-4x text-muted"></i>
            </div>
            <h4>{{ __('Your cart is empty') }}</h4>
            <p class="text-muted">{{ __('Add some products to your cart and come back here') }}</p>
            <a href="{{ route('front.index') }}" class="template-btn mt-3">
                <i class="fas fa-arrow-left me-2"></i>{{ __('Continue Shopping') }}
            </a>
        </div>
    @else
        <div class="row">
            {{-- جدول السلة --}}
            <div class="col-lg-8">
                <div class="cart-table-wrapper">

                    @foreach ($productsByVendor as $vendorId => $vendorGroup)
                        {{-- رأس التاجر --}}
                        <div class="vendor-header bg-light p-3 mb-3 rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-store me-2"></i>
                                    <strong>{{ $vendorGroup['vendor_name'] }}</strong>
                                </div>
                                <span class="badge bg-primary">
                                    {{ $vendorGroup['count'] }} {{ __('items') }}
                                </span>
                            </div>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered cart-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 80px;">{{ __('Image') }}</th>
                                        <th>{{ __('Product') }}</th>
                                        <th style="width: 100px;">{{ __('SKU') }}</th>
                                        <th style="width: 100px;">{{ __('Unit Price') }}</th>
                                        <th style="width: 150px;">{{ __('Quantity') }}</th>
                                        <th style="width: 100px;">{{ __('Subtotal') }}</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($vendorGroup['items'] as $cartKey => $item)
                                        @php
                                            $domKey = $item['dom_key'] ?? str_replace([':', '#', ' '], ['_', '', ''], $cartKey);
                                            $photo = $item['photo'] ?? '';
                                            $photoUrl = $photo ? (filter_var($photo, FILTER_VALIDATE_URL) ? $photo : Storage::url($photo)) : asset('assets/images/noimage.png');
                                        @endphp

                                        <tr id="cart-row-{{ $domKey }}" data-cart-key="{{ $cartKey }}">
                                            {{-- الصورة --}}
                                            <td class="align-middle">
                                                <img src="{{ $photoUrl }}"
                                                     alt="{{ $item['name'] ?? '' }}"
                                                     class="img-thumbnail"
                                                     style="width: 60px; height: 60px; object-fit: cover;">
                                            </td>

                                            {{-- معلومات المنتج --}}
                                            <td class="align-middle">
                                                <div class="product-info">
                                                    {{-- اسم المنتج --}}
                                                    <a href="{{ route('front.product', $item['slug'] ?? '') }}"
                                                       class="product-name fw-bold text-dark">
                                                        {{ app()->getLocale() == 'ar' && !empty($item['name_ar']) ? $item['name_ar'] : $item['name'] }}
                                                    </a>

                                                    {{-- تفاصيل إضافية --}}
                                                    <div class="product-details small text-muted mt-1">
                                                        @if (!empty($item['brand_name']))
                                                            <span class="badge bg-secondary me-1">{{ $item['brand_name'] }}</span>
                                                        @endif
                                                        @if (!empty($item['quality_name']))
                                                            <span class="badge bg-info me-1">{{ $item['quality_name'] }}</span>
                                                        @endif
                                                        @if (!empty($item['size']) && $item['size'] !== '_')
                                                            <span class="badge bg-outline-dark me-1">{{ __('Size') }}: {{ $item['size'] }}</span>
                                                        @endif
                                                        @if (!empty($item['color']) && $item['color'] !== '_')
                                                            <span class="badge me-1" style="background-color: #{{ ltrim($item['color'], '#') }}; width: 20px; height: 20px; display: inline-block; border: 1px solid #ddd; border-radius: 3px;"></span>
                                                        @endif
                                                    </div>

                                                    {{-- حالة المخزون --}}
                                                    @if (!empty($item['preordered']) && $item['preordered'] == 1)
                                                        <span class="badge bg-warning text-dark mt-1">
                                                            <i class="fas fa-clock me-1"></i>{{ __('Preorder') }}
                                                        </span>
                                                    @endif

                                                    {{-- حالة المنتج --}}
                                                    @if (!empty($item['product_condition']))
                                                        <small class="text-muted d-block">{{ __('Condition') }}: {{ $item['product_condition'] }}</small>
                                                    @endif
                                                </div>
                                            </td>

                                            {{-- SKU --}}
                                            <td class="align-middle text-center">
                                                <code class="small">{{ $item['sku'] ?? 'N/A' }}</code>
                                            </td>

                                            {{-- سعر الوحدة --}}
                                            <td class="align-middle text-center">
                                                <strong>{{ $showPrice($item['unit_price'] ?? 0) }}</strong>
                                                @if (!empty($item['previous_price']) && $item['previous_price'] > $item['unit_price'])
                                                    <br><del class="small text-muted">{{ $showPrice($item['previous_price']) }}</del>
                                                @endif
                                            </td>

                                            {{-- التحكم بالكمية --}}
                                            <td class="align-middle">
                                                <div class="cart-qty-wrapper d-flex justify-content-center align-items-center">
                                                    <div class="input-group" style="max-width: 130px;">
                                                        {{-- زر النقص --}}
                                                        <button type="button"
                                                                class="btn btn-outline-secondary btn-sm cart-v2-minus"
                                                                data-cart-key="{{ $cartKey }}"
                                                                data-dom-key="{{ $domKey }}"
                                                                data-min-qty="{{ $item['minimum_qty'] ?? 1 }}">
                                                            <i class="fas fa-minus"></i>
                                                        </button>

                                                        {{-- حقل الكمية --}}
                                                        <input type="text"
                                                               id="qty-{{ $domKey }}"
                                                               class="form-control form-control-sm text-center cart-qty-input"
                                                               value="{{ $item['qty'] ?? 1 }}"
                                                               readonly>

                                                        {{-- زر الزيادة --}}
                                                        <button type="button"
                                                                class="btn btn-outline-secondary btn-sm cart-v2-plus"
                                                                data-cart-key="{{ $cartKey }}"
                                                                data-dom-key="{{ $domKey }}"
                                                                data-stock="{{ $item['stock'] ?? 0 }}"
                                                                data-preordered="{{ $item['preordered'] ?? 0 }}">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                {{-- معلومات المخزون --}}
                                                <small class="text-muted d-block text-center mt-1">
                                                    @if (!empty($item['stock']) && $item['stock'] > 0)
                                                        {{ __('Stock') }}: {{ $item['stock'] }}
                                                    @elseif (!empty($item['preordered']) && $item['preordered'] == 1)
                                                        {{ __('Preorder available') }}
                                                    @endif
                                                </small>

                                                {{-- الحد الأدنى --}}
                                                @if (!empty($item['minimum_qty']) && $item['minimum_qty'] > 1)
                                                    <small class="text-warning d-block text-center">
                                                        {{ __('Min') }}: {{ $item['minimum_qty'] }}
                                                    </small>
                                                @endif
                                            </td>

                                            {{-- المجموع الفرعي --}}
                                            <td class="align-middle text-center cart-row-total" id="total-{{ $domKey }}">
                                                <strong>{{ $showPrice($item['total_price'] ?? 0) }}</strong>
                                                @if (!empty($item['discount']) && $item['discount'] > 0)
                                                    <br><span class="badge bg-success">{{ $item['discount'] }}% {{ __('Off') }}</span>
                                                @endif
                                            </td>

                                            {{-- زر الحذف --}}
                                            <td class="align-middle text-center">
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger cart-v2-remove"
                                                        data-cart-key="{{ $cartKey }}"
                                                        data-dom-key="{{ $domKey }}"
                                                        title="{{ __('Remove') }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach

                </div>
            </div>

            {{-- ملخص السلة --}}
            <div class="col-lg-4">
                <div class="cart-summary card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-receipt me-2"></i>{{ __('Order Summary') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        {{-- عدد العناصر --}}
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('Items') }}:</span>
                            <span class="cart-items-count">{{ count($products) }}</span>
                        </div>

                        {{-- إجمالي الكميات --}}
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('Total Quantity') }}:</span>
                            <span class="cart-qty-total">{{ $totalQty }}</span>
                        </div>

                        <hr>

                        {{-- المجموع الكلي --}}
                        <div class="d-flex justify-content-between mb-3">
                            <span class="fw-bold fs-5">{{ __('Total') }}:</span>
                            <span class="fw-bold fs-5 total-cart-price">{{ $showPrice($totalPrice) }}</span>
                        </div>

                        {{-- أزرار الإجراءات --}}
                        <div class="d-grid gap-2">
                            <a href="{{ route('front.checkout') }}" class="btn btn-primary btn-lg">
                                <i class="fas fa-credit-card me-2"></i>{{ __('Proceed to Checkout') }}
                            </a>
                            <a href="{{ route('front.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>{{ __('Continue Shopping') }}
                            </a>
                        </div>
                    </div>
                </div>

                {{-- كوبون الخصم --}}
                <div class="coupon-section card mt-3">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-tag me-2"></i>{{ __('Have a coupon?') }}
                        </h6>
                        <div class="input-group">
                            <input type="text" id="coupon-code" class="form-control" placeholder="{{ __('Enter coupon code') }}">
                            <button class="btn btn-outline-primary" type="button" id="apply-coupon">
                                {{ __('Apply') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- JavaScript للسلة الجديدة --}}
<script>
$(document).ready(function() {
    'use strict';

    // ============ زيادة الكمية ============
    $(document).on('click', '.cart-v2-plus', function(e) {
        e.preventDefault();

        var $btn = $(this);
        var cartKey = $btn.data('cart-key');
        var domKey = $btn.data('dom-key');
        var stock = parseInt($btn.data('stock')) || 0;
        var preordered = parseInt($btn.data('preordered')) || 0;

        var $qtyInput = $('#qty-' + domKey);
        var $totalCell = $('#total-' + domKey);
        var currentQty = parseInt($qtyInput.val()) || 1;

        // فحص المخزون محلياً
        if (stock > 0 && currentQty >= stock && preordered == 0) {
            toastr.warning('{{ __("Stock limit reached") }}: ' + stock);
            return;
        }

        // تعطيل الزر مؤقتاً
        $btn.prop('disabled', true);

        $.ajax({
            url: '/cart/v2/increase',
            type: 'GET',
            dataType: 'json',
            data: { cart_key: cartKey },
            success: function(resp) {
                if (resp.ok || resp.status === 'success') {
                    // تحديث الكمية
                    $qtyInput.val(resp.qty || resp[1]);

                    // تحديث سعر الصف
                    var rowHtml = '<strong>' + (resp.row_total || resp[2]) + '</strong>';
                    if (resp.discount && resp.discount > 0) {
                        rowHtml += '<br><span class="badge bg-success">' + resp.discount + '% {{ __("Off") }}</span>';
                    }
                    $totalCell.html(rowHtml);

                    // تحديث إجمالي السلة
                    $('.total-cart-price').html(resp.cart_total || resp[0]);

                    toastr.success('{{ __("Quantity updated") }}');
                } else {
                    toastr.error(resp.msg || '{{ __("Cannot increase quantity") }}');
                }
            },
            error: function(xhr) {
                var msg = '{{ __("Error occurred") }}';
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    msg = xhr.responseJSON.msg;
                }
                toastr.error(msg);
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // ============ إنقاص الكمية ============
    $(document).on('click', '.cart-v2-minus', function(e) {
        e.preventDefault();

        var $btn = $(this);
        var cartKey = $btn.data('cart-key');
        var domKey = $btn.data('dom-key');
        var minQty = parseInt($btn.data('min-qty')) || 1;

        var $qtyInput = $('#qty-' + domKey);
        var $totalCell = $('#total-' + domKey);
        var currentQty = parseInt($qtyInput.val()) || 1;

        // فحص الحد الأدنى محلياً
        if (currentQty <= minQty) {
            toastr.warning('{{ __("Minimum quantity is") }} ' + minQty);
            return;
        }

        // تعطيل الزر مؤقتاً
        $btn.prop('disabled', true);

        $.ajax({
            url: '/cart/v2/decrease',
            type: 'GET',
            dataType: 'json',
            data: { cart_key: cartKey },
            success: function(resp) {
                if (resp.ok || resp.status === 'success') {
                    // تحديث الكمية
                    $qtyInput.val(resp.qty || resp[1]);

                    // تحديث سعر الصف
                    var rowHtml = '<strong>' + (resp.row_total || resp[2]) + '</strong>';
                    if (resp.discount && resp.discount > 0) {
                        rowHtml += '<br><span class="badge bg-success">' + resp.discount + '% {{ __("Off") }}</span>';
                    }
                    $totalCell.html(rowHtml);

                    // تحديث إجمالي السلة
                    $('.total-cart-price').html(resp.cart_total || resp[0]);

                    toastr.success('{{ __("Quantity updated") }}');
                } else {
                    toastr.warning(resp.msg || '{{ __("Cannot decrease quantity") }}');
                }
            },
            error: function(xhr) {
                var msg = '{{ __("Error occurred") }}';
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    msg = xhr.responseJSON.msg;
                }
                toastr.error(msg);
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // ============ حذف عنصر ============
    $(document).on('click', '.cart-v2-remove', function(e) {
        e.preventDefault();

        var $btn = $(this);
        var cartKey = $btn.data('cart-key');
        var domKey = $btn.data('dom-key');

        if (!confirm('{{ __("Are you sure you want to remove this item?") }}')) {
            return;
        }

        // تعطيل الزر مؤقتاً
        $btn.prop('disabled', true);

        $.ajax({
            url: '/cart/v2/remove',
            type: 'GET',
            dataType: 'json',
            data: { cart_key: cartKey },
            success: function(resp) {
                if (resp.ok || resp.status === 'success') {
                    // حذف الصف من الجدول
                    $('#cart-row-' + domKey).fadeOut(300, function() {
                        $(this).remove();

                        // تحديث إجمالي السلة
                        $('.total-cart-price').html(resp.cart_total);
                        $('.cart-items-count').html(resp.cart_count);
                        $('.cart-qty-total').html(resp.totalQty);

                        // تحديث عداد السلة في الهيدر
                        if (window.applyCartState) {
                            window.applyCartState({ cart_count: resp.cart_count, cart_total: resp.cart_total });
                        }

                        // إذا السلة فارغة، إعادة تحميل الصفحة
                        if (resp.cart_count === 0) {
                            location.reload();
                        }
                    });

                    toastr.success(resp.success || '{{ __("Item removed") }}');
                } else {
                    toastr.error(resp.msg || '{{ __("Cannot remove item") }}');
                }
            },
            error: function(xhr) {
                var msg = '{{ __("Error occurred") }}';
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    msg = xhr.responseJSON.msg;
                }
                toastr.error(msg);
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // ============ تطبيق الكوبون ============
    $(document).on('click', '#apply-coupon', function() {
        var code = $('#coupon-code').val().trim();
        if (!code) {
            toastr.warning('{{ __("Please enter a coupon code") }}');
            return;
        }

        $.ajax({
            url: '/carts/coupon',
            type: 'GET',
            data: { code: code },
            success: function(resp) {
                if (resp.success) {
                    toastr.success(resp.success);
                    location.reload();
                } else if (resp.error) {
                    toastr.error(resp.error);
                }
            },
            error: function() {
                toastr.error('{{ __("Error applying coupon") }}');
            }
        });
    });
});
</script>

<style>
.cart-page-v2 .cart-table th {
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    white-space: nowrap;
}

.cart-page-v2 .product-name {
    text-decoration: none;
}

.cart-page-v2 .product-name:hover {
    color: var(--primary-color) !important;
}

.cart-page-v2 .cart-qty-wrapper .input-group {
    flex-wrap: nowrap;
}

.cart-page-v2 .cart-qty-wrapper .btn {
    padding: 0.25rem 0.5rem;
}

.cart-page-v2 .cart-qty-wrapper .form-control {
    max-width: 50px;
    font-weight: bold;
}

.cart-page-v2 .cart-summary {
    position: sticky;
    top: 20px;
}

.cart-page-v2 .vendor-header {
    border-left: 4px solid var(--primary-color, #007bff);
}

@media (max-width: 768px) {
    .cart-page-v2 .cart-table {
        font-size: 0.85rem;
    }

    .cart-page-v2 .cart-table th,
    .cart-page-v2 .cart-table td {
        padding: 0.5rem;
    }
}
</style>
