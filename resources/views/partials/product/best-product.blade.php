@php
  // Check if $prod is a MerchantProduct or Product model
  $isMerchantProduct = $prod instanceof \App\Models\MerchantProduct;

  if ($isMerchantProduct) {
      $merchantProductId = $prod->id;
      $vendorId = $prod->user_id;
      $productSlug = $prod->product->slug ?? $prod->slug;
  } else {
      $mp = $prod->merchantProducts()->where('status', 1)->orderBy('price')->first();
      $merchantProductId = $mp->id ?? null;
      $vendorId = $mp->user_id ?? null;
      $productSlug = $prod->slug;
  }

  $productUrl = ($merchantProductId && $vendorId)
      ? route('front.product', ['slug' => $productSlug, 'vendor_id' => $vendorId, 'merchant_product_id' => $merchantProductId])
      : 'javascript:;';
@endphp

<div class="col">
    <div class="product type-product">
        <div class="product-wrapper">
            <div class="product-image">
                <a href="{{ $productUrl }}" class="MUAADH-LoopProduct-link"><img src="{{ $prod->thumbnail ? asset('assets/images/thumbnails/'.$prod->thumbnail):asset('assets/images/noimage.png') }}" alt="Product Image"></a>
                @if (round($prod->offPercentage() )>0)
                <div class="on-sale">-{{ round($prod->offPercentage() )}}%</div>
                @endif
            </div>
            <div class="product-info">
                <h3 class="product-title"><a href="{{ $productUrl }}"> <x-product-name :product="$prod" :vendor-id="$vendorId" :merchant-product-id="$merchantProductId" target="_self" /></a></h3>
                <div class="product-price">
                    <div class="price">
                        <ins>{{ $prod->showPrice() }} </ins>
                        <del>{{ $prod->showPreviousPrice() }}</del>
                    </div>
                </div>
               
                <div class="shipping-feed-back">
                    <div class="star-rating">
                        <div class="rating-wrap">
                            <p><i class="fas fa-star"></i><span> {{ number_format($prod->ratings_avg_rating,1) }} ({{ $prod->ratings_count }})</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>