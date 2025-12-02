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
            {{-- سلة لكل تاجر على حدة --}}
            <div class="col-lg-12">
                <div class="cart-table-wrapper">

                    @foreach ($productsByVendor as $vendorId => $vendorGroup)
                    <div class="vendor-cart-section mb-5" style="background: #ffffff; border-radius: 20px; box-shadow: 0 8px 24px rgba(13, 148, 136, 0.1); border: 2px solid #e0f2fe; overflow: hidden;">
                        {{-- رأس التاجر --}}
                        <div class="vendor-header" style="background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); padding: 1.5rem; color: white;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-1" style="font-weight: 800; letter-spacing: 0.5px;">
                                        <i class="fas fa-store me-2"></i>{{ $vendorGroup['vendor_name'] }}
                                    </h4>
                                    <p class="mb-0" style="opacity: 0.9; font-size: 0.95rem;">
                                        <i class="fas fa-box me-1"></i>{{ $vendorGroup['count'] }} @lang('Items')
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="row g-0">
                        {{-- Products Table --}}
                        <div class="col-lg-8">
                        <div class="table-responsive" style="padding: 2rem;">
                            <table class="table table-bordered cart-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 80px;">{{ __('Image') }}</th>
                                        <th>{{ __('Product') }}</th>
                                        <th style="width: 100px;">{{ __('SKU') }}</th>
                                        <th style="width: 100px;">{{ __('Brand') }}</th>
                                        <th style="width: 100px;">{{ __('Quality') }}</th>
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

                                                    {{-- تفاصيل إضافية (Size/Color فقط) --}}
                                                    @if ((!empty($item['size']) && $item['size'] !== '_') || (!empty($item['color']) && $item['color'] !== '_'))
                                                    <div class="product-details small text-muted mt-1">
                                                        @if (!empty($item['size']) && $item['size'] !== '_')
                                                            <span class="badge bg-outline-dark me-1">{{ __('Size') }}: {{ $item['size'] }}</span>
                                                        @endif
                                                        @if (!empty($item['color']) && $item['color'] !== '_')
                                                            <span class="badge me-1" style="background-color: #{{ ltrim($item['color'], '#') }}; width: 20px; height: 20px; display: inline-block; border: 1px solid #ddd; border-radius: 3px;"></span>
                                                        @endif
                                                    </div>
                                                    @endif

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
                                                <code class="small fw-bold">{{ $item['sku'] ?? 'N/A' }}</code>
                                            </td>

                                            {{-- Brand --}}
                                            <td class="align-middle text-center">
                                                <span>{{ $item['brand_name'] ?? '-' }}</span>
                                            </td>

                                            {{-- Quality --}}
                                            <td class="align-middle text-center">
                                                <span>{{ $item['quality_name'] ?? '-' }}</span>
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
                        </div>

                        {{-- Vendor Cart Summary - INDEPENDENT per vendor --}}
                        <div class="col-lg-4">
                            <div class="cart-summary" style="margin: 2rem; background: linear-gradient(135deg, #ffffff 0%, #f0fdfa 100%); border-radius: 16px; padding: 2rem; border: 2px solid #14b8a6;">
                                <h5 class="cart-summary-title" style="color: #0f172a; font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 3px solid; border-image: linear-gradient(90deg, #0d9488 0%, #14b8a6 50%, #2dd4bf 100%) 1;">
                                    @lang('Cart Summary')
                                </h5>
                                <div class="cart-summary-content">
                                    @php
                                        // Calculate discount for THIS vendor only
                                        $vendorDiscount = 0;
                                        $vendorTotal = 0;
                                        foreach ($vendorGroup['items'] as $item) {
                                            $vendorTotal += $item['total_price'] ?? 0;
                                            if (!empty($item['discount'])) {
                                                $total_itemprice = (float)($item['unit_price'] ?? 0) * (int)($item['qty'] ?? 1);
                                                $tdiscount = ($total_itemprice * (float)$item['discount']) / 100;
                                                $vendorDiscount += $tdiscount;
                                            }
                                        }
                                        $vendorSubtotal = $vendorTotal + $vendorDiscount;
                                    @endphp

                                    <div class="cart-summary-item d-flex justify-content-between" style="padding: 1rem 0; border-bottom: 1px solid #e0f2fe;">
                                        <p class="cart-summary-subtitle" style="color: #64748b; font-weight: 600; margin: 0;">
                                            @lang('Subtotal') ({{ $vendorGroup['count'] }} @lang('Items'))
                                        </p>
                                        <p class="cart-summary-price" style="color: #0d9488; font-weight: 700; font-size: 1.1rem; margin: 0;">
                                            {{ $showPrice($vendorSubtotal) }}
                                        </p>
                                    </div>

                                    @if($vendorDiscount > 0)
                                    <div class="cart-summary-item d-flex justify-content-between" style="padding: 1rem 0; border-bottom: 1px solid #e0f2fe;">
                                        <p class="cart-summary-subtitle" style="color: #64748b; font-weight: 600; margin: 0;">
                                            @lang('Discount')
                                        </p>
                                        <p class="cart-summary-price" style="color: #ef4444; font-weight: 700; font-size: 1.1rem; margin: 0;">
                                            - {{ $showPrice($vendorDiscount) }}
                                        </p>
                                    </div>
                                    @endif

                                    <div class="cart-summary-item d-flex justify-content-between" style="padding: 1rem 0; border-bottom: 2px solid #14b8a6;">
                                        <p class="cart-summary-subtitle" style="color: #0f172a; font-weight: 700; margin: 0; font-size: 1.1rem;">
                                            @lang('Total')
                                        </p>
                                        <p class="cart-summary-price total-cart-price" style="color: #0d9488; font-weight: 800; font-size: 1.3rem; margin: 0;">
                                            {{ $showPrice($vendorTotal) }}
                                        </p>
                                    </div>

                                    <div class="cart-summary-btn" style="margin-top: 1.5rem;">
                                        {{-- زر Checkout لهذا التاجر فقط --}}
                                        @auth
                                            <a href="{{ route('front.checkout.vendor', $vendorId) }}" class="template-btn w-100" style="background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); color: #ffffff; border: none; padding: 1rem 2rem; border-radius: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 8px 20px rgba(13, 148, 136, 0.3); text-align: center; display: block; text-decoration: none;">
                                                <i class="fas fa-shopping-cart me-2"></i>@lang('Checkout This Vendor')
                                            </a>
                                        @else
                                            <a href="{{ route('user.login', ['redirect' => 'cart']) }}" class="template-btn w-100" style="background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); color: #ffffff; border: none; padding: 1rem 2rem; border-radius: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 8px 20px rgba(13, 148, 136, 0.3); text-align: center; display: block; text-decoration: none;">
                                                <i class="fas fa-shopping-cart me-2"></i>@lang('Checkout This Vendor')
                                            </a>
                                        @endauth

                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>
                    @endforeach

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
