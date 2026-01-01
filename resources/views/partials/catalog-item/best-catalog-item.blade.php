@php
    // Use eager-loaded accessor (avoids N+1 query)
    $bestProdMerchant = $prod->best_merchant_item;

    $bestProdUrl = $bestProdMerchant && $prod->slug
        ? route('front.catalog-item', ['slug' => $prod->slug, 'merchant_id' => $bestProdMerchant->user_id, 'merchant_item_id' => $bestProdMerchant->id])
        : ($prod->slug ? route('front.catalog-item.legacy', $prod->slug) : '#');
@endphp

<div class="col">
    <div class="product type-product">
        <div class="product-wrapper">
            <div class="catalog-item-image">
                <a href="{{ $bestProdUrl }}" class="woocommerce-LoopProduct-link"><img src="{{ filter_var($prod->photo, FILTER_VALIDATE_URL) ? $prod->photo : ($prod->photo ? \Illuminate\Support\Facades\Storage::url($prod->photo) : asset('assets/images/noimage.png')) }}" alt="Product Image"></a>
                @if (round($prod->offPercentage() )>0)
                <div class="on-sale">-{{ round($prod->offPercentage() )}}%</div>
                @endif
            </div>
            <div class="product-info">
                <h3 class="product-title"><a href="{{ $bestProdUrl }}">{{ $prod->showName() }}</a></h3>
                <div class="product-price">
                    <div class="price">
                        <ins>{{ $prod->showPrice() }} </ins>
                        <del>{{ $prod->showPreviousPrice() }}</del>
                    </div>
                </div>
               
                <div class="shipping-feed-back">
                    <div class="star-rating">
                        <div class="rating-wrap">
                            <p><i class="fas fa-star"></i><span> {{ number_format($prod->catalog_reviews_avg_rating ?? 0, 1) }} ({{ $prod->catalog_reviews_count ?? 0 }})</span></p>
                        </div>
                    </div>
                </div>
                {{-- Shipping Quote Button --}}
                @if(($prod->type ?? 'Physical') == 'Physical' && $bestProdMerchant)
                    <x-shipping-quote-button
                        :merchant-user-id="$bestProdMerchant->user_id"
                        :catalog-item-name="$prod->showName()"
                        class="mt-2"
                    />
                @endif
            </div>
        </div>
    </div>
</div>