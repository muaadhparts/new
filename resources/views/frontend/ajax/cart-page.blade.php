{{--
    ====================================================================
    MULTI-VENDOR CART SYSTEM
    ====================================================================

    This view implements a per-vendor cart display system where:

    1. Each vendor has their own independent cart section
    2. Each vendor section contains:
       - Vendor header with vendor name/ID
       - Products table (only this vendor's products)
       - Independent Cart Summary (subtotal, discount, total)
       - Dedicated "Checkout This Vendor" button

    3. NO GLOBAL SUMMARY exists - all calculations are per-vendor
    4. Each checkout processes ONLY one vendor at a time
    5. After order completion, only that vendor's products are removed

    Key Variables:
    - $productsByVendor: Array grouped by vendor_id
    - $vendorData: Contains vendor_id, vendor_name, products, total, count

    Flow:
    Cart Page → Checkout Vendor {id} → Step1 → Step2 → Step3 → Order Creation

    Updated: 2025-12-02 - Added SKU, Brand, Quality columns
    ====================================================================
--}}

@php
    use App\Models\Product;
    use App\Models\Brand;
    use App\Models\MerchantProduct;
    use App\Helpers\CartHelper;
    use Illuminate\Support\Facades\Storage;

    // التحقق من وجود سلة في النظام الجديد (v2)
    $hasV2Cart = CartHelper::hasCart();
@endphp

{{-- إذا كانت هناك سلة في النظام الجديد، استخدمها --}}
@if ($hasV2Cart)
    @include('frontend.ajax.cart-page-v2')
@else
<div class="container gs-cart-container">
    <div class="row gs-cart-row">

        @if (Session::has('cart') && isset($productsByVendor) && !empty($productsByVendor))

            <div class="col-lg-12">
                {{-- Loop through each vendor section independently --}}
                @foreach($productsByVendor as $vendorId => $vendorData)
                <div class="vendor-cart-section muaadh-vendor-cart-section mb-5">
                    {{-- Vendor Header - Styles moved to MUAADH.css Section 39 --}}
                    <div class="vendor-header muaadh-vendor-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-1 muaadh-vendor-title">
                                    <i class="fas fa-store me-2"></i>{{ $vendorData['vendor_name'] }}
                                </h4>
                                <p class="mb-0 muaadh-vendor-subtitle">
                                    <i class="fas fa-box me-1"></i>{{ $vendorData['count'] }} @lang('Items')
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row g-0">
                        {{-- Products Table --}}
                        <div class="col-lg-8">
                            <div class="cart-table table-responsive muaadh-cart-table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th scope="col">@lang('Image')</th>
                                            <th scope="col">@lang('Name')</th>
                                            <th scope="col">@lang('SKU')</th>
                                            <th scope="col">@lang('Brand')</th>
                                            <th scope="col">@lang('Quality')</th>
                                            <th scope="col">@lang('Weight')</th>
                                            <th scope="col">@lang('Price')</th>
                                            <th scope="col">@lang('Quantity')</th>
                                            <th scope="col">@lang('Subtotal')</th>
                                            <th scope="col">@lang('Action')</th>
                                        </tr>
                                    </thead>

                                    <tbody class="t_body">
                                        @foreach ($vendorData['products'] as $rowKey => $product)
                                            @php
                                                // معلومات أساسية
                                                $currentVendorId = data_get($product, 'item.user_id') ?? data_get($product, 'user_id') ?? 0;
                                                $slug     = data_get($product, 'item.slug');
                                                $name     = data_get($product, 'item.name');
                                                $sku      = data_get($product, 'item.sku');
                                                $photo    = data_get($product, 'item.photo');

                                                // المفتاح الحقيقي للسلة كما هو (Vendor-aware)
                                                $row    = (string) $rowKey;
                                                // نسخة آمنة للـ DOM
                                                $domKey = str_replace([':', '#', '.', ' ', '/', '\\'], '_', $row);

                                                // Fetch merchant data
                                                $mpId = $product['merchant_product_id'] ?? null;
                                                $itemProduct = \App\Models\Product::where('slug', $slug)->first();

                                                // جلب MerchantProduct
                                                $itemMerchant = null;
                                                if ($mpId) {
                                                    $itemMerchant = MerchantProduct::with(['qualityBrand'])->find($mpId);
                                                } elseif ($itemProduct && $currentVendorId) {
                                                    $itemMerchant = $itemProduct->getMerchantProduct($currentVendorId);
                                                }
                                                $itemMerchantId = $itemMerchant->id ?? null;

                                                // جلب Brand
                                                $brand = null;
                                                $brandId = data_get($product, 'item.brand_id');
                                                if ($brandId) {
                                                    $brand = Brand::find($brandId);
                                                } elseif ($itemProduct && $itemProduct->brand) {
                                                    $brand = $itemProduct->brand;
                                                }

                                                // Quality Brand
                                                $qualityBrand = $itemMerchant?->qualityBrand;

                                                // Stock & Minimum Qty
                                                $stock = (int)($product['stock'] ?? $itemMerchant->stock ?? 999);
                                                if (!empty($product['size_qty'])) {
                                                    $stock = (int)$product['size_qty'];
                                                }
                                                $minQty = (int)(data_get($product, 'item.minimum_qty') ?? $itemMerchant->minimum_qty ?? 1);
                                                if ($minQty < 1) $minQty = 1;

                                                $preordered = $product['preordered'] ?? $itemMerchant->preordered ?? 0;

                                                // رابط تفاصيل المنتج
                                                $productUrl = ($currentVendorId && $itemMerchantId)
                                                    ? route('front.product', ['slug' => $slug, 'vendor_id' => $currentVendorId, 'merchant_product_id' => $itemMerchantId])
                                                    : 'javascript:;';
                                            @endphp

                                            <tr>
                                                {{-- Image Column --}}
                                                <td class="cart-image">
                                                    <img src="{{ $photo ? Storage::url($photo) : asset('assets/images/noimage.png') }}"
                                                         alt="" class="muaadh-cart-product-img">
                                                </td>

                                                {{-- Name Column --}}
                                                <td class="cart-name">
                                                    <x-product-name :item="$product['item']" :vendor-id="$currentVendorId" :merchant-product-id="$itemMerchantId" :showSku="false" target="_blank" />
                                                    @if (!empty($product['color']) || !empty($product['size']))
                                                        <div class="d-flex align-items-center gap-2 mt-2">
                                                            @if (!empty($product['color']))
                                                                <span class="text-muted small">@lang('Color'): </span>
                                                                <span class="cart-color muaadh-cart-color-swatch" style="--swatch-color: #{{ $product['color']==''?'white':$product['color'] }};"></span>
                                                            @endif
                                                            @if (!empty($product['size']))
                                                                <span class="text-muted small">@lang('Size'): {{ $product['size'] }}</span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </td>

                                                {{-- SKU Column --}}
                                                <td class="cart-sku">
                                                    <code class="small fw-bold">{{ $sku ?? '-' }}</code>
                                                </td>

                                                {{-- Brand Column --}}
                                                <td class="cart-brand">
                                                    <span>{{ $brand ? Str::ucfirst(getLocalizedBrandName($brand)) : '-' }}</span>
                                                </td>

                                                {{-- Quality Column --}}
                                                <td class="cart-quality">
                                                    <span>{{ $qualityBrand ? getLocalizedQualityName($qualityBrand) : '-' }}</span>
                                                </td>

                                                {{-- Weight Column - من VendorCartService بدون fallback --}}
                                                <td class="cart-weight">
                                                    @php
                                                        // جلب الوزن من بيانات المنتج المحسوبة
                                                        $rowWeight = $product['row_weight'] ?? null;
                                                        $dimensions = $product['dimensions'] ?? null;
                                                        $unitWeight = $dimensions['weight'] ?? null;
                                                    @endphp
                                                    @if ($rowWeight !== null)
                                                        <span class="fw-bold">{{ number_format($rowWeight, 2) }} kg</span>
                                                        @if ($unitWeight && (int)$product['qty'] > 1)
                                                            <br><small class="text-muted">({{ number_format($unitWeight, 2) }} × {{ $product['qty'] }})</small>
                                                        @endif
                                                    @elseif ($dimensions && isset($dimensions['is_complete']) && !$dimensions['is_complete'])
                                                        <span class="text-warning" title="{{ implode(', ', $dimensions['missing_fields'] ?? []) }}">
                                                            <i class="fas fa-exclamation-triangle"></i> @lang('Incomplete')
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>

                                                {{-- Price Column --}}
                                                <td class="cart-price">
                                                    {{ Product::convertPrice($product['item_price']) }}
                                                </td>

                                                {{-- Quantity Column --}}
                                                @if (data_get($product,'item.type') == 'Physical')
                                                    <td>
                                                        <div class="cart-quantity">
                                                            <button class="cart-quantity-btn quantity-down"
                                                                    data-min-qty="{{ $minQty }}">-</button>

                                                            <input type="text" id="qty{{ $domKey }}" value="{{ $product['qty'] }}"
                                                                   class="borderless" readonly>

                                                            {{-- مفاتيح الطلب --}}
                                                            <input type="hidden" class="prodid"   value="{{ data_get($product,'item.id') }}">
                                                            <input type="hidden" class="itemid"   value="{{ $row }}">
                                                            <input type="hidden" class="domkey"   value="{{ $domKey }}">
                                                            <input type="hidden" class="size_qty" value="{{ $product['size_qty'] ?? '' }}">
                                                            <input type="hidden" class="size_price" value="{{ $product['size_price'] ?? 0 }}">
                                                            <input type="hidden" class="minimum_qty" value="{{ $minQty }}">
                                                            <input type="hidden" class="stock_val" value="{{ $stock }}">
                                                            <input type="hidden" class="preordered_val" value="{{ $preordered }}">

                                                            <button class="cart-quantity-btn quantity-up"
                                                                    data-stock="{{ $stock }}"
                                                                    data-preordered="{{ $preordered }}">+</button>
                                                        </div>
                                                    </td>
                                                @else
                                                    <td class="product-quantity">1</td>
                                                @endif

                                                {{-- مخزون الصف --}}
                                                @if (!empty($product['size_qty']))
                                                    <input type="hidden" id="stock{{ $domKey }}" value="{{ $product['size_qty'] }}">
                                                @elseif (data_get($product,'item.type') != 'Physical')
                                                    <input type="hidden" id="stock{{ $domKey }}" value="1">
                                                @else
                                                    <input type="hidden" id="stock{{ $domKey }}" value="{{ $stock }}">
                                                @endif

                                                {{-- Subtotal Column --}}
                                                <td class="cart-price" id="prc{{ $domKey }}">
                                                    {{ Product::convertPrice($product['price']) }}
                                                    @if (!empty($product['discount']))
                                                        <br><small class="text-success">{{ $product['discount'] }}% @lang('off')</small>
                                                    @endif
                                                </td>

                                                {{-- Remove Button --}}
                                                <td>
                                                    <a class="cart-remove-btn btn btn-sm btn-outline-danger"
                                                       data-class="cremove{{ $domKey }}"
                                                       href="{{ route('product.cart.remove', $row) }}"
                                                       title="@lang('Remove')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Vendor Cart Summary - INDEPENDENT per vendor --}}
                        <div class="col-lg-4">
                            <div class="cart-summary muaadh-cart-summary">
                                <h5 class="cart-summary-title muaadh-cart-summary-title">
                                    @lang('Cart Summary')
                                </h5>
                                <div class="cart-summary-content">
                                    @php
                                        // Calculate discount for THIS vendor only (not global)
                                        $vendorDiscount = 0;
                                        $vendorTotalWeight = 0;
                                        $hasMissingWeight = false;
                                        foreach ($vendorData['products'] as $product) {
                                            if (!empty($product['discount'])) {
                                                $total_itemprice = (float)($product['item_price'] ?? 0) * (int)($product['qty'] ?? 1);
                                                $tdiscount = ($total_itemprice * (float)$product['discount']) / 100;
                                                $vendorDiscount += $tdiscount;
                                            }
                                            // حساب الوزن الإجمالي
                                            if (isset($product['row_weight']) && $product['row_weight'] !== null) {
                                                $vendorTotalWeight += (float)$product['row_weight'];
                                            } else {
                                                $hasMissingWeight = true;
                                            }
                                        }
                                        $vendorSubtotal = $vendorData['total'] + $vendorDiscount;

                                        // بيانات الشحن من VendorCartService
                                        $shippingData = $vendorData['shipping_data'] ?? null;
                                        $hasCompleteShippingData = $vendorData['has_complete_data'] ?? false;
                                    @endphp

                                    <div class="cart-summary-item muaadh-cart-summary-item d-flex justify-content-between">
                                        <p class="cart-summary-subtitle text-muted fw-semibold mb-0">
                                            @lang('Subtotal') ({{ $vendorData['count'] }} @lang('Items'))
                                        </p>
                                        <p class="cart-summary-price text-primary fw-bold mb-0">
                                            {{ Product::convertPrice($vendorSubtotal) }}
                                        </p>
                                    </div>

                                    {{-- Total Weight Row --}}
                                    <div class="cart-summary-item muaadh-cart-summary-item d-flex justify-content-between">
                                        <p class="cart-summary-subtitle text-muted fw-semibold mb-0">
                                            <i class="fas fa-weight-hanging me-1"></i> @lang('Total Weight')
                                        </p>
                                        <p class="cart-summary-price fw-semibold mb-0">
                                            @if ($vendorTotalWeight > 0)
                                                {{ number_format($vendorTotalWeight, 2) }} kg
                                                @if ($hasMissingWeight)
                                                    <i class="fas fa-exclamation-triangle text-warning ms-1" title="@lang('Some products are missing weight data')"></i>
                                                @endif
                                            @elseif ($hasMissingWeight)
                                                <span class="text-warning">@lang('Incomplete')</span>
                                            @else
                                                -
                                            @endif
                                        </p>
                                    </div>

                                    @if($vendorDiscount > 0)
                                    <div class="cart-summary-item muaadh-cart-summary-item d-flex justify-content-between">
                                        <p class="cart-summary-subtitle text-muted fw-semibold mb-0">
                                            @lang('Discount')
                                        </p>
                                        <p class="cart-summary-price text-danger fw-bold mb-0">
                                            - {{ Product::convertPrice($vendorDiscount) }}
                                        </p>
                                    </div>
                                    @endif

                                    <div class="cart-summary-item muaadh-cart-summary-total d-flex justify-content-between">
                                        <p class="cart-summary-subtitle muaadh-cart-summary-total-label mb-0">
                                            @lang('Total')
                                        </p>
                                        <p class="cart-summary-price total-cart-price muaadh-cart-summary-total-value mb-0">
                                            {{ Product::convertPrice($vendorData['total']) }}
                                        </p>
                                    </div>

                                    <div class="cart-summary-btn">
                                        {{-- زر Checkout لهذا التاجر فقط --}}
                                        @auth
                                            <a href="{{ route('front.checkout.vendor', $vendorId) }}" class="template-btn muaadh-checkout-btn">
                                                <i class="fas fa-shopping-cart"></i>@lang('Checkout This Vendor')
                                            </a>
                                        @else
                                            <a href="{{ route('user.login', ['redirect' => 'cart']) }}" class="template-btn muaadh-checkout-btn">
                                                <i class="fas fa-shopping-cart"></i>@lang('Checkout This Vendor')
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

        @else
            {{-- سلة فارغة --}}
            <div class="col-xl-12 col-lg-12 col-md-12 col-12">
                <div class="card border py-4">
                    <div class="card-body text-center">
                        <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                        <h4>@lang('Your cart is empty')</h4>
                        <p class="text-muted">@lang('Add some products to your cart')</p>
                        <a href="{{ route('front.categories') }}" class="btn btn-primary mt-3">
                            @lang('Start Shopping')
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- JavaScript للتحكم بالكمية --}}
<script>
$(document).ready(function() {
    // زيادة الكمية
    $(document).on('click', '.quantity-up', function() {
        var $btn = $(this);
        var $wrapper = $btn.closest('.cart-quantity');
        var $qtyInput = $wrapper.find('input[type="text"]');
        var domKey = $wrapper.find('.domkey').val();
        var stock = parseInt($btn.data('stock')) || 999;
        var preordered = parseInt($btn.data('preordered')) || 0;
        var currentQty = parseInt($qtyInput.val()) || 1;

        // التحقق من المخزون
        if (stock > 0 && currentQty >= stock && preordered == 0) {
            if (typeof toastr !== 'undefined') {
                toastr.warning('{{ __("Stock limit reached") }}: ' + stock);
            }
            return;
        }

        var prodId = $wrapper.find('.prodid').val();
        var itemId = $wrapper.find('.itemid').val();
        var sizeQty = $wrapper.find('.size_qty').val() || '';
        var sizePrice = $wrapper.find('.size_price').val() || 0;

        $.ajax({
            url: '/addbyone',
            type: 'GET',
            dataType: 'json',
            data: {
                id: prodId,
                itemid: itemId,
                size_qty: sizeQty,
                size_price: sizePrice
            },
            success: function(resp) {
                if (resp === 0 || resp === '0') {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('{{ __("Cannot increase quantity") }}');
                    }
                    return;
                }
                $qtyInput.val(resp[1]);
                $('#prc' + domKey).html(resp[2]);
                $('.total-cart-price').html(resp[0]);
                if (typeof toastr !== 'undefined') {
                    toastr.success('{{ __("Quantity updated") }}');
                }
            },
            error: function() {
                if (typeof toastr !== 'undefined') {
                    toastr.error('{{ __("Error occurred") }}');
                }
            }
        });
    });

    // إنقاص الكمية
    $(document).on('click', '.quantity-down', function() {
        var $btn = $(this);
        var $wrapper = $btn.closest('.cart-quantity');
        var $qtyInput = $wrapper.find('input[type="text"]');
        var domKey = $wrapper.find('.domkey').val();
        var minQty = parseInt($wrapper.find('.minimum_qty').val()) || 1;
        var currentQty = parseInt($qtyInput.val()) || 1;

        // التحقق من الحد الأدنى
        if (currentQty <= minQty) {
            if (typeof toastr !== 'undefined') {
                toastr.warning('{{ __("Minimum quantity is") }} ' + minQty);
            }
            return;
        }

        var prodId = $wrapper.find('.prodid').val();
        var itemId = $wrapper.find('.itemid').val();
        var sizeQty = $wrapper.find('.size_qty').val() || '';
        var sizePrice = $wrapper.find('.size_price').val() || 0;

        $.ajax({
            url: '/reducebyone',
            type: 'GET',
            dataType: 'json',
            data: {
                id: prodId,
                itemid: itemId,
                size_qty: sizeQty,
                size_price: sizePrice
            },
            success: function(resp) {
                if (resp === 0 || resp === '0') {
                    if (typeof toastr !== 'undefined') {
                        toastr.warning('{{ __("Cannot decrease quantity") }}');
                    }
                    return;
                }
                $qtyInput.val(resp[1]);
                $('#prc' + domKey).html(resp[2]);
                $('.total-cart-price').html(resp[0]);
                if (typeof toastr !== 'undefined') {
                    toastr.success('{{ __("Quantity updated") }}');
                }
            },
            error: function() {
                if (typeof toastr !== 'undefined') {
                    toastr.error('{{ __("Error occurred") }}');
                }
            }
        });
    });
});
</script>
@endif {{-- نهاية @else للتحقق من النظام الجديد --}}
