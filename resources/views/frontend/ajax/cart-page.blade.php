@php
    use App\Models\Product;
    use App\Models\Brand;
    use App\Models\MerchantProduct;
    use App\Helpers\CartHelper;
    use Illuminate\Support\Facades\Storage;

    $discount = 0;

    // التحقق من وجود سلة في النظام الجديد (v2)
    $hasV2Cart = CartHelper::hasCart();
@endphp

{{-- إذا كانت هناك سلة في النظام الجديد، استخدمها --}}
@if ($hasV2Cart)
    @include('frontend.ajax.cart-page-v2')
@else
<div class="container gs-cart-container">
    <div class="row gs-cart-row">

        @if (Session::has('cart') && !empty($products))

            <div class="col-lg-8">
                @if(isset($productsByVendor) && !empty($productsByVendor))
                    {{-- عرض سلة منفصلة لكل تاجر --}}
                    @foreach($productsByVendor as $vendorId => $vendorData)
                        <div class="vendor-cart-section mb-4" style="border: 2px solid #e0e0e0; border-radius: 8px; overflow: hidden;">
                            {{-- رأس التاجر --}}
                            <div class="vendor-header p-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                <h5 class="mb-1" style="color: white; font-weight: 600;">
                                    <i class="fas fa-store me-2"></i>
                                    {{ $vendorData['vendor_name'] }}
                                </h5>
                                <small style="color: rgba(255,255,255,0.9);">
                                    {{ $vendorData['count'] }} @lang('Items') •
                                    @lang('Total'): {{ Product::convertPrice($vendorData['total']) }}
                                </small>
                            </div>

                            {{-- جدول المنتجات --}}
                            <div class="cart-table table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>@lang('Product')</th>
                                            <th>@lang('SKU')</th>
                                            <th>@lang('Brand')</th>
                                            <th>@lang('Quality')</th>
                                            <th>@lang('Price')</th>
                                            <th>@lang('Quantity')</th>
                                            <th>@lang('Subtotal')</th>
                                            <th>@lang('Action')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($vendorData['products'] as $cartKey => $product)
                                            @php
                                                // استخراج البيانات من السلة
                                                $item = $product['item'];
                                                $mpId = $product['merchant_product_id'] ?? 0;
                                                $userId = $product['user_id'] ?? 0;

                                                // جلب MerchantProduct مع العلاقات
                                                $mp = $mpId ? MerchantProduct::with(['qualityBrand', 'product'])->find($mpId) : null;

                                                // جلب Brand
                                                $brand = null;
                                                $brandId = $item['brand_id'] ?? null;
                                                if ($brandId) {
                                                    $brand = Brand::find($brandId);
                                                }

                                                // Quality Brand
                                                $qualityBrand = $mp?->qualityBrand;

                                                // الحد الأدنى للكمية
                                                $minQty = (int)($product['minimum_qty'] ?? $mp->minimum_qty ?? 1);
                                                if ($minQty < 1) $minQty = 1;

                                                // المخزون
                                                $stock = (int)($product['stock'] ?? $mp->stock ?? 999);
                                                if (!empty($product['size_qty'])) {
                                                    $stock = (int)$product['size_qty'];
                                                }

                                                // Preorder
                                                $preordered = $product['preordered'] ?? $mp->preordered ?? 0;

                                                // حساب الخصم
                                                if (($product['discount'] ?? 0) != 0) {
                                                    $totalItemPrice = $product['item_price'] * $product['qty'];
                                                    $discount += ($totalItemPrice * $product['discount']) / 100;
                                                }

                                                // رابط المنتج
                                                $productUrl = '#';
                                                if (isset($item['slug'])) {
                                                    if ($mpId && $userId) {
                                                        $productUrl = route('front.product', [
                                                            'slug' => $item['slug'],
                                                            'vendor_id' => $userId,
                                                            'merchant_product_id' => $mpId
                                                        ]);
                                                    } else {
                                                        $productUrl = route('front.product.legacy', $item['slug']);
                                                    }
                                                }
                                            @endphp

                                            <tr class="cart-item-row" id="cart-row-{{ $loop->index }}">
                                                {{-- اسم المنتج والصورة --}}
                                                <td class="cart-product-area">
                                                    <div class="cart-product d-flex align-items-center">
                                                        <img src="{{ !empty($item['photo']) ? Storage::url($item['photo']) : asset('assets/images/noimage.png') }}"
                                                             alt="{{ $item['name'] ?? '' }}"
                                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                                        <div class="cart-product-info ms-3">
                                                            <a class="cart-title d-block fw-bold" href="{{ $productUrl }}">
                                                                {{ getLocalizedProductName((object)$item, 40) }}
                                                            </a>
                                                            @if (!empty($product['size']) || !empty($product['color']))
                                                                <small class="text-muted">
                                                                    @if (!empty($product['size']))
                                                                        <span class="badge bg-secondary">{{ $product['size'] }}</span>
                                                                    @endif
                                                                    @if (!empty($product['color']))
                                                                        <span style="display:inline-block; width:14px; height:14px; background:#{{ $product['color'] }}; border-radius:50%; border:1px solid #ccc; vertical-align:middle;"></span>
                                                                    @endif
                                                                </small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>

                                                {{-- SKU --}}
                                                <td class="cart-sku align-middle">
                                                    <code class="small">{{ $item['sku'] ?? 'N/A' }}</code>
                                                </td>

                                                {{-- البراند --}}
                                                <td class="cart-brand align-middle">
                                                    <small>{{ $brand ? getLocalizedBrandName($brand) : 'N/A' }}</small>
                                                </td>

                                                {{-- الجودة --}}
                                                <td class="cart-quality align-middle">
                                                    <small>{{ $qualityBrand ? getLocalizedQualityName($qualityBrand) : 'N/A' }}</small>
                                                </td>

                                                {{-- السعر --}}
                                                <td class="cart-price align-middle">
                                                    {{ Product::convertPrice($product['item_price']) }}
                                                </td>

                                                {{-- الكمية --}}
                                                <td class="align-middle">
                                                    @if (($item['type'] ?? 'Physical') == 'Physical')
                                                        <div class="add-qty-wrapper cart-qty-wrapper" style="display: inline-flex; align-items: center;">
                                                            <div class="product-input-wrapper" style="display: flex; align-items: center; border: 1px solid #ddd; border-radius: 4px;">
                                                                {{-- زر الإنقاص --}}
                                                                <button type="button" class="action-btn cart-qtminus"
                                                                        data-cart-key="{{ $cartKey }}"
                                                                        data-min-qty="{{ $minQty }}"
                                                                        style="width: 32px; height: 32px; border: none; background: #f5f5f5; cursor: pointer; font-size: 16px;">
                                                                    -
                                                                </button>

                                                                {{-- حقل الكمية --}}
                                                                <input type="text"
                                                                       class="qty-input cart-qty-input"
                                                                       id="cart-qty-{{ $loop->index }}"
                                                                       data-cart-key="{{ $cartKey }}"
                                                                       value="{{ $product['qty'] }}"
                                                                       readonly
                                                                       style="width: 40px; text-align: center; border: none; font-weight: bold;">

                                                                {{-- زر الزيادة --}}
                                                                <button type="button" class="action-btn cart-qtplus"
                                                                        data-cart-key="{{ $cartKey }}"
                                                                        data-stock="{{ $stock }}"
                                                                        data-preordered="{{ $preordered }}"
                                                                        style="width: 32px; height: 32px; border: none; background: #f5f5f5; cursor: pointer; font-size: 16px;">
                                                                    +
                                                                </button>
                                                            </div>

                                                            {{-- Hidden inputs --}}
                                                            <input type="hidden" class="cart-item-id" value="{{ is_object($item) ? $item->id : ($item['id'] ?? 0) }}">
                                                            <input type="hidden" class="cart-item-key" value="{{ $cartKey }}">
                                                            <input type="hidden" class="cart-size-qty" value="{{ $product['size_qty'] ?? '' }}">
                                                            <input type="hidden" class="cart-size-price" value="{{ $product['size_price'] ?? 0 }}">
                                                            <input type="hidden" class="cart-stock" value="{{ $stock }}">
                                                            <input type="hidden" class="cart-min-qty" value="{{ $minQty }}">
                                                            <input type="hidden" class="cart-preordered" value="{{ $preordered }}">
                                                        </div>
                                                    @else
                                                        <span class="text-center d-block">1</span>
                                                    @endif
                                                </td>

                                                {{-- المجموع الفرعي --}}
                                                <td class="cart-subtotal align-middle" id="cart-price-{{ $loop->index }}">
                                                    <strong>{{ Product::convertPrice($product['price']) }}</strong>
                                                    @if (($product['discount'] ?? 0) != 0)
                                                        <br><small class="text-success">{{ $product['discount'] }}% @lang('off')</small>
                                                    @endif
                                                </td>

                                                {{-- الحذف --}}
                                                <td class="align-middle">
                                                    <a class="cart-remove-btn btn btn-sm btn-outline-danger"
                                                       href="{{ route('product.cart.remove', $cartKey) }}"
                                                       title="@lang('Remove')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- تم إزالة زر الدفع المنفصل لكل تاجر - يوجد checkout موحد في الأسفل --}}
                        </div>
                    @endforeach
                @else
                    {{-- Fallback: عرض بسيط بدون تجميع حسب التاجر --}}
                    <div class="cart-table table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>@lang('Product')</th>
                                    <th>@lang('Price')</th>
                                    <th>@lang('Quantity')</th>
                                    <th>@lang('Subtotal')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($products as $cartKey => $product)
                                    @php
                                        $item = $product['item'];
                                        $minQty = (int)($product['minimum_qty'] ?? 1);
                                        if ($minQty < 1) $minQty = 1;
                                        $stock = (int)($product['stock'] ?? 999);
                                    @endphp
                                    <tr>
                                        <td>{{ getLocalizedProductName((object)$item, 30) }}</td>
                                        <td>{{ Product::convertPrice($product['item_price']) }}</td>
                                        <td>
                                            <div class="add-qty-wrapper cart-qty-wrapper" style="display: inline-flex;">
                                                <div class="product-input-wrapper" style="display: flex; border: 1px solid #ddd; border-radius: 4px;">
                                                    <button type="button" class="action-btn cart-qtminus"
                                                            data-cart-key="{{ $cartKey }}"
                                                            data-min-qty="{{ $minQty }}"
                                                            style="width: 32px; height: 32px; border: none; background: #f5f5f5;">-</button>
                                                    <input type="text" class="qty-input cart-qty-input"
                                                           id="cart-qty-fallback-{{ $loop->index }}"
                                                           data-cart-key="{{ $cartKey }}"
                                                           value="{{ $product['qty'] }}" readonly
                                                           style="width: 40px; text-align: center; border: none;">
                                                    <button type="button" class="action-btn cart-qtplus"
                                                            data-cart-key="{{ $cartKey }}"
                                                            data-stock="{{ $stock }}"
                                                            style="width: 32px; height: 32px; border: none; background: #f5f5f5;">+</button>
                                                </div>
                                                <input type="hidden" class="cart-item-id" value="{{ is_object($item) ? $item->id : ($item['id'] ?? 0) }}">
                                                <input type="hidden" class="cart-item-key" value="{{ $cartKey }}">
                                                <input type="hidden" class="cart-size-qty" value="{{ $product['size_qty'] ?? '' }}">
                                                <input type="hidden" class="cart-size-price" value="{{ $product['size_price'] ?? 0 }}">
                                                <input type="hidden" class="cart-stock" value="{{ $stock }}">
                                                <input type="hidden" class="cart-min-qty" value="{{ $minQty }}">
                                            </div>
                                        </td>
                                        <td id="cart-price-fallback-{{ $loop->index }}">{{ Product::convertPrice($product['price']) }}</td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-danger" href="{{ route('product.cart.remove', $cartKey) }}">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- ملخص السلة --}}
            <div class="col-lg-4">
                <div class="cart-summary" style="background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px;">
                    <h4 class="cart-summary-title mb-4" style="font-weight: 600; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                        @lang('Cart Summary')
                    </h4>

                    <div class="cart-summary-content">
                        <div class="d-flex justify-content-between mb-2">
                            <span>@lang('Subtotal') ({{ count($products) }} @lang('Items'))</span>
                            <span>{{ Product::convertPrice($totalPrice + $discount) }}</span>
                        </div>

                        @if($discount > 0)
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span>@lang('Discount')</span>
                                <span>-{{ Product::convertPrice($discount) }}</span>
                            </div>
                        @endif

                        <hr>

                        <div class="d-flex justify-content-between mb-3">
                            <strong>@lang('Total')</strong>
                            <strong class="total-cart-price" style="font-size: 1.25rem; color: #667eea;">
                                {{ Product::convertPrice($mainTotal) }}
                            </strong>
                        </div>

                        @if(isset($productsByVendor) && count($productsByVendor) > 1)
                            <div class="alert alert-info small">
                                <i class="fas fa-info-circle me-1"></i>
                                @lang('Your cart contains items from') {{ count($productsByVendor) }} @lang('different vendors.')
                            </div>
                        @endif

                        <a href="{{ route('front.checkout') }}" class="btn btn-primary w-100 btn-lg mt-3">
                            <i class="fas fa-lock me-2"></i>
                            @lang('Proceed to Checkout')
                        </a>

                        <a href="{{ route('front.categories') }}" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="fas fa-arrow-left me-2"></i>
                            @lang('Continue Shopping')
                        </a>
                    </div>
                </div>
            </div>

        @else
            <div class="col-12">
                <div class="card py-5">
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

