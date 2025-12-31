<tbody class="favorite-items-wrapper">
    @foreach($favorites as $favorite)
    @php
        $favoriteMerchant = $favorite->effective_merchant_item
            ?? $favorite->getEffectiveMerchantItem();

        $productSlug = $favorite->catalogItem->slug ?? $favorite->slug ?? null;
        $favoriteProductUrl = $favoriteMerchant && $productSlug
            ? route('front.catalog-item', ['slug' => $productSlug, 'vendor_id' => $favoriteMerchant->user_id, 'merchant_item_id' => $favoriteMerchant->id])
            : ($productSlug ? route('front.catalog-item.legacy', $productSlug) : '#');
    @endphp

    @php
        $favoriteProduct = $favorite->catalogItem;
    @endphp
    @if($favoriteProduct)
    <tr id="favorite-row-{{ $favorite->id }}" data-row-id="{{ $favorite->id }}">
        <td class="product-remove">
            <div>
                <a href="{{ route('user-favorite-remove', $favorite->id) }}" class="remove favorite-remove remove_from_favorite" title="Remove this product">×</a>
            </div>
        </td>
        <td class="product-thumbnail">
            <a href="{{ $favoriteProductUrl }}"> <img src="{{ $favoriteProduct->photo ? \Illuminate\Support\Facades\Storage::url($favoriteProduct->photo) : asset('assets/images/noimage.png') }}" alt=""> </a>
        </td>
        <td class="product-name"> <a href="{{ $favoriteProductUrl }}">{{ mb_strlen($favoriteProduct->name,'UTF-8') > 35 ? mb_substr($favoriteProduct->name,0,35,'UTF-8').'...' : $favoriteProduct->name }}</a></td>
        <td class="product-price"> <span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">{{ $favoriteProduct->showPrice() }}  <small>
            <del>
                {{ $favoriteProduct->showPreviousPrice() }}
            </del>
        </small></bdi>
            </span>
        </td>
        <td class="product-stock-status">
            @if($favoriteProduct->type == 'Physical')
            @if($favoriteProduct->emptyStock())
            <div class="stock-availability out-stock">{{ __('Out Of Stock') }}</div>
            @else
            <div class="stock-availability in-stock text-bold">{{ __('In Stock') }}</div>
            @endif
            @endif
        </td>
        <td class="product-add-to-cart">
            <button type="submit" id="addcrt" class="single_add_to_cart_button button alt single_add_to_cart_ajax_button">{{ __('Add to cart') }}</button>
        </td>
    <input type="hidden" id="product_price" value="{{ round($favoriteProduct->vendorPrice() * $curr->value,2) }}">
    <input type="hidden" id="product_id" value="{{ $favoriteProduct->id }}">
    <input type="hidden" id="curr_pos" value="{{ $gs->currency_format }}">
    <input type="hidden" id="curr_sign" value="{{ $curr->sign }}">
    </tr>
    @endif
    @endforeach
</tbody>

<script>
    $('[data-bs-toggle="tooltip"]').tooltip({});
    $('[rel-toggle="tooltip"]').tooltip();
    $('[data-bs-toggle="tooltip"]').on('click', function () {
      $(this).tooltip('hide');
    })
    $('[rel-toggle="tooltip"]').on('click', function () {
      $(this).tooltip('hide');
    })
  </script>
