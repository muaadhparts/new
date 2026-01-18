@php
    // Use eager-loaded accessor (avoids N+1 query)
    $bestProdMerchant = $cartItem->best_merchant_item;

    $bestProdUrl = $bestProdMerchant && $cartItem->slug
        ? route('front.catalog-item', ['slug' => $cartItem->slug, 'merchant_item_id' => $bestProdMerchant->id])
        : ($cartItem->slug ? route('front.catalog-item.legacy', $cartItem->slug) : '#');
@endphp

<div class="col">
    <div class="catalogItem type-catalogItem">
        <div class="catalogItem-wrapper">
            <div class="catalog-item-image">
                <a href="{{ $bestProdUrl }}" class="woocommerce-LoopProduct-link"><img src="{{ filter_var($cartItem->photo, FILTER_VALIDATE_URL) ? $cartItem->photo : ($cartItem->photo ? \Illuminate\Support\Facades\Storage::url($cartItem->photo) : asset('assets/images/noimage.png')) }}" alt="CatalogItem Image"></a>
                @if (round($cartItem->offPercentage() )>0)
                <div class="on-sale">-{{ round($cartItem->offPercentage() )}}%</div>
                @endif
            </div>
            <div class="catalogItem-info">
                <h3 class="catalogItem-name"><a href="{{ $bestProdUrl }}">{{ $cartItem->showName() }}</a></h3>
                <div class="catalogItem-price">
                    <div class="price">
                        <ins>{{ $cartItem->showPrice() }} </ins>
                        <del>{{ $cartItem->showPreviousPrice() }}</del>
                    </div>
                </div>
               
                <div class="shipping-feed-back">
                    <div class="star-rating">
                        <div class="rating-wrap">
                            <p><i class="fas fa-star"></i><span> {{ number_format($cartItem->catalog_reviews_avg_rating ?? 0, 1) }} ({{ $cartItem->catalog_reviews_count ?? 0 }})</span></p>
                        </div>
                    </div>
                </div>
                {{-- Shipping Quote Button --}}
                @if($bestProdMerchant)
                    <x-shipping-quote-button
                        :merchant-user-id="$bestProdMerchant->user_id"
                        :catalog-item-name="$cartItem->showName()"
                        class="mt-2"
                    />
                @endif
            </div>
        </div>
    </div>
</div>