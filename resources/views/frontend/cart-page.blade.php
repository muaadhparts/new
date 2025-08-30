
<div class="container-fluid px-3">
    <div class="row g-4">
        @if (Session::has('cart'))
            @php
                $discount = 0;
            @endphp

            <!-- جدول المنتجات المحسن -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-shopping-cart me-2"></i>
                            @lang('Shopping Cart') ({{ count(Session::get('cart')->items) }} @lang('Items'))
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="border-0 py-3">@lang('Product')</th>
                                        <th scope="col" class="border-0 py-3 text-center">@lang('Price')</th>
                                        <th scope="col" class="border-0 py-3 text-center">@lang('Quantity')</th>
                                        <th scope="col" class="border-0 py-3 text-center">@lang('Subtotal')</th>
                                        <th scope="col" class="border-0 py-3 text-center">@lang('Action')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($products as $product)
                                        @php
                                            if ($product['discount'] != 0) {
                                                $total_itemprice = $product['item_price'] * $product['qty'];
                                                $tdiscount = ($total_itemprice * $product['discount']) / 100;
                                                $discount += $tdiscount;
                                            }
                                        @endphp

                                        <tr class="border-bottom">
                                            <!-- معلومات المنتج -->
                                            <td class="py-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="position-relative me-3">
                                                        <img src="{{ $product['item']['photo'] ? \Illuminate\Support\Facades\Storage::url($product['item']['photo']) : asset('assets/images/noimage.png') }}"
                                                            alt="{{ $product['item']['name'] }}"
                                                            class="rounded border"
                                                            style="width: 80px; height: 80px; object-fit: cover;">
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">
                                                            <a href="{{ route('front.product', $product['item']['slug']) }}" 
                                                                class="text-decoration-none text-dark fw-medium">
                                                                {{ mb_strlen($product['item']['name'], 'UTF-8') > 40
                                                                    ? mb_substr($product['item']['name'], 0, 40, 'UTF-8') . '...'
                                                                    : $product['item']['name'] }}
                                                            </a>
                                                        </h6>
                                                        <p class="text-muted small mb-2">
                                                            <span class="fw-medium">@lang('SKU'):</span>
                                                            <a href="{{ route('front.product', $product['item']['slug']) }}" 
                                                                class="text-primary text-decoration-none">
                                                                {{ $product['item']['sku'] }}
                                                            </a>
                                                        </p>
                                                        <div class="d-flex flex-wrap gap-2">
                                                            @if (!empty($product['color']))
                                                                <span class="badge bg-light text-dark border">
                                                                    @lang('Color'): 
                                                                    <span class="d-inline-block rounded-circle ms-1" 
                                                                        style="width: 12px; height: 12px; background-color: #{{ $product['color'] == '' ? 'cccccc' : $product['color'] }};"></span>
                                                                </span>
                                                            @endif
                                                            @if (!empty($product['size']))
                                                                <span class="badge bg-light text-dark border">
                                                                    @lang('Size'): {{ $product['size'] }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- السعر -->
                                            <td class="py-4 text-center">
                                                <span class="fw-bold text-primary">
                                                    {{ App\Models\Product::convertPrice($product['item_price']) }}
                                                </span>
                                            </td>
`
                                            <!-- الكمية -->
                                            @if ($product['item']['type'] == 'Physical')
                                                <td class="py-4 text-center">
                                                    <div class="d-flex justify-content-center">
                                                        <div class="input-group" style="max-width: 120px;">
                                                            <button class="btn btn-outline-secondary btn-sm quantity-down" type="button">
                                                                <i class="fas fa-minus"></i>
                                                            </button>
                                                            <input type="text"
                                                                id="qty{{ $product['item']['id'] . $product['size'] . $product['color'] . str_replace(str_split(' ,'), '', $product['values']) }}"
                                                                value="{{ $product['qty'] }}"
                                                                class="form-control text-center border-secondary"
                                                                readonly>
                                                            @if($product['stock'] > 0)
                                                                <button class="btn btn-outline-secondary btn-sm quantity-up" type="button">
                                                                    <i class="fas fa-plus"></i>
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- الحقول المخفية -->
                                                    <input type="hidden" class="prodid" value="{{ $product['item']['id'] }}">
                                                    <input type="hidden" class="itemid" value="{{ $product['item']['id'] . $product['size'] . $product['color'] . str_replace(str_split(' ,'), '', $product['values']) }}">
                                                    <input type="hidden" class="size_qty" value="{{ $product['size_qty'] }}">
                                                    <input type="hidden" class="size_price" value="{{ $product['size_price'] }}">
                                                    <input type="hidden" class="minimum_qty" value="{{ $product['item']['minimum_qty'] == null ? '0' : $product['item']['minimum_qty'] }}">
                                                    
                                                    @if ($product['size_qty'])
                                                        <input type="hidden" id="stock{{ $product['item']['id'] . $product['size'] . $product['color'] . str_replace(str_split(' ,'), '', $product['values']) }}" value="{{ $product['size_qty'] }}">
                                                    @elseif($product['item']['type'] != 'Physical')
                                                        <input type="hidden" id="stock{{ $product['item']['id'] . $product['size'] . $product['color'] . str_replace(str_split(' ,'), '', $product['values']) }}" value="1">
                                                    @else
                                                        <input type="hidden" id="stock{{ $product['item']['id'] . $product['size'] . $product['color'] . str_replace(str_split(' ,'), '', $product['values']) }}" value="{{ $product['stock'] }}">
                                                    @endif
                                                </td>
                                            @else
                                                <td class="py-4 text-center">
                                                    <span class="badge bg-info">1</span>
                                                </td>
                                            @endif

                                            <!-- المجموع الفرعي -->
                                            <td class="py-4 text-center">
                                                <div id="prc{{ $product['item']['id'] . $product['size'] . $product['color'] . str_replace(str_split(' ,'), '', $product['values']) }}">
                                                    <span class="fw-bold text-success fs-5">
                                                        {{ App\Models\Product::convertPrice($product['price']) }}
                                                    </span>
                                                    @if ($product['discount'] != 0)
                                                        <div class="mt-1">
                                                            <span class="badge bg-danger">
                                                                {{ $product['discount'] }}% @lang('off')
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>

                                            <!-- إجراءات -->
                                            <td class="py-4 text-center">
                                                <button type="button" 
                                                    class="btn btn-outline-danger btn-sm cart-remove-btn"
                                                    data-class="cremove{{ $product['item']['id'] . $product['size'] . $product['color'] . str_replace(str_split(' ,'), '', $product['values']) }}"
                                                    data-href="{{ route('product.cart.remove', $product['item']['id'] . $product['size'] . $product['color'] . str_replace(str_split(' ,'), '', $product['values'])) }}"
                                                    title="@lang('Remove from cart')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ملخص الطلب المحسن -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-calculator me-2"></i>
                            @lang('Order Summary')
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- إحصائيات سريعة -->
                        <div class="row text-center mb-4">
                            <div class="col-6">
                                <div class="border-end">
                                    <h6 class="text-muted mb-1">@lang('Items')</h6>
                                    <span class="fw-bold text-primary fs-5">{{ count(Session::get('cart')->items) }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <h6 class="text-muted mb-1">@lang('Total Weight')</h6>
                                <span class="fw-bold text-info fs-6">{{ array_sum(array_column($products, 'qty')) }} @lang('Units')</span>
                            </div>
                        </div>

                        <!-- تفاصيل التكلفة -->
                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">
                                    <i class="fas fa-shopping-bag me-2"></i>
                                    @lang('Subtotal')
                                </span>
                                <span class="fw-medium">
                                    {{ Session::has('cart') ? App\Models\Product::convertPrice($totalPrice + $discount) : '0.00' }}
                                </span>
                            </div>

                            @if($discount > 0)
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-success">
                                        <i class="fas fa-tag me-2"></i>
                                        @lang('Discount')
                                    </span>
                                    <span class="text-success fw-medium">
                                        -{{ App\Models\Product::convertPrice($discount) }}
                                    </span>
                                </div>
                            @endif

                            {{-- لا يتم عرض ضريبة القيمة المضافة في صفحة السلة الأولى لأن الضريبة تعتمد على الدولة
                                 وسيتم احتسابها لاحقاً في صفحة الشحن بعد تسجيل الدخول --}}

                            <div class="border-top pt-3 mb-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold fs-5">
                                        <i class="fas fa-receipt me-2 text-success"></i>
                                        @lang('Total')
                                    </span>
                                    <span class="fw-bold fs-4 text-success total-cart-price">
                                        {{ Session::has('cart') ? App\Models\Product::convertPrice($mainTotal + $discount) : '0.00' }}
                                    </span>
                                </div>
                            </div>

                            <!-- أزرار الإجراءات -->
                            <div class="d-grid gap-2">
                                @auth
                                    <!-- If the user is logged in send them directly to the quick checkout -->
                                    <a href="{{ route('front.checkout.quick') }}" class="btn btn-success btn-lg">
                                        <i class="fas fa-credit-card me-2"></i>
                                        @lang('Proceed to Checkout')
                                    </a>
                                @else
                                    <!-- Guests are redirected to the login page with a redirect flag pointing back to the cart -->
                                    <a href="{{ route('user.login', ['redirect' => 'cart']) }}" class="btn btn-success btn-lg">
                                        <i class="fas fa-credit-card me-2"></i>
                                        @lang('Proceed to Checkout')
                                    </a>
                                @endauth
                                <a href="{{ route('front.category') }}" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    @lang('Continue Shopping')
                                </a>
                            </div>

                            <!-- معلومات إضافية -->
                            <div class="mt-4 p-3 bg-light rounded">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-shield-alt text-success me-2"></i>
                                    <small class="text-muted">@lang('Secure SSL Encryption')</small>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-truck text-primary me-2"></i>
                                    <small class="text-muted">@lang('Free shipping on orders over') 500 @lang('SAR')</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-undo text-warning me-2"></i>
                                    <small class="text-muted">@lang('30-day return policy')</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- رسالة السلة الفارغة المحسنة -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-shopping-cart text-muted" style="font-size: 5rem; opacity: 0.3;"></i>
                        </div>
                        <h3 class="text-muted mb-3">@lang('Your cart is empty')</h3>
                        <p class="text-muted mb-4">
                            @lang('Looks like you have not added anything to your cart yet. Start shopping to fill it up!')
                        </p>
                        
                        <!-- إحصائيات سريعة -->
                        <div class="row justify-content-center mb-4">
                            <div class="col-md-8">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="p-3 bg-light rounded">
                                            <i class="fas fa-tags text-primary mb-2 d-block"></i>
                                            <small class="text-muted">@lang('Best Deals')</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-3 bg-light rounded">
                                            <i class="fas fa-shipping-fast text-success mb-2 d-block"></i>
                                            <small class="text-muted">@lang('Fast Shipping')</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-3 bg-light rounded">
                                            <i class="fas fa-shield-alt text-warning mb-2 d-block"></i>
                                            <small class="text-muted">@lang('Secure Payment')</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- أزرار الإجراءات -->
                        <div class="d-flex flex-column flex-md-row gap-3 justify-content-center">
                            <a href="{{ route('front.category') }}" class="btn btn-primary btn-lg px-4">
                                <i class="fas fa-shopping-bag me-2"></i>
                                @lang('Start Shopping')
                            </a>
                            <a href="{{ route('front.index') }}" class="btn btn-outline-secondary btn-lg px-4">
                                <i class="fas fa-home me-2"></i>
                                @lang('Back to Home')
                            </a>
                        </div>

                        <!-- اقتراحات -->
                        <div class="mt-5 pt-4 border-top">
                            <h6 class="text-muted mb-3">@lang('You might be interested in:')</h6>
                            <div class="d-flex flex-wrap gap-2 justify-content-center">
                                <a href="{{ route('front.category') }}" class="btn btn-sm btn-outline-primary">@lang('New Arrivals')</a>
                                <a href="{{ route('front.category') }}" class="btn btn-sm btn-outline-success">@lang('Best Sellers')</a>
                                <a href="{{ route('front.category') }}" class="btn btn-sm btn-outline-warning">@lang('Special Offers')</a>
                                <a href="{{ route('front.category') }}" class="btn btn-sm btn-outline-info">@lang('Featured Products')</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
