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
                <div class="vendor-cart-section mb-5" style="background: #ffffff; border-radius: 20px; box-shadow: 0 8px 24px rgba(13, 148, 136, 0.1); border: 2px solid #e0f2fe; overflow: hidden;">
                    {{-- Vendor Header --}}
                    <div class="vendor-header" style="background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); padding: 1.5rem; color: white;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-1" style="font-weight: 800; letter-spacing: 0.5px;">
                                    <i class="fas fa-store me-2"></i>{{ $vendorData['vendor_name'] }}
                                </h4>
                                <p class="mb-0" style="opacity: 0.9; font-size: 0.95rem;">
                                    <i class="fas fa-box me-1"></i>{{ $vendorData['count'] }} @lang('Items')
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row g-0">
                        {{-- Products Table --}}
                        <div class="col-lg-8">
                            <div class="cart-table table-responsive" style="padding: 2rem;">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th scope="col">@lang('Image')</th>
                                            <th scope="col">@lang('Name')</th>
                                            <th scope="col">@lang('SKU')</th>
                                            <th scope="col">@lang('Brand')</th>
                                            <th scope="col">@lang('Quality')</th>
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
                                                         alt="" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
                                                </td>

                                                {{-- Name Column --}}
                                                <td class="cart-name">
                                                    <x-product-name :item="$product['item']" :vendor-id="$currentVendorId" :merchant-product-id="$itemMerchantId" :showSku="false" target="_blank" />
                                                    @if (!empty($product['color']) || !empty($product['size']))
                                                        <div class="d-flex align-items-center gap-2 mt-2">
                                                            @if (!empty($product['color']))
                                                                <span class="text-muted small">@lang('Color'): </span>
                                                                <span class="cart-color d-inline-block rounded-2" style="border:10px solid #{{ $product['color']==''?'white':$product['color'] }};"></span>
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
                            <div class="cart-summary" style="margin: 2rem; background: linear-gradient(135deg, #ffffff 0%, #f0fdfa 100%); border-radius: 16px; padding: 2rem; border: 2px solid #14b8a6;">
                                <h5 class="cart-summary-title" style="color: #0f172a; font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 3px solid; border-image: linear-gradient(90deg, #0d9488 0%, #14b8a6 50%, #2dd4bf 100%) 1;">
                                    @lang('Cart Summary')
                                </h5>
                                <div class="cart-summary-content">
                                    @php
                                        // Calculate discount for THIS vendor only (not global)
                                        $vendorDiscount = 0;
                                        foreach ($vendorData['products'] as $product) {
                                            if (!empty($product['discount'])) {
                                                $total_itemprice = (float)($product['item_price'] ?? 0) * (int)($product['qty'] ?? 1);
                                                $tdiscount = ($total_itemprice * (float)$product['discount']) / 100;
                                                $vendorDiscount += $tdiscount;
                                            }
                                        }
                                        $vendorSubtotal = $vendorData['total'] + $vendorDiscount;
                                    @endphp

                                    <div class="cart-summary-item d-flex justify-content-between" style="padding: 1rem 0; border-bottom: 1px solid #e0f2fe;">
                                        <p class="cart-summary-subtitle" style="color: #64748b; font-weight: 600; margin: 0;">
                                            @lang('Subtotal') ({{ $vendorData['count'] }} @lang('Items'))
                                        </p>
                                        <p class="cart-summary-price" style="color: #0d9488; font-weight: 700; font-size: 1.1rem; margin: 0;">
                                            {{ Product::convertPrice($vendorSubtotal) }}
                                        </p>
                                    </div>

                                    @if($vendorDiscount > 0)
                                    <div class="cart-summary-item d-flex justify-content-between" style="padding: 1rem 0; border-bottom: 1px solid #e0f2fe;">
                                        <p class="cart-summary-subtitle" style="color: #64748b; font-weight: 600; margin: 0;">
                                            @lang('Discount')
                                        </p>
                                        <p class="cart-summary-price" style="color: #ef4444; font-weight: 700; font-size: 1.1rem; margin: 0;">
                                            - {{ Product::convertPrice($vendorDiscount) }}
                                        </p>
                                    </div>
                                    @endif

                                    <div class="cart-summary-item d-flex justify-content-between" style="padding: 1rem 0; border-bottom: 2px solid #14b8a6;">
                                        <p class="cart-summary-subtitle" style="color: #0f172a; font-weight: 700; margin: 0; font-size: 1.1rem;">
                                            @lang('Total')
                                        </p>
                                        <p class="cart-summary-price total-cart-price" style="color: #0d9488; font-weight: 800; font-size: 1.3rem; margin: 0;">
                                            {{ Product::convertPrice($vendorData['total']) }}
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
