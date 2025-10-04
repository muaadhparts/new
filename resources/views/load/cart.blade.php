<div class="cart-popup">
    <ul class="cart_list product_list_widget ">
        @if (Session::has('cart'))
            @foreach(Session::get('cart')->items as $rowKey => $product)
                @php
                    // المفتاح الحقيقي Vendor-aware كما هو في جلسة السلة
                    $row    = (string) $rowKey;
                    // نسخة آمنة للـ DOM لتجنب محارف تُربك المحددات
                    $domKey = str_replace([':', '#', '.', ' ', '/', '\\'], '_', $row);

                    $slug     = data_get($product, 'item.slug');
                    $vendorId = data_get($product, 'item.user_id');
                    $merchantProductId = data_get($product, 'item.id');
                    $name     = data_get($product, 'item.name');
                    $photo    = data_get($product, 'item.photo');

                    $productUrl = ($vendorId && $merchantProductId)
                        ? route('front.product', ['slug' => $slug, 'vendor_id' => $vendorId, 'merchant_product_id' => $merchantProductId])
                        : 'javascript:;';
                @endphp

                <li class="mini-cart-item">
                    <div class="cart-remove remove"
                         data-class="cremove{{ $domKey }}"
                         data-href="{{ route('product.cart.remove', $row) }}"
                         title="Remove this item">
                        <i class="fas fa-times"></i>
                    </div>

                    <a href="{{ $productUrl }}" class="product-image">
                        <img
                            src="{{ \Illuminate\Support\Facades\Storage::url($photo) ?? asset('assets/images/noimage.png') }}"
                            class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail"
                            alt="Cart product">
                    </a>

                    <x-product-name :item="$product['item']" :vendor-id="$vendorId" :merchantProductId="$merchantProductId" target="_self" :useSearchRoute="false" class="product-name" />

                    <div class="cart-item-quantity">
                        <span class="cart-product-qty" id="cqt{{ $domKey }}">{{ $product['qty'] }}</span>
                        <span>{{ data_get($product, 'item.measure') }}</span>
                        x
                        <span id="prct{{ $domKey }}">
                            {{ App\Models\Product::convertPrice($product['item_price']) }}
                            {{ $product['discount'] == 0 ? '' : '(' . $product['discount'] . '% ' . __('Off') . ')' }}
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
        <div class="title">@lang('Total:')</div>
        <div class="price">
            <span class="cart-total">
                {{ Session::has('cart') ? App\Models\Product::convertPrice(Session::get('cart')->totalPrice) : '0.00' }}
            </span>
        </div>
    </div>

    <a href="{{ route('front.cart') }}" class="btn btn-primary rounded-0 view-cart">{{ __('View cart') }}</a>
    <a href="{{ route('front.checkout') }}" class="btn btn-secondary rounded-0 checkout">{{ __('Check out') }}</a>
</div>
