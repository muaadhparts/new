<tbody class="wishlist-items-wrapper">
    @foreach($wishlists as $wishlist)
    @php
        // Use effective_merchant_product from controller, or fetch from product
        $wishlistMerchant = $wishlist->effective_merchant_product
            ?? ($wishlist->product ? $wishlist->product->merchantProducts()
                ->where('status', 1)
                ->whereHas('user', function ($user) {
                    $user->where('is_vendor', 2);
                })
                ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
                ->orderBy('price')
                ->first() : null);

        $productSlug = $wishlist->product->slug ?? $wishlist->slug ?? null;
        $wishlistProductUrl = $wishlistMerchant && $productSlug
            ? route('front.product', ['slug' => $productSlug, 'vendor_id' => $wishlistMerchant->user_id, 'merchant_product_id' => $wishlistMerchant->id])
            : ($productSlug ? route('front.product.legacy', $productSlug) : '#');
    @endphp

    @php
        $wishlistProduct = $wishlist->product;
    @endphp
    @if($wishlistProduct)
    <tr id="yith-wcwl-row-{{ $wishlist->id }}" data-row-id="{{ $wishlist->id }}">
        <td class="product-remove">
            <div>
                <a href="{{ route('user-wishlist-remove', $wishlist->id) }}" class="remove wishlist-remove remove_from_wishlist" title="Remove this product">×</a>
            </div>
        </td>
        <td class="product-thumbnail">
            <a href="{{ $wishlistProductUrl }}"> <img src="{{ $wishlistProduct->photo ? \Illuminate\Support\Facades\Storage::url($wishlistProduct->photo) : asset('assets/images/noimage.png') }}" alt=""> </a>
        </td>
        <td class="product-name"> <a href="{{ $wishlistProductUrl }}">{{ mb_strlen($wishlistProduct->name,'UTF-8') > 35 ? mb_substr($wishlistProduct->name,0,35,'UTF-8').'...' : $wishlistProduct->name }}</a></td>
        <td class="product-price"> <span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">{{ $wishlistProduct->showPrice() }}  <small>
            <del>
                {{ $wishlistProduct->showPreviousPrice() }}
            </del>
        </small></bdi>
            </span>
        </td>
        <td class="product-stock-status">
            @if($wishlistProduct->type == 'Physical')
            @if($wishlistProduct->emptyStock())
            <div class="stock-availability out-stock">{{ __('Out Of Stock') }}</div>
            @else
            <div class="stock-availability in-stock text-bold">{{ __('In Stock') }}</div>
            @endif
            @endif
        </td>
        <td class="product-add-to-cart">
            <!-- Date added -->
            <button type="submit" id="addcrt" class="single_add_to_cart_button button alt single_add_to_cart_ajax_button">{{ __('Add to cart') }}</button>
            <!-- Remove from wishlist -->
        </td>
    <input type="hidden" id="product_price" value="{{ round($wishlistProduct->vendorPrice() * $curr->value,2) }}">
    <input type="hidden" id="product_id" value="{{ $wishlistProduct->id }}">
    <input type="hidden" id="curr_pos" value="{{ $gs->currency_format }}">
    <input type="hidden" id="curr_sign" value="{{ $curr->sign }}">
    </tr>
    @endif
    @endforeach
</tbody>

<script>

    // Tooltip Section

    $('[data-bs-toggle="tooltip"]').tooltip({});

    $('[rel-toggle="tooltip"]').tooltip();

    $('[data-bs-toggle="tooltip"]').on('click', function () {
      $(this).tooltip('hide');
    })


    $('[rel-toggle="tooltip"]').on('click', function () {
      $(this).tooltip('hide');
    })

    // Tooltip Section Ends
  </script>