{{-- JavaScript للتحكم بالكمية - نفس منطق product.blade.php --}}
<script>
$(document).ready(function() {

    // زيادة الكمية - نفس منطق .qtplus في product.blade.php
    $(document).on('click', '.cart-qtplus', function() {
        var $btn = $(this);
        var $wrapper = $btn.closest('.cart-qty-wrapper');
        var $qtyInput = $wrapper.find('.cart-qty-input');
        var cartKey = $btn.data('cart-key');
        var stock = parseInt($btn.data('stock')) || 999;
        var preordered = parseInt($btn.data('preordered')) || 0;
        var currentQty = parseInt($qtyInput.val()) || 1;

        // نفس منطق product.blade.php
        // التحقق من المخزون قبل الزيادة
        if (stock > 0 && currentQty >= stock && preordered == 0) {
            toastr.warning('{{ __("Stock limit reached") }}: ' + stock);
            return;
        }

        // إرسال AJAX
        var prodId = $wrapper.find('.cart-item-id').val();
        var sizeQty = $wrapper.find('.cart-size-qty').val() || '';
        var sizePrice = $wrapper.find('.cart-size-price').val() || 0;

        // IMPORTANT: استخدام data object بدلاً من URL string للتعامل الصحيح مع الأحرف الخاصة
        $.ajax({
            url: '/addbyone',
            type: 'GET',
            dataType: 'json',
            data: {
                id: prodId,
                itemid: cartKey,
                size_qty: sizeQty,
                size_price: sizePrice
            },
            success: function(resp) {
                if (resp === 0 || resp === '0') {
                    toastr.error('{{ __("Cannot increase quantity") }}');
                    return;
                }
                // تحديث الكمية والسعر
                $qtyInput.val(resp[1]);
                var $priceCell = $wrapper.closest('tr').find('.cart-subtotal');
                $priceCell.find('strong').first().html(resp[2]);
                $('.total-cart-price').html(resp[0]);
                toastr.success('{{ __("Quantity updated") }}');
            },
            error: function() {
                toastr.error('{{ __("Error occurred") }}');
            }
        });
    });

    // إنقاص الكمية - نفس منطق .qtminus في product.blade.php
    $(document).on('click', '.cart-qtminus', function() {
        var $btn = $(this);
        var $wrapper = $btn.closest('.cart-qty-wrapper');
        var $qtyInput = $wrapper.find('.cart-qty-input');
        var cartKey = $btn.data('cart-key');
        var minQty = parseInt($btn.data('min-qty')) || 1;
        var currentQty = parseInt($qtyInput.val()) || 1;

        // نفس منطق product.blade.php
        // التحقق من الحد الأدنى للكمية قبل التنقيص
        if (currentQty <= minQty) {
            toastr.warning('{{ __("Minimum quantity is") }} ' + minQty);
            return;
        }

        // إرسال AJAX
        var prodId = $wrapper.find('.cart-item-id').val();
        var sizeQty = $wrapper.find('.cart-size-qty').val() || '';
        var sizePrice = $wrapper.find('.cart-size-price').val() || 0;

        // IMPORTANT: استخدام data object بدلاً من URL string للتعامل الصحيح مع الأحرف الخاصة
        $.ajax({
            url: '/reducebyone',
            type: 'GET',
            dataType: 'json',
            data: {
                id: prodId,
                itemid: cartKey,
                size_qty: sizeQty,
                size_price: sizePrice
            },
            success: function(resp) {
                if (resp === 0 || resp === '0') {
                    toastr.warning('{{ __("Cannot decrease quantity") }}');
                    return;
                }
                // تحديث الكمية والسعر
                $qtyInput.val(resp[1]);
                var $priceCell = $wrapper.closest('tr').find('.cart-subtotal');
                $priceCell.find('strong').first().html(resp[2]);
                $('.total-cart-price').html(resp[0]);
                toastr.success('{{ __("Quantity updated") }}');
            },
            error: function() {
                toastr.error('{{ __("Error occurred") }}');
            }
        });
    });

});
</script>
@endif {{-- نهاية @else للتحقق من النظام القديم --}}
