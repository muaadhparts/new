@php
    $bestProdMerchant = $prod->merchantProducts()
        ->where('status', 1)
        ->whereHas('user', function ($user) {
            $user->where('is_vendor', 2);
        })
        ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
        ->orderBy('price')
        ->first();

    $bestProdUrl = $bestProdMerchant && $prod->slug
        ? route('front.product', ['slug' => $prod->slug, 'vendor_id' => $bestProdMerchant->user_id, 'merchant_product_id' => $bestProdMerchant->id])
        : ($prod->slug ? route('front.product.legacy', $prod->slug) : '#');
@endphp

<div class="col">
    <div class="product type-product">
        <div class="product-wrapper">
            <div class="product-image">
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
                            <p><i class="fas fa-star"></i><span> {{ number_format($prod->ratings_avg_rating ?? 0, 1) }} ({{ $prod->ratings_count ?? 0 }})</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>