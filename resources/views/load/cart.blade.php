<div class="cart-popup">
    <ul class="cart_list product_list_widget ">
        @if (Session::has('cart'))
            @foreach(Session::get('cart')->items as $rowKey => $catalogItem)
                @php
                    // المفتاح الحقيقي Merchant-aware كما هو في جلسة السلة
                    $row    = (string) $rowKey;
                    // نسخة آمنة للـ DOM لتجنب محارف تُربك المحددات
                    $domKey = str_replace([':', '#', '.', ' ', '/', '\\'], '_', $row);

                    $slug     = data_get($catalogItem, 'item.slug');
                    $merchantUserId = data_get($catalogItem, 'item.user_id');
                    $merchantItemId = data_get($catalogItem, 'item.id');
                    $name     = data_get($catalogItem, 'item.name');
                    $photo    = data_get($catalogItem, 'item.photo');

                    $catalogItemUrl = ($merchantUserId && $merchantItemId)
                        ? route('front.catalog-item', ['slug' => $slug, 'merchant_id' => $merchantUserId, 'merchant_item_id' => $merchantItemId])
                        : 'javascript:;';
                @endphp

                <li class="mini-cart-item">
                    <div class="cart-remove remove"
                         data-class="cremove{{ $domKey }}"
                         data-href="{{ route('catalogItem.cart.remove', $row) }}"
                         title="Remove this item">
                        <i class="fas fa-times"></i>
                    </div>

                    <a href="{{ $catalogItemUrl }}" class="catalog-item-image">
                        <img
                            src="{{ \Illuminate\Support\Facades\Storage::url($photo) ?? asset('assets/images/noimage.png') }}"
                            class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail"
                            alt="Cart catalogItem">
                    </a>

                    <x-catalog-item-name :item="$catalogItem['item']" :merchant-user-id="$merchantUserId" :merchantItemId="$merchantItemId" target="_self" :useSearchRoute="false" class="catalog-item-name" />

                    <div class="cart-item-quantity">
                        <span class="cart-catalogItem-qty" id="cqt{{ $domKey }}">{{ $catalogItem['qty'] }}</span>
                        <span>{{ data_get($catalogItem, 'item.measure') }}</span>
                        x
                        <span id="prct{{ $domKey }}">
                            {{ App\Models\CatalogItem::convertPrice($catalogItem['item_price']) }}
                            {{ $catalogItem['discount'] == 0 ? '' : '(' . $catalogItem['discount'] . '% ' . __('Off') . ')' }}
                        </span>
                    </div>
                </li>
            @endforeach
        @else
            <div class="card">
                <div class="card-body">
                    <h4 class="text-center">{{ __('Cart is Empty!! Add some catalogItems in your Cart') }}</h4>
                </div>
            </div>
        @endif
    </ul>

    <div class="total-cart">
        <div class="title">@lang('Total:')</div>
        <div class="price">
            <span class="cart-total">
                {{ Session::has('cart') ? App\Models\CatalogItem::convertPrice(Session::get('cart')->totalPrice) : '0.00' }}
            </span>
        </div>
    </div>

    <a href="{{ route('front.cart') }}" class="btn btn-primary rounded-0 view-cart">{{ __('View cart') }}</a>
    <a href="{{ route('front.cart') }}" class="btn btn-secondary rounded-0 checkout">{{ __('Check out') }}</a>
</div>
