@php /** @var \App\Models\Product $prod */ @endphp
@php
  $mp = $prod->merchantProducts()->where('status',1)->orderBy('price')->first();
  $vendorId = optional($mp)->user_id;
  $thumb = $prod->thumbnail ? asset('assets/images/thumbnails/'.$prod->thumbnail) : asset('assets/images/noimage.png');
  $hasPrev = $mp && $mp->previous_price;
@endphp

<a href="{{ $vendorId ? route('front.product', ['slug' => $prod->slug, 'user' => $vendorId]) : 'javascript:;' }}" class="single-product-flas">
    <div class="img">
        <img src="{{ $thumb }}" alt="">
        @if(!empty($prod->features))
            <div class="sell-area">
                @foreach($prod->features as $key => $data1)
                    <span class="sale" style="background-color:{{ $prod->colors[$key] }}">
                      {{ $prod->features[$key] }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="content">
        <h4 class="name"><x-product-name :product="$prod" :vendor-id="$vendorId" target="_self" /></h4>

        <ul class="stars d-flex">
            <div class="ratings">
                <div class="empty-stars"></div>
                <div class="full-stars" style="width:{{ number_format($prod->ratings_avg_rating,1) }}%"></div>
            </div>
            <li class="ml-2"><span>({{ $prod->ratings_count }})</span></li>
        </ul>

        <div class="price">
            <span class="new-price">{{ $mp ? $mp->showPrice() : $prod->showPrice() }}</span>
            <small class="old-price"><del>{{ $hasPrev ? \App\Models\Product::convertPrice($mp->previous_price) : $prod->showPreviousPrice() }}</del></small>
        </div>

        <ul class="action-meta">
            {{-- Wishlist --}}
            @if(Auth::check())
                <li>
                    <span class="wish add-to-wish"
                          data-href="{{ $vendorId ? route('user-wishlist-add', ['id'=>$prod->id,'user'=>$vendorId]) : route('user-wishlist-add',$prod->id) }}"
                          data-toggle="tooltip" data-placement="top" title="{{ __('Wish') }}">
                        <i class="far fa-heart"></i>
                    </span>
                </li>
            @else
                <li>
                    <span class="wish add-to-wish" data-toggle="modal" data-target="#user-login"
                          rel-toggle="tooltip" title="{{ __('Wish') }}" data-placement="top">
                        <i class="far fa-heart"></i>
                    </span>
                </li>
            @endif

            {{-- Add to Cart --}}
            @if($prod->product_type == "affiliate")
                <li>
                    <span class="cart-btn affilate-btn" data-href="{{ $prod->affiliate_link }}"
                          data-toggle="tooltip" data-placement="top" title="{{ __('Buy Now') }}">
                        <i class="icofont-cart"></i>
                    </span>
                </li>
            @else
                @if($prod->emptyStock())
                    <li>
                        <span class="cart-btn cart-out-of-stock" data-toggle="tooltip" data-placement="top"
                              title="{{ __('Out Of Stock') }}"><i class="icofont-close-circled"></i></span>
                    </li>
                @else
                    @if ($prod->type != 'Listing')
                        <li>
                            <span class="cart-btn add-to-cart add-to-cart-btn"
                                  data-href="{{ $vendorId ? route('product.cart.add', ['product'=>$prod->id,'user'=>$vendorId]) : route('product.cart.add',$prod->id) }}"
                                  title="{{ __('Add To Cart') }}">
                                <i class="icofont-cart"></i>
                            </span>
                        </li>
                        <li>
                            <span class="cart-btn quick-view"
                                  data-href="{{ route('product.quick', $prod->id) }}"
                                  data-user="{{ $vendorId ?? '' }}"
                                  rel-toggle="tooltip" data-placement="top"
                                  title="{{ __('Quick View') }}" data-toggle="modal" data-target="#quickview">
                                <i class="fas fa-eye"></i>
                            </span>
                        </li>
                    @endif
                @endif
            @endif

            {{-- Compare --}}
            @if ($prod->type != 'Listing')
                <li>
                    <span class="cart-btn"
                          data-href="{{ $vendorId ? route('product.compare.add', ['id'=>$prod->id,'user'=>$vendorId]) : route('product.compare.add',$prod->id) }}"
                          rel-toggle="tooltip" data-placement="top" title="{{ __('Compare') }}">
                        <i class="fas fa-random"></i>
                    </span>
                </li>
            @endif
        </ul>
    </div>
</a>
