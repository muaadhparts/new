@if (Session::has('view'))
   @if (Session::get('view') == 'list-view')
      <div
        class="row row-cols-xxl-2 row-cols-md-2 row-cols-1 g-3 catalogItem-style-1 shop-list catalogItem-list e-bg-light e-name-hover-primary e-hover-image-zoom">
        @foreach($prods as $catalogItem)
         @php
            $catalogItemUrl = $catalogItem->getCatalogItemUrl();
            // affiliate_link is now on merchant_items
            $bestMerchantItem = $catalogItem->best_merchant_item ?? null;
         @endphp
         <div class="col">
          <div class="catalogItem type-catalogItem">
            <div class="catalogItem-wrapper">
            <div class="catalog-item-image">
               <a href="{{ $catalogItemUrl }}" class="woocommerce-LoopProduct-link"><img
                 src="{{ filter_var($catalogItem->photo, FILTER_VALIDATE_URL) ? $catalogItem->photo : ($catalogItem->photo ? \Illuminate\Support\Facades\Storage::url($catalogItem->photo) : asset('assets/images/noimage.png')) }}"
                 alt="CatalogItem Image"></a>
               @if (round($catalogItem->offPercentage()) > 0)
               <div class="on-sale">- {{ round($catalogItem->offPercentage())}}%</div>
            @endif
               <div class="hover-area">
               {{-- item_type and affiliate_link are now on merchant_items --}}
               @if($bestMerchantItem && $bestMerchantItem->item_type == "affiliate" && $bestMerchantItem->affiliate_link)
               <div class="cart-button buynow">
                <a class="affilate-btn button add_to_cart_button" href="javascript:;"
                 data-href="{{ $bestMerchantItem->affiliate_link }}" data-bs-toggle="tooltip"
                 data-bs-placement="right" name="" data-bs-original-name="{{ __('Buy Now') }}"
                 aria-label="{{ __('Buy Now') }}"></a>
               </div>
            @else
               @if(!$bestMerchantItem || $catalogItem->emptyStock())
               <div class="closed">
               <a class="cart-out-of-stock button add_to_cart_button" href="#" name="{{ __('Out Of Stock') }}"><i
                 class="flaticon-cancel flat-mini mx-auto"></i></a>
               </div>
            @else
               <div class="cart-button">
               <a href="javascript:;" data-bs-toggle="modal"
               class="m-cart-add button add_to_cart_button"
               data-merchant-item-id="{{ $bestMerchantItem->id }}"
               data-catalog-item-id="{{ $catalogItem->id }}"
               data-bs-toggle="tooltip" data-bs-placement="right" name=""
               data-bs-original-name="{{ __('Add To Cart') }}" aria-label="{{ __('Add To Cart') }}"></a>
               </div>
            @endif
            @endif
               @if(Auth::check())
               <div class="favorite-button">
                <a class="add_to_favorite  new button add_to_cart_button" id="add-to-wish" href="javascript:;"
                 data-href="{{ route('user-favorite-add', $catalogItem->id) }}" data-bs-toggle="tooltip"
                 data-bs-placement="right" name="{{ __('Favorites') }}" data-bs-original-name="Add to Favorites"
                 aria-label="Add to Favorites">{{ __('Favorites') }}</a>
               </div>
            @else
               <div class="favorite-button">
                <a class="add_to_favorite button add_to_cart_button" href="{{ route('user.login') }}"
                 data-bs-toggle="tooltip" data-bs-placement="right" name="{{ __('Favorites') }}"
                 data-bs-original-name="Add to Favorites" aria-label="Add to Favorites">{{ __('Favorites') }}</a>
               </div>
            @endif

               <div class="compare-button">
                <a class="compare button button add_to_cart_button"
                 data-href="{{ route('catalog-item.compare.add', $catalogItem->id) }}" href="javascrit:;"
                 data-bs-toggle="tooltip" data-bs-placement="right" name="{{ __('Compare') }}"
                 data-bs-original-name="Compare" aria-label="Compare">{{ __('Compare') }}</a>
               </div>
               </div>
            </div>
            <div class="catalogItem-info">
               <h3 class="catalogItem-name"><a
                 href="{{ $catalogItemUrl }}">{{ $catalogItem->showName() }}</a></h3>
               <div class="catalogItem-price">
               <div class="price">

                 <ins>{{ $catalogItem->setCurrency() }}</ins>
                 <del>{{ $catalogItem->showPreviousPrice() }}</del>
               </div>
               </div>
               <div class="shipping-feed-back">
               <div class="star-rating">
                 <div class="rating-wrap">
                  <p><i class="fas fa-star"></i><span> {{ number_format($catalogItem->catalog_reviews_avg_rating ?? 0, 1) }}
                     ({{ $catalogItem->catalog_reviews_count }})</span></p>
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
      <div
        class="row row-cols-xl-4 row-cols-md-3 row-cols-sm-2 row-cols-1 catalogItem-style-1 e-name-hover-primary e-image-bg-light e-hover-image-zoom e-info-center">
        @foreach($prods as $catalogItem)
         @php
            $catalogItemUrl = $catalogItem->getCatalogItemUrl();
            // affiliate_link is now on merchant_items
            $bestMerchantItem = $catalogItem->best_merchant_item ?? null;
         @endphp
         <div class="col">
          <div class="catalogItem type-catalogItem">
            <div class="catalogItem-wrapper">
            <div class="catalog-item-image">
               <a href="{{ $catalogItemUrl }}" class="woocommerce-LoopProduct-link"><img
                 src="{{ filter_var($catalogItem->photo, FILTER_VALIDATE_URL) ? $catalogItem->photo : ($catalogItem->photo ? \Illuminate\Support\Facades\Storage::url($catalogItem->photo) : asset('assets/images/noimage.png')) }}"
                 alt="CatalogItem Image"></a>
               @if (round($catalogItem->offPercentage()) > 0)
               <div class="on-sale">- {{ round($catalogItem->offPercentage())}}%</div>
            @endif
               <div class="hover-area">
               {{-- item_type and affiliate_link are now on merchant_items --}}
               @if($bestMerchantItem && $bestMerchantItem->item_type == "affiliate" && $bestMerchantItem->affiliate_link)
               <div class="cart-button buynow">
                <a class="affilate-btn button add_to_cart_button" href="javascript:;"
                 data-href="{{ $bestMerchantItem->affiliate_link }}" data-bs-toggle="tooltip"
                 data-bs-placement="right" name="" data-bs-original-name="{{ __('Buy Now') }}"
                 aria-label="{{ __('Buy Now') }}"></a>
               </div>
            @else
               @if(!$bestMerchantItem || $catalogItem->emptyStock())
               <div class="closed">
               <a class="cart-out-of-stock button add_to_cart_button" href="#" name="{{ __('Out Of Stock') }}"><i
                 class="flaticon-cancel flat-mini mx-auto"></i></a>
               </div>
            @else
               <div class="cart-button">
               <a href="javascript:;" data-bs-toggle="modal"
               class="m-cart-add button add_to_cart_button"
               data-merchant-item-id="{{ $bestMerchantItem->id }}"
               data-catalog-item-id="{{ $catalogItem->id }}"
               data-bs-toggle="tooltip" data-bs-placement="right" name=""
               data-bs-original-name="{{ __('Add To Cart') }}" aria-label="{{ __('Add To Cart') }}"></a>
               </div>
            @endif
            @endif
               @if(Auth::check())
               <div class="favorite-button">
                <a class="add_to_favorite  new button add_to_cart_button" id="add-to-wish" href="javascript:;"
                 data-href="{{ route('user-favorite-add', $catalogItem->id) }}" data-bs-toggle="tooltip"
                 data-bs-placement="right" name="" data-bs-original-name="Add to Favorites"
                 aria-label="Add to Favorites">{{ __('Favorites') }}</a>
               </div>
            @else
               <div class="favorite-button">
                <a class="add_to_favorite button add_to_cart_button" href="{{ route('user.login') }}"
                 data-bs-toggle="tooltip" data-bs-placement="right" name=""
                 data-bs-original-name="Add to Favorites" aria-label="Add to Favorites">{{ __('Favorites') }}</a>
               </div>
            @endif
               <div class="compare-button">
                <a class="compare button button add_to_cart_button"
                 data-href="{{ route('catalog-item.compare.add', $catalogItem->id) }}" href="javascrit:;"
                 data-bs-toggle="tooltip" data-bs-placement="right" name="" data-bs-original-name="Compare"
                 aria-label="Compare">{{ __('Compare') }}</a>
               </div>
               </div>




            </div>
            <div class="catalogItem-info">
               <h3 class="catalogItem-name"><a
                 href="{{ $catalogItemUrl }}">{{ $catalogItem->showName() }}</a></h3>
               <div class="catalogItem-price">
               <div class="price">
                 <ins>{{ $catalogItem->setCurrency() }}</ins>
                 <del>{{ $catalogItem->showPreviousPrice() }}</del>
               </div>
               </div>
               <div class="shipping-feed-back">
               <div class="star-rating">
                 <div class="rating-wrap">
                  <p><i class="fas fa-star"></i><span> {{ number_format($catalogItem->catalog_reviews_avg_rating ?? 0, 1) }}
                     ({{ $catalogItem->catalog_reviews_count }})</span></p>
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
   <div
      class="row row-cols-xl-4 row-cols-md-3 row-cols-sm-2 row-cols-1 catalogItem-style-1 e-name-hover-primary e-image-bg-light e-hover-image-zoom e-info-center">
      @foreach($prods as $catalogItem)
        @php
            $catalogItemUrl = $catalogItem->getCatalogItemUrl();
            // affiliate_link is now on merchant_items
            $bestMerchantItem = $catalogItem->best_merchant_item ?? null;
        @endphp
        <div class="col">
         <div class="catalogItem type-catalogItem">
           <div class="catalogItem-wrapper">
            <div class="catalog-item-image">
               <a href="{{ $catalogItemUrl }}" class="woocommerce-LoopProduct-link"><img
                  src="{{ filter_var($catalogItem->photo, FILTER_VALIDATE_URL) ? $catalogItem->photo : ($catalogItem->photo ? \Illuminate\Support\Facades\Storage::url($catalogItem->photo) : asset('assets/images/noimage.png')) }}"
                  alt="CatalogItem Image"></a>
               @if (round($catalogItem->offPercentage()) > 0)
               <div class="on-sale">- {{ round($catalogItem->offPercentage())}}%</div>
            @endif
               <div class="hover-area">
                {{-- item_type and affiliate_link are now on merchant_items --}}
                @if($bestMerchantItem && $bestMerchantItem->item_type == "affiliate" && $bestMerchantItem->affiliate_link)
               <div class="cart-button">
                 <a href="javascript:;" data-href="{{ $bestMerchantItem->affiliate_link }}"
                  class="button add_to_cart_button affilate-btn" data-bs-toggle="tooltip"
                  data-bs-placement="right" name="" data-bs-original-name="{{ __('Add To Cart') }}"
                  aria-label="{{ __('Add To Cart') }}"></a>
               </div>
            @else
            @if(!$bestMerchantItem || $catalogItem->emptyStock())
            <div class="cart-button">
              <a class="cart-out-of-stock button add_to_cart_button" href="#" name="{{ __('Out Of Stock') }}"><i
               class="flaticon-cancel flat-mini mx-auto"></i></a>
            </div>
         @else
          <div class="cart-button">
            <a href="javascript:;"
               class="m-cart-add button add_to_cart_button"
               data-merchant-item-id="{{ $bestMerchantItem->id }}"
               data-catalog-item-id="{{ $catalogItem->id }}"
               data-bs-placement="right"
               data-bs-original-name="{{ __('Add To Cart') }}"
               aria-label="{{ __('Add To Cart') }}"></a>
          </div>
      @endif
         @endif
                @if(Auth::check())
               <div class="favorite-button">
                 <a class="add_to_favorite  new button add_to_cart_button" id="add-to-wish" href="javascript:;"
                  data-href="{{ route('user-favorite-add', $catalogItem->id) }}" data-bs-toggle="tooltip"
                  data-bs-placement="right" name="" data-bs-original-name="Add to Favorites"
                  aria-label="Add to Favorites">{{ __('Favorites') }}</a>
               </div>
            @else
            <div class="favorite-button">
              <a class="add_to_favorite button add_to_cart_button" href="{{ route('user.login') }}"
               data-bs-toggle="tooltip" data-bs-placement="right" name=""
               data-bs-original-name="Add to Favorites" aria-label="Add to Favorites">{{ __('Favorites') }}</a>
            </div>
         @endif

               <div class="compare-button">
                 <a class="compare button button add_to_cart_button"
                  data-href="{{ route('catalog-item.compare.add', $catalogItem->id) }}" href="javascrit:;"
                  data-bs-toggle="tooltip" data-bs-placement="right" name="" data-bs-original-name="Compare"
                  aria-label="Compare">{{ __('Compare') }}</a>
               </div>
               </div>
            </div>
            <div class="catalogItem-info">
               <h3 class="catalogItem-name"><a
                  href="{{ $catalogItemUrl }}">{{ $catalogItem->showName() }}</a></h3>
               <div class="catalogItem-price">
                <div class="price">
                  <ins>{{ $catalogItem->setCurrency() }}</ins>
                  <del>{{ $catalogItem->showPreviousPrice() }}</del>
                </div>
               </div>
               <div class="shipping-feed-back">
                <div class="star-rating">
                  <div class="rating-wrap">
                   <p><i class="fas fa-star"></i><span> {{ number_format($catalogItem->catalog_reviews_avg_rating ?? 0, 1) }}
                       ({{ $catalogItem->catalog_reviews_count }})</span></p>
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
