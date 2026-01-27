{{--
    Cart Dropdown Popup
    Uses: $headerCartData from HeaderComposer (DATA_FLOW_POLICY)
--}}
<div class="cart-popup">
    <ul class="cart_list product_list_widget">
        @if ($headerCartData['has_items'] ?? false)
            @foreach($headerCartData['items'] as $item)
                <li class="mini-cart-item">
                    <div class="cart-remove remove"
                         data-cart-key="{{ $item['key'] }}"
                         data-action="remove-from-dropdown"
                         title="@lang('Remove')">
                        <i class="fas fa-times"></i>
                    </div>

                    <a href="{{ $item['item_url'] }}" class="catalog-item-image">
                        <img src="{{ $item['photo_url'] }}"
                             alt="{{ $item['name'] }}"
                             onerror="this.onerror=null; this.src='{{ asset('assets/images/noimage.png') }}';">
                    </a>

                    <div class="catalog-item-info">
                        <a href="{{ $item['item_url'] }}" class="catalog-item-name">
                            {{ Str::limit($item['name'], 30) }}
                        </a>
                        <div class="cart-item-quantity">
                            <span>{{ $item['qty'] }}</span> x
                            <span>{{ $item['unit_price_formatted'] }}</span>
                            @if($item['has_discount'])
                                <small class="text-success">({{ $item['discount_percent'] }}% @lang('Off'))</small>
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

    @if ($headerCartData['has_items'] ?? false)
        <div class="total-cart">
            <div class="name">@lang('Total:')</div>
            <div class="price">
                <span class="cart-total">{{ $headerCartData['total_price_formatted'] }}</span>
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
