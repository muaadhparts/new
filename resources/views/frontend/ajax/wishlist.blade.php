<tbody class="wishlist-items-wrapper">
    @foreach($wishlistItems as $wishlistItem)
        @php
            $product = $wishlistItem->product;
            $merchantProduct = $wishlistItem->effective_merchant_product;
            $vendorId = $merchantProduct ? $merchantProduct->user_id : 0;
        @endphp

    <tr id="yith-wcwl-row-{{ $wishlistItem->id }}" data-row-id="{{ $wishlistItem->id }}">
        <td class="product-remove">
            <div>
                <a href="{{ route('user-wishlist-remove', $wishlistItem->id) }}" class="remove wishlist-remove remove_from_wishlist" title="Remove this product">×</a>
            </div>
        </td>
        <td class="product-thumbnail">
            <a href="{{ route('front.product.user', ['slug' => $product->slug, 'user' => $vendorId]) }}">
                <img src="{{ \Illuminate\Support\Facades\Storage::url($product->photo) ?? asset('assets/images/noimage.png') }}" alt="">
            </a>
        </td>
        <td class="product-name">
            <a href="{{ route('front.product.user', ['slug' => $product->slug, 'user' => $vendorId]) }}">
                {{ $product->name }}
            </a>
            @if($merchantProduct && $merchantProduct->user)
                <br><small class="text-muted">{{ __('Vendor:') }} {{ $merchantProduct->user->shop_name ?? $merchantProduct->user->name }}</small>
            @endif
        </td>
        <td class="product-price">
            <span class="woocommerce-Price-amount amount">
                <bdi>
                    <span class="woocommerce-Price-currencySymbol">
                        @if($merchantProduct)
                            {{ $curr->sign }}{{ number_format($merchantProduct->price * $curr->value, 2) }}
                            @if($merchantProduct->previous_price && $merchantProduct->previous_price > $merchantProduct->price)
                                <small>
                                    <del>{{ $curr->sign }}{{ number_format($merchantProduct->previous_price * $curr->value, 2) }}</del>
                                </small>
                            @endif
                        @else
                            {{ $product->showPrice() }}
                        @endif
                    </span>
                </bdi>
            </span>
        </td>
        <td class="product-stock-status">
            @if($product->type == 'Physical')
                @if($merchantProduct)
                    @if(!$merchantProduct->stock || $merchantProduct->stock == 0)
                        <div class="stock-availability out-stock">{{ __('Out Of Stock') }}</div>
                    @else
                        <div class="stock-availability in-stock text-bold">{{ __('In Stock') }} ({{ $merchantProduct->stock }})</div>
                    @endif
                @else
                    <div class="stock-availability out-stock">{{ __('Not Available') }}</div>
                @endif
            @endif
        </td>
        <td class="product-add-to-cart">
            @if($merchantProduct && ($merchantProduct->stock > 0 || !$merchantProduct->stock))
                <button type="submit" data-merchant-product-id="{{ $merchantProduct->id }}" class="single_add_to_cart_button button alt single_add_to_cart_ajax_button">{{ __('Add to cart') }}</button>
            @else
                <button type="button" disabled class="single_add_to_cart_button button alt">{{ __('Out of Stock') }}</button>
            @endif
        </td>
        <input type="hidden" id="product_price" value="{{ $merchantProduct ? round($merchantProduct->price * $curr->value, 2) : 0 }}">
        <input type="hidden" id="merchant_product_id" value="{{ $merchantProduct ? $merchantProduct->id : 0 }}">
        <input type="hidden" id="curr_pos" value="{{ $gs->currency_format }}">
        <input type="hidden" id="curr_sign" value="{{ $curr->sign }}">
    </tr>
    @endforeach
</tbody>

<script>

    // Tooltip Section

    $('[data-toggle="tooltip"]').tooltip({});

    $('[rel-toggle="tooltip"]').tooltip();

    $('[data-toggle="tooltip"]').on('click', function () {
      $(this).tooltip('hide');
    })


    $('[rel-toggle="tooltip"]').on('click', function () {
      $(this).tooltip('hide');
    })

    // Tooltip Section Ends
  </script>
