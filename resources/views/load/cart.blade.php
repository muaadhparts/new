<div class="cart-popup">
    <ul class="cart_list product_list_widget ">
        @if (Session::has('cart') && is_array(Session::get('cart')->items))
            @foreach(Session::get('cart')->items as $product)
                @php
                    // بيانات أساسية آمنة
                    $pid      = (int) data_get($product, 'item.id');
                    $slug     = (string) data_get($product, 'item.slug');
                    $name     = (string) data_get($product, 'item.name');
                    $sku      = (string) data_get($product, 'item.sku');
                    $photo    = (string) data_get($product, 'item.photo');

                    // Vendor-aware
                    $vendorId = (int) (data_get($product, 'item.vendor_user_id') ?? data_get($product, 'item.user_id') ?? 0);

                    // أبعاد العنصر (مطابقة لبناء مفتاح السلة في Cart::makeKey)
                    $sizeDim  = (string) ($product['size_key'] ?? $product['size'] ?? '');
                    $colorDim = (string) ($product['color'] ?? '');
                    $valsDim  = str_replace([' ', ','], '', (string) ($product['values'] ?? ''));

                    // rowKey Vendor-aware
                    $rowKey   = $pid . ':u' . $vendorId . ':' . $sizeDim . ':' . $colorDim . ':' . $valsDim;

                    // Dom-safe key
                    $domKey   = str_replace([':', '#', '.', ' ', '/', '\\'], '_', $rowKey);

                    // روابط التفاصيل
                    $productUrl = $vendorId
                        ? route('front.product', ['slug' => $slug, 'user' => $vendorId])
                        : 'javascript:;';

                    // صورة
                    $imgSrc = $photo
                        ? (filter_var($photo, FILTER_VALIDATE_URL) ? $photo : asset('assets/images/products/' . $photo))
                        : asset('assets/images/noimage.png');
                @endphp

                <li class="mini-cart-item">
                    {{-- Remove: مرر rowKey بدقة --}}
                    <div class="cart-remove remove"
                         data-class="cremove{{ $domKey }}"
                         data-href="{{ route('product.cart.remove', $pid) . '?row=' . urlencode($rowKey) }}"
                         title="@lang('Remove this item')">
                        <i class="fas fa-times"></i>
                    </div>

                    <a href="{{ $productUrl }}" class="product-image">
                        <img src="{{ $imgSrc }}" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="Cart product">
                    </a>

                    <a href="{{ $productUrl }}" class="product-name">
                        {{ mb_strlen($name, 'UTF-8') > 45 ? mb_substr($name, 0, 45, 'UTF-8') . '...' : $name }}
                    </a>

                    <div class="cart-item-quantity">
                        <span class="cart-product-qty" id="cqt{{ $domKey }}">{{ (int) ($product['qty'] ?? 1) }}</span>
                        <span>{{ (string) data_get($product,'item.measure') }}</span>
                        x
                        <span id="prct{{ $domKey }}">
                            {{ \App\Models\Product::convertPrice($product['item_price'] ?? 0) }}
                            {{ !empty($product['discount']) ? '(' . $product['discount'] . '% ' . __('Off') . ')' : '' }}
                        </span>
                    </div>
                </li>
            @endforeach
        @else
            <div class="card">
                <div class="card-body">
                    <h4 class="text-center">{{ __('Cart is Empty!! Add some products in your Cart') }}</h4>
                </div>
            </div>
        @endif
    </ul>

    <div class="total-cart">
        <div class="title">@lang('Total:') </div>
        <div class="price">
            <span class="cart-total">
                {{ Session::has('cart') ? \App\Models\Product::convertPrice(Session::get('cart')->totalPrice ?? 0) : '0.00' }}
            </span>
        </div>
    </div>

    <a href="{{ route('front.cart') }}" class="btn btn-primary rounded-0 view-cart">{{ __('View cart') }}</a>
    <a href="{{ route('front.checkout') }}" class="btn btn-secondary rounded-0 checkout">{{ __('Check out') }}</a>
</div>
