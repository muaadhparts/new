{{--
    Cart Dropdown Popup (v4 - New Cart System)
    Works with both new merchant_cart and legacy cart sessions
--}}
<div class="cart-popup">
    <ul class="cart_list product_list_widget">
        @php
            // Try new cart first, then fallbacks
            $cartItems = [];
            $totalPrice = 0;

            if (Session::has('merchant_cart')) {
                $cart = Session::get('merchant_cart');
                $cartItems = $cart['items'] ?? [];
                $totalPrice = $cart['totals']['total'] ?? 0;
            } elseif (Session::has('cart_v2')) {
                $cart = Session::get('cart_v2');
                $cartItems = $cart['items'] ?? [];
                $totalPrice = $cart['totalPrice'] ?? 0;
            } elseif (Session::has('cart')) {
                $oldCart = Session::get('cart');
                if (is_object($oldCart)) {
                    $cartItems = $oldCart->items ?? [];
                    $totalPrice = $oldCart->totalPrice ?? 0;
                }
            }
        @endphp

        @if (!empty($cartItems))
            @foreach($cartItems as $rowKey => $item)
                @php
                    $domKey = str_replace([':', '#', '.', ' ', '/', '\\'], '_', (string)$rowKey);

                    // Handle both new format and legacy format
                    if (isset($item['slug'])) {
                        // New cart format (v4)
                        $slug = $item['slug'] ?? '';
                        $merchantId = $item['merchant_id'] ?? 0;
                        $merchantItemId = $item['merchant_item_id'] ?? 0;
                        $name = app()->getLocale() === 'ar' && !empty($item['name_ar']) ? $item['name_ar'] : ($item['name'] ?? '');
                        $photo = $item['photo'] ?? '';
                        $qty = (int) ($item['qty'] ?? 1);
                        $unitPrice = (float) ($item['effective_unit_price'] ?? $item['unit_price'] ?? 0);
                        $discountPercent = (float) ($item['discount_percent'] ?? 0);
                    } else {
                        // Legacy format
                        $slug = data_get($item, 'item.slug', '');
                        $merchantId = $item['user_id'] ?? data_get($item, 'item.user_id', 0);
                        $merchantItemId = $item['merchant_item_id'] ?? data_get($item, 'item.id', 0);
                        $name = data_get($item, 'item.name', '');
                        $photo = data_get($item, 'item.photo', '');
                        $qty = (int) ($item['qty'] ?? 1);
                        $unitPrice = (float) ($item['item_price'] ?? 0);
                        $discountPercent = (float) ($item['discount'] ?? 0);
                    }

                    $photoUrl = $photo ? \Illuminate\Support\Facades\Storage::url($photo) : asset('assets/images/noimage.png');

                    $catalogItemUrl = ($slug && $merchantId && $merchantItemId)
                        ? route('front.catalog-item', ['slug' => $slug, 'merchant_id' => $merchantId, 'merchant_item_id' => $merchantItemId])
                        : 'javascript:;';
                @endphp

                <li class="mini-cart-item">
                    <div class="cart-remove remove"
                         data-class="cremove{{ $domKey }}"
                         data-cart-key="{{ $rowKey }}"
                         data-action="remove-from-dropdown"
                         title="@lang('Remove this item')">
                        <i class="fas fa-times"></i>
                    </div>

                    <a href="{{ $catalogItemUrl }}" class="catalog-item-image">
                        <img src="{{ $photoUrl }}"
                             class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail"
                             alt="{{ $name }}"
                             onerror="this.onerror=null; this.src='{{ asset('assets/images/noimage.png') }}';">
                    </a>

                    <div class="catalog-item-info">
                        <a href="{{ $catalogItemUrl }}" class="catalog-item-name">
                            {{ Str::limit($name, 30) }}
                        </a>

                        <div class="cart-item-quantity">
                            <span class="cart-catalogItem-qty" id="cqt{{ $domKey }}">{{ $qty }}</span>
                            x
                            <span id="prct{{ $domKey }}">
                                {{ monetaryUnit()->convertAndFormat($unitPrice) }}
                                @if($discountPercent > 0)
                                    <small class="text-success">({{ $discountPercent }}% @lang('Off'))</small>
                                @endif
                            </span>
                        </div>
                    </div>
                </li>
            @endforeach
        @else
            <li class="empty-cart-message">
                <div class="card">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-shopping-cart fa-2x text-muted mb-2"></i>
                        <p class="mb-0">@lang('Your cart is empty')</p>
                    </div>
                </div>
            </li>
        @endif
    </ul>

    @if (!empty($cartItems))
        <div class="total-cart">
            <div class="name">@lang('Total:')</div>
            <div class="price">
                <span class="cart-total">
                    {{ monetaryUnit()->convertAndFormat($totalPrice) }}
                </span>
            </div>
        </div>

        <div class="cart-popup-buttons">
            <a href="{{ route('merchant-cart.index') }}" class="btn btn-primary rounded-0 view-cart">
                @lang('View Cart')
            </a>
        </div>
    @else
        <div class="cart-popup-buttons">
            <a href="{{ route('front.index') }}" class="btn btn-outline-primary rounded-0 w-100">
                @lang('Continue Shopping')
            </a>
        </div>
    @endif
</div>

<script>
// Handle remove from dropdown
document.addEventListener('click', function(e) {
    if (e.target.closest('[data-action="remove-from-dropdown"]')) {
        e.preventDefault();
        const btn = e.target.closest('[data-action="remove-from-dropdown"]');
        const cartKey = btn.dataset.cartKey;

        if (typeof MerchantCart !== 'undefined' && MerchantCart.remove) {
            MerchantCart.remove(cartKey).then(() => {
                // Reload the page to refresh the dropdown
                location.reload();
            });
        } else {
            // Fallback: redirect to cart page
            window.location.href = '{{ route('merchant-cart.index') }}';
        }
    }
});
</script>
