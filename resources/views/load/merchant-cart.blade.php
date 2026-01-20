{{--
    Cart Dropdown Popup
    Uses: merchant_cart session (ONLY)
--}}
@php
    $cart = Session::get('merchant_cart', ['items' => [], 'totals' => ['qty' => 0, 'total' => 0]]);
    $cartItems = $cart['items'] ?? [];
    $totalPrice = (float) ($cart['totals']['total'] ?? 0);
@endphp

<div class="cart-popup">
    <ul class="cart_list product_list_widget">
        @if (!empty($cartItems))
            @foreach($cartItems as $key => $item)
                @php
                    $domKey = str_replace([':', '#', '.', ' ', '/', '\\'], '_', (string)$key);
                    $partNumber = $item['part_number'] ?? '';
                    $merchantId = (int) ($item['merchant_id'] ?? 0);
                    $merchantItemId = (int) ($item['merchant_item_id'] ?? 0);
                    $name = app()->getLocale() === 'ar' && !empty($item['name_ar']) ? $item['name_ar'] : ($item['name'] ?? '');
                    $photo = $item['photo'] ?? '';
                    $qty = (int) ($item['qty'] ?? 1);
                    $unitPrice = (float) ($item['effective_unit_price'] ?? $item['unit_price'] ?? 0);
                    $discountPercent = (float) ($item['discount_percent'] ?? 0);

                    $photoUrl = $photo ? \Illuminate\Support\Facades\Storage::url($photo) : asset('assets/images/noimage.png');
                    $itemUrl = $partNumber
                        ? route('front.part-result', $partNumber)
                        : '#';
                @endphp

                <li class="mini-cart-item">
                    <div class="cart-remove remove"
                         data-cart-key="{{ $key }}"
                         data-action="remove-from-dropdown"
                         title="@lang('Remove')">
                        <i class="fas fa-times"></i>
                    </div>

                    <a href="{{ $itemUrl }}" class="catalog-item-image">
                        <img src="{{ $photoUrl }}"
                             alt="{{ $name }}"
                             onerror="this.onerror=null; this.src='{{ asset('assets/images/noimage.png') }}';">
                    </a>

                    <div class="catalog-item-info">
                        <a href="{{ $itemUrl }}" class="catalog-item-name">
                            {{ Str::limit($name, 30) }}
                        </a>
                        <div class="cart-item-quantity">
                            <span>{{ $qty }}</span> x
                            <span>{{ monetaryUnit()->convertAndFormat($unitPrice) }}</span>
                            @if($discountPercent > 0)
                                <small class="text-success">({{ $discountPercent }}% @lang('Off'))</small>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        @else
            <li class="empty-cart-message">
                <div class="text-center py-4">
                    <i class="fas fa-shopping-cart fa-2x text-muted mb-2"></i>
                    <p class="mb-0">@lang('Your cart is empty')</p>
                </div>
            </li>
        @endif
    </ul>

    @if (!empty($cartItems))
        <div class="total-cart">
            <div class="name">@lang('Total:')</div>
            <div class="price">
                <span class="cart-total">{{ monetaryUnit()->convertAndFormat($totalPrice) }}</span>
            </div>
        </div>
        <a href="{{ route('merchant-cart.index') }}" class="btn btn-primary rounded-0 view-cart">@lang('View Cart')</a>
    @else
        <a href="{{ route('front.index') }}" class="btn btn-outline-primary rounded-0 w-100">@lang('Continue Shopping')</a>
    @endif
</div>

<script>
document.addEventListener('click', function(e) {
    if (e.target.closest('[data-action="remove-from-dropdown"]')) {
        e.preventDefault();
        const btn = e.target.closest('[data-action="remove-from-dropdown"]');
        const cartKey = btn.dataset.cartKey;

        fetch('{{ route('merchant-cart.remove.post') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ key: cartKey })
        }).then(() => location.reload());
    }
});
</script>
