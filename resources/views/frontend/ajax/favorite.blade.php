<tbody class="favorite-items-wrapper">
    @foreach($favorites as $favorite)
    @php
        $favoriteMerchant = $favorite->effective_merchant_item
            ?? $favorite->getEffectiveMerchantItem();

        $catalogItemSlug = $favorite->catalogItem->slug ?? $favorite->slug ?? null;
        $favoriteCatalogItemUrl = $favoriteMerchant && $catalogItemSlug
            ? route('front.catalog-item', ['slug' => $catalogItemSlug, 'merchant_item_id' => $favoriteMerchant->id])
            : ($catalogItemSlug ? route('front.catalog-item.legacy', $catalogItemSlug) : '#');
    @endphp

    @php
        $favoriteCatalogItem = $favorite->catalogItem;
    @endphp
    @if($favoriteCatalogItem)
    <tr id="favorite-row-{{ $favorite->id }}" data-row-id="{{ $favorite->id }}">
        <td class="catalogItem-remove">
            <div>
                <a href="{{ route('user-favorite-remove', $favorite->id) }}" class="remove favorite-remove remove_from_favorite" name="Remove this catalogItem">×</a>
            </div>
        </td>
        <td class="catalogItem-thumbnail">
            <a href="{{ $favoriteCatalogItemUrl }}"> <img src="{{ $favoriteCatalogItem->photo ? \Illuminate\Support\Facades\Storage::url($favoriteCatalogItem->photo) : asset('assets/images/noimage.png') }}" alt=""> </a>
        </td>
        <td class="catalogItem-name"> <a href="{{ $favoriteCatalogItemUrl }}">{{ mb_strlen($favoriteCatalogItem->name,'UTF-8') > 35 ? mb_substr($favoriteCatalogItem->name,0,35,'UTF-8').'...' : $favoriteCatalogItem->name }}</a></td>
        <td class="catalogItem-price"> <span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">{{ $favoriteCatalogItem->showPrice() }}  <small>
            <del>
                {{ $favoriteCatalogItem->showPreviousPrice() }}
            </del>
        </small></bdi>
            </span>
        </td>
        <td class="catalogItem-stock-status">
            @if($favoriteCatalogItem->emptyStock())
            <div class="stock-availability out-stock">{{ __('Out Of Stock') }}</div>
            @else
            <div class="stock-availability in-stock text-bold">{{ __('In Stock') }}</div>
            @endif
        </td>
        <td class="catalogItem-add-to-cart">
            @if($favoriteMerchant)
                <button type="button" class="m-cart-add button" data-merchant-item-id="{{ $favoriteMerchant->id }}">
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
