@if (Session::has('view'))
  @if (Session::get('view') == 'list-view')
    <div class="row row-cols-xxl-2 row-cols-md-2 row-cols-1 g-3 product-style-1 shop-list product-list e-bg-light e-title-hover-primary e-hover-image-zoom">
      @foreach($vprods as $product)
        <div class="col">
          <div class="product type-product">
            <div class="product-wrapper">
              <div class="product-image">
                <a href="{{ route('front.product.user', ['slug' => $product->slug, 'user' => $product->user_id]) }}" class="woocommerce-LoopProduct-link">
                  <img src="{{ $product->thumbnail ? asset('assets/images/thumbnails/' . $product->thumbnail) : asset('assets/images/noimage.png') }}" alt="Product Image">
                </a>
                <div class="hover-area">
                  @if($product->product_type == "affiliate")
                    <div class="cart-button">
                      <a href="javascript:;" data-href="{{ $product->affiliate_link }}" class="button add_to_cart_button affilate-btn" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="{{ __('Add To Cart') }}"></a>
                    </div>
                  @else
                    @if($product->emptyStock())
                      <div class="cart-button">
                        <a class="cart-out-of-stock button add_to_cart_button" href="#" title="{{ __('Out Of Stock') }}"><i class="flaticon-cancel flat-mini mx-auto"></i></a>
                      </div>
                    @else
                      @if ($product->type != 'Listing')
                        <div class="cart-button">
                          <a href="javascript:;" data-bs-toggle="modal"
                             data-cross-href="{{ route('front.show.cross.product', $product->id) }}"
                             {{ $product->cross_products ? 'data-bs-target=#exampleModal' : '' }}
                             data-href="{{ route('product.cart.add', ['product' => $product->id, 'user' => $product->user_id]) }}"
                             class="add-cart button add_to_cart_button {{ $product->cross_products ? 'view_cross_product' : '' }}"
                             data-bs-toggle="tooltip" data-bs-placement="right" aria-label="{{ __('Add To Cart') }}"></a>
                        </div>
                      @endif
                    @endif
                  @endif

                  @if(Auth::check())
                    <div class="wishlist-button">
                      <a class="add_to_wishlist new button add_to_cart_button" id="add-to-wish" href="javascript:;"
                         data-href="{{ route('user-wishlist-add', ['id' => $product->id, 'user' => $product->user_id]) }}"
                         data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Add to Wishlist">{{ __('Wishlist') }}</a>
                    </div>
                  @else
                    <div class="wishlist-button">
                      <a class="add_to_wishlist button add_to_cart_button" href="{{ route('user.login') }}" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Add to Wishlist">{{ __('Wishlist') }}</a>
                    </div>
                  @endif

                  @if ($product->type != 'Listing')
                    <div class="compare-button">
                      <a class="compare button button add_to_cart_button" href="javascrit:;"
                         data-href="{{ route('product.compare.add', ['id' => $product->id, 'user' => $product->user_id]) }}"
                         data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Compare">{{ __('Compare') }}</a>
                    </div>
                  @endif
                </div>
              </div>

              <div class="product-info">
                <h3 class="product-title">
                  <a href="{{ route('front.product.user', ['slug' => $product->slug, 'user' => $product->user_id]) }}"><x-product-name :product="$product" :vendor-id="$product->user_id" target="_self" /></a>
                </h3>
                <div class="product-price">
                  <div class="price">
                    <ins>{{ $product->showPrice() }}</ins>
                    <del>{{ $product->showPreviousPrice() }}</del>
                  </div>
                </div>
                <div class="shipping-feed-back">
                  <div class="star-rating">
                    <div class="rating-wrap">
                      <p><i class="fas fa-star"></i><span> {{ number_format($product->ratings_avg_rating, 1) }} ({{ $product->ratings_count }})</span></p>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      @endforeach
    </div>
  @else
    <div class="row row-cols-xl-4 row-cols-md-3 row-cols-sm-2 row-cols-1 product-style-1 e-title-hover-primary e-image-bg-light e-hover-image-zoom e-info-center">
      @foreach($vprods as $product)
        <div class="col">
          <div class="product type-product">
            <div class="product-wrapper">
              <div class="product-image">
                <a href="{{ route('front.product.user', ['slug' => $product->slug, 'user' => $product->user_id]) }}" class="woocommerce-LoopProduct-link">
                  <img src="{{ $product->thumbnail ? asset('assets/images/thumbnails/' . $product->thumbnail) : asset('assets/images/noimage.png') }}" alt="Product Image">
                </a>
                <div class="hover-area">
                  @if($product->product_type == "affiliate")
                    <div class="cart-button">
                      <a href="javascript:;" data-href="{{ $product->affiliate_link }}" class="button add_to_cart_button affilate-btn" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="{{ __('Add To Cart') }}"></a>
                    </div>
                  @else
                    @if($product->emptyStock())
                      <div class="cart-button">
                        <a class="cart-out-of-stock button add_to_cart_button" href="#" title="{{ __('Out Of Stock') }}"><i class="flaticon-cancel flat-mini mx-auto"></i></a>
                      </div>
                    @else
                      @if ($product->type != 'Listing')
                        <div class="cart-button">
                          <a href="javascript:;" data-bs-toggle="modal"
                             data-cross-href="{{ route('front.show.cross.product', $product->id) }}"
                             {{ $product->cross_products ? 'data-bs-target=#exampleModal' : '' }}
                             data-href="{{ route('product.cart.add', ['product' => $product->id, 'user' => $product->user_id]) }}"
                             class="add-cart button add_to_cart_button {{ $product->cross_products ? 'view_cross_product' : '' }}"
                             data-bs-toggle="tooltip" data-bs-placement="right" aria-label="{{ __('Add To Cart') }}"></a>
                        </div>
                      @endif
                    @endif
                  @endif

                  @if(Auth::check())
                    <div class="wishlist-button">
                      <a class="add_to_wishlist new button add_to_cart_button" id="add-to-wish" href="javascript:;"
                         data-href="{{ route('user-wishlist-add', ['id' => $product->id, 'user' => $product->user_id]) }}"
                         data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Add to Wishlist">{{ __('Wishlist') }}</a>
                    </div>
                  @else
                    <div class="wishlist-button">
                      <a class="add_to_wishlist button add_to_cart_button" href="{{ route('user.login') }}"
                         data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Add to Wishlist">{{ __('Wishlist') }}</a>
                    </div>
                  @endif

                  @if ($product->type != 'Listing')
                    <div class="compare-button">
                      <a class="compare button button add_to_cart_button" href="javascrit:;"
                         data-href="{{ route('product.compare.add', ['id' => $product->id, 'user' => $product->user_id]) }}"
                         data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Compare">{{ __('Compare') }}</a>
                    </div>
                  @endif
                </div>
              </div>

              <div class="product-info">
                <h3 class="product-title">
                  <a href="{{ route('front.product.user', ['slug' => $product->slug, 'user' => $product->user_id]) }}"><x-product-name :product="$product" :vendor-id="$product->user_id" target="_self" /></a>
                </h3>
                <div class="product-price">
                  <div class="price">
                    <ins>{{ $product->showPrice() }}</ins>
                    <del>{{ $product->showPreviousPrice() }}</del>
                  </div>
                </div>
                <div class="shipping-feed-back">
                  <div class="star-rating">
                    <div class="rating-wrap">
                      <p><i class="fas fa-star"></i><span> {{ number_format($product->ratings_avg_rating, 1) }} ({{ $product->ratings_count }})</span></p>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif
