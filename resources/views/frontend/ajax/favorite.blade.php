{{-- Uses FavoriteItemDTO from FavoriteController (Clean Architecture) --}}
<tbody class="favorite-items-wrapper">
    @foreach($favorites as $favorite)
    <tr id="favorite-row-{{ $favorite->favoriteId }}" data-row-id="{{ $favorite->favoriteId }}">
        <td class="catalogItem-remove">
            <div>
                <a href="{{ route('user-favorite-remove', $favorite->favoriteId) }}" class="remove favorite-remove remove_from_favorite" name="Remove this catalogItem">×</a>
            </div>
        </td>
        <td class="catalogItem-thumbnail">
            <a href="{{ $favorite->catalogItemUrl }}"> <img src="{{ $favorite->photoUrl }}" alt=""> </a>
        </td>
        <td class="catalogItem-name"> <a href="{{ $favorite->catalogItemUrl }}">{{ $favorite->name }}</a></td>
        <td class="catalogItem-price">
            <span class="woocommerce-Price-amount amount">
                <bdi>
                    <span class="woocommerce-Price-currencySymbol">{{ $favorite->priceFormatted }}</span>
                    @if($favorite->previousPriceFormatted)
                        <small>
                            <del>{{ $favorite->previousPriceFormatted }}</del>
                        </small>
                    @endif
                </bdi>
            </span>
        </td>
        <td class="catalogItem-stock-status">
            @if($favorite->hasStock)
                <div class="stock-availability in-stock text-bold">{{ __('In Stock') }}</div>
            @else
                <div class="stock-availability out-stock">{{ __('Out Of Stock') }}</div>
            @endif
        </td>
        <td class="catalogItem-add-to-cart">
            @if($favorite->merchantItemId)
                <button type="button" class="m-cart-add button" data-merchant-item-id="{{ $favorite->merchantItemId }}">
                    <i class="fas fa-cart-plus"></i> {{ __('Add to cart') }}
                </button>
            @else
                <button type="button" class="catalog-offers-btn button" data-catalog-item-id="{{ $favorite->catalogItemId }}" data-part-number="{{ $favorite->partNumber }}">
                    <i class="fas fa-tags"></i> {{ __('View Offers') }}
                </button>
            @endif
        </td>
    </tr>
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
