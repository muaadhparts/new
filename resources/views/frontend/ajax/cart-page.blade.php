<div class="container gs-cart-container">
    <div class="row gs-cart-row">

        @if (Session::has('cart'))

            @php $discount = 0; @endphp

            <div class="col-lg-8">
                <div class="cart-table table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">@lang('Product Name')</th>
                                <th scope="col">@lang('Price')</th>
                                <th scope="col">@lang('Quantity')</th>
                                <th scope="col">@lang('Subtotal')</th>
                                <th scope="col">@lang('Action')</th>
                            </tr>
                        </thead>

                        <tbody class="t_body">
                            @foreach ($products as $product)
                                @php
                                    // خصم العنصر
                                    if (!empty($product['discount'])) {
                                        $total_itemprice = (float) ($product['item_price'] ?? 0) * (int) ($product['qty'] ?? 1);
                                        $tdiscount = ($total_itemprice * (float) $product['discount']) / 100;
                                        $discount += $tdiscount;
                                    }

                                    // بيانات منتج/بائع آمنة
                                    $vendorId = (int) (data_get($product, 'item.vendor_user_id') ?? data_get($product, 'item.user_id') ?? 0);
                                    $pid      = (int) data_get($product, 'item.id');
                                    $slug     = data_get($product, 'item.slug');
                                    $name     = data_get($product, 'item.name');
                                    $sku      = data_get($product, 'item.sku');
                                    $photo    = data_get($product, 'item.photo');

                                    // أبعاد العنصر
                                    $sizeDim  = (string) ($product['size_key'] ?? $product['size'] ?? '');
                                    $colorDim = (string) ($product['color'] ?? '');
                                    $valsDim  = str_replace([' ', ','], '', (string) ($product['values'] ?? ''));

                                    // مفتاح السطر Vendor-aware مطابق تمامًا لـ Cart::makeKey()
                                    $rowKey   = $pid . ':u' . $vendorId . ':' . $sizeDim . ':' . $colorDim . ':' . $valsDim;

                                    // مفتاح آمن لعناصر الـ DOM
                                    $domKey   = str_replace([':', '#', '.', ' ', '/','\\'], '_', $rowKey);

                                    // رابط تفاصيل
                                    $productUrl = $vendorId
                                        ? route('front.product', ['slug' => $slug, 'user' => $vendorId])
                                        : 'javascript:;';
                                @endphp

                                <tr>
                                    <td class="cart-product-area">
                                        <div class="cart-product d-flex">
                                            <img
                                                src="{{ $photo ? \Illuminate\Support\Facades\Storage::url($photo) : asset('assets/images/noimage.png') }}"
                                                alt="">
                                            <div class="cart-product-info">
                                                <a class="cart-title d-inline-block" href="{{ $productUrl }}">
                                                    {{ mb_strlen($name, 'UTF-8') > 35 ? mb_substr($name, 0, 35, 'UTF-8') . '...' : $name }}
                                                </a>

                                                @if (!empty($sku))
                                                    <p class="text-muted small mb-1">
                                                        <span class="fw-medium">@lang('SKU'):</span>
                                                        <a href="{{ $productUrl }}" class="text-primary text-decoration-none">{{ $sku }}</a>
                                                    </p>
                                                @endif

                                                <div class="d-flex align-items-center gap-2">
                                                    @if (!empty($product['color']))
                                                        @lang('Color'):
                                                        <span class="cart-color d-inline-block rounded-2"
                                                              style="border:10px solid #{{ $product['color'] == '' ? 'ffffff' : $product['color'] }};"></span>
                                                    @endif

                                                    @if (!empty($product['size']))
                                                        @lang('Size'): <span class="d-inline-block">{{ $product['size'] }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="cart-price">
                                        {{ \App\Models\Product::convertPrice($product['item_price']) }}
                                    </td>

                                    @if (data_get($product, 'item.type') == 'Physical')
                                        <td>
                                            <div class="cart-quantity" data-row="{{ $rowKey }}">
                                                <button class="cart-quantity-btn quantity-down" data-row="{{ $rowKey }}">-</button>

                                                <input type="text"
                                                       id="qty{{ $domKey }}"
                                                       value="{{ (int)$product['qty'] }}"
                                                       class="borderless" readonly>

                                                {{-- قيم مساندة لعمليات الزيادة/النقصان --}}
                                                <input type="hidden" class="prodid"     value="{{ $pid }}">
                                                <input type="hidden" class="itemid"     value="{{ $rowKey }}">
                                                <input type="hidden" class="size_qty"   value="{{ $product['size_qty'] ?? '' }}">
                                                <input type="hidden" class="size_price" value="{{ $product['size_price'] ?? 0 }}">
                                                <input type="hidden" class="minimum_qty"
                                                       value="{{ data_get($product,'item.minimum_qty') === null ? '0' : (int) data_get($product,'item.minimum_qty') }}">
                                                <input type="hidden" class="vendor_id"  value="{{ $vendorId }}">
                                                <input type="hidden" class="size_key"   value="{{ $product['size_key'] ?? '' }}">
                                                <input type="hidden" class="color_val"  value="{{ $colorDim }}">
                                                <input type="hidden" class="values_val" value="{{ $valsDim }}">

                                                <button class="cart-quantity-btn quantity-up" data-row="{{ $rowKey }}">+</button>
                                            </div>
                                        </td>
                                    @else
                                        <td class="product-quantity">1</td>
                                    @endif

                                    {{-- مخزون فعلي للقياس/اللون الحالي --}}
                                    @if (!empty($product['size_qty']))
                                        <input type="hidden" id="stock{{ $domKey }}" value="{{ (int) $product['size_qty'] }}">
                                    @elseif (data_get($product,'item.type') != 'Physical')
                                        <input type="hidden" id="stock{{ $domKey }}" value="1">
                                    @else
                                        <input type="hidden" id="stock{{ $domKey }}" value="{{ (int) $product['stock'] }}">
                                    @endif

                                    <td class="cart-price" id="prc{{ $domKey }}">
                                        {{ \App\Models\Product::convertPrice($product['price']) }}
                                        @if (!empty($product['discount']))
                                            <strong>{{ $product['discount'] }} %{{ __('off') }}</strong>
                                        @endif
                                    </td>

                                    <td>
                                        {{-- نجعل الحذف يمرّر rowKey لتكون العملية دقيقة --}}
                                        <a class="cart-remove-btn cremove{{ $domKey }}"
                                           href="{{ route('product.cart.remove', $pid) }}?row={{ urlencode($rowKey) }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none">
                                                <path d="M9 3H15M3 6H21M19 6L18.2987 16.5193C18.1935 18.0975 18.1409 18.8867 17.8 19.485C17.4999 20.0118 17.0472 20.4353 16.5017 20.6997C15.882 21 15.0911 21 13.5093 21H10.4907C8.90891 21 8.11803 21 7.49834 20.6997C6.95276 20.4353 6.50009 20.0118 6.19998 19.485C5.85911 18.8867 5.8065 18.0975 5.70129 16.5193L5 6M10 10.5V15.5M14 10.5V15.5"
                                                      stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                      stroke-linejoin="round"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="cart-summary">
                    <h4 class="cart-summary-title">@lang('Cart Summary')</h4>
                    <div class="cart-summary-content">
                        <div class="cart-summary-item d-flex justify-content-between">
                            <p class="cart-summary-subtitle">
                                @lang('Subtotal')({{ count(Session::get('cart')->items) }} @lang('Items'))
                            </p>
                            <p class="cart-summary-price">
                                {{ Session::has('cart') ? \App\Models\Product::convertPrice(($totalPrice ?? 0) + ($discount ?? 0)) : '0.00' }}
                            </p>
                        </div>
                        <div class="cart-summary-item d-flex justify-content-between">
                            <p class="cart-summary-subtitle">@lang('Discount')</p>
                            <p class="cart-summary-price">{{ \App\Models\Product::convertPrice($discount) }}</p>
                        </div>
                        <div class="cart-summary-item d-flex justify-content-between">
                            <p class="cart-summary-subtitle">@lang('Total')</p>
                            <p class="cart-summary-price total-cart-price">
                                {{ Session::has('cart') ? \App\Models\Product::convertPrice($mainTotal ?? 0) : '0.00' }}
                            </p>
                        </div>

                        <div class="cart-summary-btn">
                            @auth
                                <a href="{{ route('front.checkout') }}" class="template-btn w-100">
                                    @lang('Proceed to Checkout')
                                </a>
                            @else
                                @if (Route::has('user.register'))
                                    <a href="{{ route('user.register', ['redirect' => 'cart']) }}" class="template-btn w-100">
                                        @lang('Proceed to Checkout')
                                    </a>
                                @else
                                    <a href="{{ route('user.login', ['redirect' => 'cart']) }}" class="template-btn w-100">
                                        @lang('Proceed to Checkout')
                                    </a>
                                @endif
                            @endauth
                        </div>
                    </div>
                </div>
            </div>

        @else
            <div class="col-xl-12 col-lg-12 col-md-12 col-12">
                <div class="card border py-4">
                    <div class="card-body">
                        <h4 class="text-center">{{ __('Cart is Empty!! Add some products in your Cart') }}</h4>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