@else
  <div class="row row-cols-xl-4 row-cols-md-3 row-cols-sm-2 row-cols-1 product-style-1 e-title-hover-primary e-image-bg-light e-hover-image-zoom e-info-center">
    @foreach($vprods as $product)
      <div class="col">
        <div class="product type-product">
          <div class="product-wrapper">
            <div class="product-image">
              <a href="{{ route('front.product.user', ['slug' => $product->slug, 'user' => $product->user_id]) }}" class="woocommerce-LoopProduct-link">
                <img src="{{ $product->thumbnail ? asset('assets/images/thumbnails/' . $product->thumbnail) : asset('assets/images/noimage.png') }}" alt="Product Image">
              </a>
              <div class="hover-area">
                @if($product->product_type == "affiliate")
                  <div class="cart-button">
                    <a href="javascript:;" data-href="{{ $product->affiliate_link }}" class="button add_to_cart_button affilate-btn" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="{{ __('Add To Cart') }}"></a>
                  </div>
                @else
                  @if($product->emptyStock())
                    <div class="cart-button">
                      <a class="cart-out-of-stock button add_to_cart_button" href="#" title="{{ __('Out Of Stock') }}"><i class="flaticon-cancel flat-mini mx-auto"></i></a>
                    </div>
                  @else
                    @if ($product->type != 'Listing')
                      <div class="cart-button">
                        <a href="javascript:;" data-bs-toggle="modal"
                           data-cross-href="{{ route('front.show.cross.product', $product->id) }}"
                           {{ $product->cross_products ? 'data-bs-target=#exampleModal' : '' }}
                           data-href="{{ route('product.cart.add', ['product' => $product->id, 'user' => $product->user_id]) }}"
                           class="add-cart button add_to_cart_button {{ $product->cross_products ? 'view_cross_product' : '' }}"
                           data-bs-toggle="tooltip" data-bs-placement="right" aria-label="{{ __('Add To Cart') }}"></a>
                      </div>
                    @endif
                  @endif
                @endif

                @if(Auth::check())
                  <div class="wishlist-button">
                    <a class="add_to_wishlist new button add_to_cart_button" id="add-to-wish" href="javascript:;"
                       data-href="{{ route('user-wishlist-add', ['id' => $product->id, 'user' => $product->user_id]) }}"
                       data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Add to Wishlist">{{ __('Wishlist') }}</a>
                  </div>
                @else
                  <div class="wishlist-button">
                    <a class="add_to_wishlist button add_to_cart_button" href="{{ route('user.login') }}" data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Add to Wishlist">{{ __('Wishlist') }}</a>
                  </div>
                @endif

                @if ($product->type != 'Listing')
                  <div class="compare-button">
                    <a class="compare button button add_to_cart_button" href="javascrit:;"
                       data-href="{{ route('product.compare.add', ['id' => $product->id, 'user' => $product->user_id]) }}"
                       data-bs-toggle="tooltip" data-bs-placement="right" aria-label="Compare">{{ __('Compare') }}</a>
                  </div>
                @endif
              </div>
            </div>

            <div class="product-info">
              <h3 class="product-title">
                <a href="{{ route('front.product.user', ['slug' => $product->slug, 'user' => $product->user_id]) }}">{{ $product->showName() }}</a>
              </h3>
              <div class="product-price">
                <div class="price">
                  <ins>{{ $product->showPrice() }}</ins>
                  <del>{{ $product->showPreviousPrice() }}</del>
                </div>
              </div>
              <div class="shipping-feed-back">
                <div class="star-rating">
                  <div class="rating-wrap">
                    <p><i class="fas fa-star"></i><span> {{ number_format($product->ratings_avg_rating, 1) }} ({{ $product->ratings_count }})</span></p>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    @endforeach
  </div>
@endif
