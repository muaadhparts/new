{{-- Pre-computed values: catalog_item_url, catalog_item_photo_url, catalog_item_name_truncated from FavoriteController (DATA_FLOW_POLICY) --}}
<tbody class="favorite-items-wrapper">
    @foreach($favorites as $favorite)
    @if($favorite->catalogItem)
    <tr id="favorite-row-{{ $favorite->id }}" data-row-id="{{ $favorite->id }}">
        <td class="catalogItem-remove">
            <div>
                <a href="{{ route('user-favorite-remove', $favorite->id) }}" class="remove favorite-remove remove_from_favorite" name="Remove this catalogItem">×</a>
            </div>
        </td>
        <td class="catalogItem-thumbnail">
            <a href="{{ $favorite->catalog_item_url }}"> <img src="{{ $favorite->catalog_item_photo_url }}" alt=""> </a>
        </td>
        <td class="catalogItem-name"> <a href="{{ $favorite->catalog_item_url }}">{{ $favorite->catalog_item_name_truncated }}</a></td>
        <td class="catalogItem-price"> <span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">{{ app(\App\Domain\Catalog\Services\CatalogItemDisplayService::class)->formatPrice($favorite->catalogItem, $favorite->catalogItem->lowest_price ?? 0) }}  <small>
            <del>
                {{ ($favorite->catalogItem->previous_price > 0 ? \App\Domain\Catalog\Models\CatalogItem::convertPrice($favorite->catalogItem->previous_price) : \'\') }}
            </del>
        </small></bdi>
            </span>
        </td>
        <td class="catalogItem-stock-status">
            @if(app(\App\Domain\Catalog\Services\CatalogItemMerchantService::class)->hasNoStock($favorite->catalogItem))
            <div class="stock-availability out-stock">{{ __('Out Of Stock') }}</div>
            @else
            <div class="stock-availability in-stock text-bold">{{ __('In Stock') }}</div>
            @endif
        </td>
        <td class="catalogItem-add-to-cart">
            @if($favorite->effective_merchant_item)
                <button type="button" class="m-cart-add button" data-merchant-item-id="{{ $favorite->effective_merchant_item->id }}">
                    <i class="fas fa-cart-plus"></i> {{ __('Add to cart') }}
                </button>
            @else
                <span class="text-muted">{{ __('Not available') }}</span>
            @endif
        </td>
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
