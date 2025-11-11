<tbody class="wishlist-items-wrapper">
    @foreach($wishlists as $wishlist)
    @php
        $wishlistMerchant = $wishlist->merchantProducts()
            ->where('status', 1)
            ->whereHas('user', function ($user) {
                $user->where('is_vendor', 2);
            })
            ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
            ->orderBy('price')
            ->first();

        $wishlistProductUrl = $wishlistMerchant && $wishlist->slug
            ? route('front.product', ['slug' => $wishlist->slug, 'vendor_id' => $wishlistMerchant->user_id, 'merchant_product_id' => $wishlistMerchant->id])
            : ($wishlist->slug ? route('front.product.legacy', $wishlist->slug) : '#');
    @endphp

    <tr id="yith-wcwl-row-103" data-row-id="103">
        <td class="product-remove">
            <div>
                <a href="{{ route('user-wishlist-remove', App\Models\Wishlist::where('user_id','=',$user->id)->where('product_id','=',$wishlist->id)->first()->id ) }}" class="remove wishlist-remove remove_from_wishlist" title="Remove this product">×</a>
            </div>
        </td>
        <td class="product-thumbnail">
            <a href="{{ $wishlistProductUrl }}"> <img src="{{ $wishlist->photo ? asset('assets/images/products/'.$wishlist->photo):asset('assets/images/noimage.png') }}" alt=""> </a>
        </td>
        <td class="product-name"> <a href="{{ $wishlistProductUrl }}">{{  mb_strlen($wishlist->name,'UTF-8') > 35 ? mb_substr($wishlist->name,0,35,'UTF-8').'...' : $wishlist->name }}</a></td>
        <td class="product-price"> <span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">{{ $wishlist->showPrice() }}  <small>
            <del>
                {{ $wishlist->showPreviousPrice() }}
            </del>
        </small></bdi>
            </span>
        </td>
        <td class="product-stock-status">
            @if($wishlist->type == 'Physical')
            @if($wishlist->emptyStock())
            <div class="stock-availability out-stock">{{ ('Out Of Stock') }}</div>
            @else
            <div class="stock-availability in-stock text-bold">{{ ('In Stock') }}</div>
            @endif
            @endif
        </td>
        <td class="product-add-to-cart">
            <!-- Date added -->
            <button type="submit" id="addcrt" class="single_add_to_cart_button button alt single_add_to_cart_ajax_button">{{ __('Add to cart') }}</button>
            <!-- Remove from wishlist -->
        </td>
    <input type="hidden" id="product_price" value="{{ round($wishlist->vendorPrice() * $curr->value,2) }}">
    <input type="hidden" id="product_id" value="{{ $wishlist->id }}">
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
