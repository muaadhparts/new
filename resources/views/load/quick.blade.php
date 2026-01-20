<section class="catalogItem-details-area">
    <div id="quick-section">
    <div class="left-area-top-info">
        <div class="row">
          <div class="col-lg-5">
              <div class="xzoom-container">
                  <img class="xzoom5" id="xzoom-magnific"
                    src="{{ $catalogItem->photo ? \Illuminate\Support\Facades\Storage::url($catalogItem->photo) : asset('assets/images/noimage.png') }}"
                    xoriginal="{{ $catalogItem->photo ? \Illuminate\Support\Facades\Storage::url($catalogItem->photo) : asset('assets/images/noimage.png') }}" />
                  <div class="xzoom-thumbs">
                    <div class="all-carousel">

                      <a href="{{ $catalogItem->photo ? \Illuminate\Support\Facades\Storage::url($catalogItem->photo) : asset('assets/images/noimage.png') }}">
                        <img class="xzoom-gallery5" width="80" src="{{ $catalogItem->photo ? \Illuminate\Support\Facades\Storage::url($catalogItem->photo) : asset('assets/images/noimage.png') }}">
                      </a>

                      @php
                        // Get merchant-specific photos
                        $quickMerchantUserId = request()->get('user', $catalogItem->user_id);
                        $merchantGalleries = $catalogItem->merchantPhotosForMerchant($quickMerchantUserId, 4);
                      @endphp
                      @foreach($merchantGalleries as $gal)

                      <a href="{{asset('assets/images/merchant-photos/'.$gal->photo)}}">
                        <img class="xzoom-gallery5" width="80" src="{{asset('assets/images/merchant-photos/'.$gal->photo)}}" >
                      </a>

                      @endforeach

                    </div>
                  </div>
                </div>



          </div>
          <div class="col-lg-7">
            <div class="catalogItem-info">
              @php
                  $headerMerchantUserId = request()->get('user', $catalogItem->user_id);
              @endphp
              <h4 class="item-name">
                <x-catalog-item-name :catalog-item="$catalogItem" :merchant-user-id="$headerMerchantUserId" target="_blank" />
              </h4>

              <div class="top-meta">

                  {{-- STOCK SECTION  --}}

                  @if($catalogItem->emptyStock())
                  <li class="outStock">
                    <p>
                      <i class="icofont-close-circled"></i>
                      {{ __('Out Of Stock') }}
                    </p>
                  </li>
                  @else
                  <div class="isStock">
                      <span>
                        <i class="far fa-check-circle"></i>
                        {{ $gs->show_stock == 0 ? '' : $catalogItem->merchantSizeStock() }} {{ __('In Stock') }}
                      </span>
                  </div>
                  @endif

                  {{-- STOCK SECTION ENDS  --}}

                  {{-- REVIEW SECTION  --}}

                    <div class="stars">
                        <div class="review-stars">
                            <div class="empty-stars"></div>
                            <div class="full-stars" style="width:{{ App\Models\CatalogTestimonial::scorePercentage($catalogItem->id) }}%"></div>
                          </div>
                    </div>

                    <div class="review">
                      <i class="far fa-comments"></i> {{ App\Models\CatalogTestimonial::reviewCount($catalogItem->id) }} {{ __('Review') }}
                    </div>

                  {{-- REVIEW SECTION ENDS  --}}

                  {{-- CATALOGITEM CONDITION SECTION  --}}

                  @if($catalogItem->item_condition != 0)

                    <div class="{{ $catalogItem->item_condition == 2 ? 'condition' : 'no-condition' }}">
                      <span>{{ $catalogItem->item_condition == 2 ?  __('New')  :  __('Used') }}</span>
                    </div>

                  @endif

                  {{-- CATALOGITEM CONDITION SECTION ENDS --}}

                  {{-- CATALOGITEM FAVORITE SECTION  --}}

                    <div class="wish">

                      @if(Auth::check())

                      <a class="add-to-favorite" href="javascript:;" data-href="{{ $quickMerchantItem ? route('user-favorite-add-merchant', $quickMerchantItem->id) : '#' }}" data-bs-toggle="tooltip" data-bs-placement="top" name="{{ __('Favorites') }}">
                        <i class="far fa-heart"></i>
                      </a>

                      @else

                      <a rel-toggle="tooltip" href="javascript:;" name="{{ __('Favorites') }}" data-bs-placement="top" class="add-to-favorite" data-bs-toggle="modal" data-bs-target="#user-login">
                        <i class="far fa-heart"></i>
                      </a>

                      @endif

                    </div>

                  {{-- CATALOGITEM FAVORITE SECTION ENDS --}}

                  {{-- CATALOGITEM COMPARE SECTION  --}}

                    <div class="compear">

                      <a class="add-to-compare" href="javascript:;" data-href="{{ $quickMerchantItem ? route('merchant.compare.add', $quickMerchantItem->id) : '#' }}" data-bs-toggle="tooltip" data-bs-placement="top" name="{{ __('Compare') }}">
                        <i class="fas fa-random"></i>
                      </a>

                    </div>

                  {{-- CATALOGITEM COMPARE SECTION  --}}

                  {{-- CATALOGITEM VIDEO DISPLAY SECTION  --}}

                    @if($catalogItem->youtube != null)
                      <div class="play-video">
                        <a href="{{ $catalogItem->youtube }}" class="video-play-btn mfp-iframe"
                          data-bs-toggle="tooltip" data-bs-placement="top" name="{{ __('Play Video') }}">
                          <i class="fas fa-play"></i>
                        </a>
                      </div>
                    @endif

                  {{-- CATALOGITEM VIDEO DISPLAY SECTION ENDS  --}}

              </div>

              {{-- CATALOGITEM PRICE SECTION  --}}

              <div class="price-and-discount">
                <div class="price">
                  <div class="current-price" id="msizeprice">
                    {{ $catalogItem->merchantSizePrice() }}
                  </div>
                  <small>
                    <del>
                    @php
                        $merchantPrice = $catalogItem->merchantSizePrice();
                        $merchantPrevPrice = $catalogItem->merchantSizePreviousPrice();
                    @endphp
                    {{ $merchantPrevPrice ? $merchantPrevPrice : '' }}
                    </del>
                  </small>
                </div>
              </div>

              {{-- CATALOGITEM PRICE SECTION ENDS --}}

              {{-- CATALOGITEM STOCK CONDITION SECTION  --}}

              @if(!$catalogItem->emptyStock())
                <input type="hidden" class="catalogItem-stock" value="{{ $catalogItem->merchantSizeStock() }}">
              @else
                <input type="hidden" class="catalogItem-stock" value="">
              @endif

              {{-- CATALOGITEM STOCK CONDITION SECTION ENDS --}}

              {{-- CATALOGITEM ATTRIBUTE SECTION  --}}

              @if (!empty($catalogItem->attributes))
                @php
                  $attrArr = json_decode($catalogItem->attributes, true);
                @endphp
              @endif
              @if (!empty($attrArr))
                <div class="catalogItem-attributes">
                  <div class="row">
                  @foreach ($attrArr as $attrKey => $attrVal)
                    @if (array_key_exists("details_status",$attrVal) && $attrVal['details_status'] == 1)

                  <div class="col-lg-6">
                    <div class="form-group mb-2">
                      <strong for="" class="text-capitalize">{{ str_replace("_", " ", $attrKey) }} :</strong>
                        <div class="">
                        @foreach ($attrVal['values'] as $optionKey => $optionVal)
                          <div class="custom-control custom-radio">
                            <input type="hidden" class="keys" value="">
                            <input type="hidden" class="values" value="">
                            <input type="radio" id="{{$attrKey}}{{ $optionKey }}" name="{{ $attrKey }}" class="custom-control-input mproduct-attr"  data-key="{{ $attrKey }}" data-price = "{{ $attrVal['prices'][$optionKey] * $curr->value }}" value="{{ $optionVal }}" {{ $loop->first ? 'checked' : '' }}>
                            <label class="custom-control-label" for="{{$attrKey}}{{ $optionKey }}">{{ $optionVal }}

                            @if (!empty($attrVal['prices'][$optionKey]))
                              +
                              {{$curr->sign}} {{$attrVal['prices'][$optionKey] * $curr->value}}
                            @endif
                            </label>
                          </div>
                        @endforeach
                        </div>
                    </div>
                  </div>
                    @endif
                  @endforeach
                  </div>
                </div>
              @endif

              {{-- CATALOGITEM ATTRIBUTE SECTION ENDS  --}}

              {{-- CATALOGITEM ADD CART SECTION --}}
              @php
                  $quickMerchantUserId = request()->get('user', $catalogItem->user_id);
                  $quickMerchantItem = $catalogItem->merchantItems()->where('user_id', $quickMerchantUserId)->where('status', 1)->first();
                  $quickMinQty = $quickMerchantItem ? max(1, (int)($quickMerchantItem->minimum_qty ?? 1)) : max(1, (int)($catalogItem->minimum_qty ?? 1));
                  $quickStock = $quickMerchantItem ? (int)($quickMerchantItem->stock ?? 0) : (int)($catalogItem->stock ?? 0);
                  $quickPreordered = $quickMerchantItem ? (int)($quickMerchantItem->preordered ?? 0) : 0;
                  $quickCanBuy = $quickStock > 0 || $quickPreordered;
              @endphp

              <input type="hidden" id="mproduct_price" value="{{ round($catalogItem->merchantSizePrice() * $curr->value,2) }}">
              <input type="hidden" id="mcatalog_item_id" value="{{ $catalogItem->id }}">
              <input type="hidden" id="mmerchant_item_id" value="{{ $quickMerchantItem->id ?? '' }}">
              <input type="hidden" id="mmerchant_user_id" value="{{ $quickMerchantUserId }}">
              <input type="hidden" id="mcurr_pos" value="{{ $gs->currency_format }}">
              <input type="hidden" id="mcurr_sign" value="{{ $curr->sign }}">

              @if($quickCanBuy && $quickMerchantItem)

                <div class="inner-box">
                  <div class="cart-btn">
                    <ul class="btn-list">

                      {{-- CATALOGITEM QUANTITY SECTION --}}

                      {{-- item_type is now on merchant_items --}}
                      @if($quickMerchantItem && $quickMerchantItem->item_type != "affiliate")

                          <li>
                            <div class="multiple-item-price">
                              <div class="qty">
                                <span class="modal-plus">
                                  <i class="fas fa-plus"></i>
                                </span>
                                <input class="modal-total" type="text" id="purchase-qty1" value="{{ $quickMinQty }}"
                                       data-min="{{ $quickMinQty }}" data-stock="{{ $quickStock }}" data-preordered="{{ $quickPreordered }}">
                                <input type="hidden" id="mproduct_minimum_qty" value="{{ $quickMinQty }}">
                                <span class="modal-minus">
                                  <i class="fas fa-minus"></i>
                                </span>
                              </div>
                            </div>
                          </li>

                      @endif

                      {{-- CATALOGITEM QUANTITY SECTION ENDS --}}

                      {{-- item_type is now on merchant_items --}}
                      @if($quickMerchantItem && $quickMerchantItem->item_type == "affiliate")

                      <li>
                        <a href="{{ route('affiliate.catalogItem', $catalogItem->slug) }}" target="_blank">
                          <i class="icofont-cart"></i>
                          {{ __('Purchase Now') }}
                        </a>
                      </li>

                      @else

                      {{-- UNIFIED: Use data attributes for merchant-cart-unified.js --}}
                      <li>
                        <button type="button" class="m-cart-add"
                                data-merchant-item-id="{{ $quickMerchantItem->id }}"
                                data-merchant-user-id="{{ $quickMerchantUserId }}"
                                data-min-qty="{{ $quickMinQty }}"
                                data-qty-input=".modal-total">
                          <i class="icofont-cart"></i>
                          {{ __('Add To Cart') }}
                        </button>
                      </li>

                      <li>
                        <button type="button" class="m-cart-add"
                                data-merchant-item-id="{{ $quickMerchantItem->id }}"
                                data-merchant-user-id="{{ $quickMerchantUserId }}"
                                data-min-qty="{{ $quickMinQty }}"
                                data-qty-input=".modal-total"
                                data-redirect="/merchant-cart">
                          <i class="icofont-cart"></i>
                          {{ __('Purchase Now') }}
                        </button>
                      </li>

                      @endif

                    </ul>
                  </div>
                </div>

              @endif

              {{-- CATALOGITEM ADD CART SECTION ENDS --}}

              {{-- CATALOGITEM OTHER DETAILS SECTION --}}

              @if($catalogItem->ship != null)

              <div class="shipping-time">
                {{ __('Estimated Shipping Time:') }}
                <span>{{ $catalogItem->ship }}</span>
              </div>

              @endif

              @if( $catalogItem->part_number != null )

              <div class="catalogItem-id">
                {{ __('CatalogItem PART_NUMBER:') }}
                <span>{{ $catalogItem->part_number }}</span>
              </div>

              @endif

              @php
                $qualityMerchantUserId = request()->get('user', $catalogItem->user_id);
                $qualityMerchantItem = $catalogItem->merchantItems()
                    ->where('user_id', $qualityMerchantUserId)
                    ->where('status', 1)
                    ->first();
                // All brands from catalog_item_fitments (vehicle compatibility)
                $quickFitments = $catalogItem->fitments ?? collect();
                $quickBrands = $quickFitments->map(fn($f) => $f->brand)->filter()->unique('id')->values();
                $quickBrandCount = $quickBrands->count();
                $quickBrandsJson = $quickBrands->map(fn($b) => ['id' => $b->id, 'name' => $b->localized_name, 'logo' => $b->photo_url, 'slug' => $b->slug])->toArray();
              @endphp

              {{-- Vehicle Fitment Brands (from catalog_item_fitments) --}}
              @if($quickBrandCount === 1)
              <div class="catalogItem-id">
                {{ __('Fits:') }}
                <span>{{ Str::ucfirst(getLocalizedBrandName($quickBrands->first())) }}</span>
              </div>
              @elseif($quickBrandCount > 1)
              <div class="catalogItem-id">
                {{ __('Fits:') }}
                <button type="button" class="fitment-brands-btn"
                        data-brands="{{ json_encode($quickBrandsJson) }}"
                        data-part-number="{{ $catalogItem->part_number }}">
                    <i class="fas fa-car"></i> {{ $quickBrandCount }} {{ __('brands') }}
                </button>
              </div>
              @endif

              @if($qualityMerchantItem && $qualityMerchantItem->qualityBrand)
              <div class="catalogItem-id">
                {{ __('Brand qualities:') }}
                <span>{{ getLocalizedQualityName($qualityMerchantItem->qualityBrand) }}</span>
              </div>
              @endif

              {{-- CATALOGITEM OTHER DETAILS SECTION ENDS --}}

              <div class="mt-2">
                @if($catalogItem->part_number)
                    <a class="view_more_btn" href="{{ route('front.part-result', $catalogItem->part_number) }}">{{__('Get More Details')}} <i class="fas fa-arrow-right"></i></a>
                @endif
              </div>




            </div>
          </div>
        </div>
      </div>
    </div>

    </section>

    <script src="{{asset('assets/front/js/setup.js')}}"></script>

    <script type="text/javascript">

    (function($) {
        "use strict";

      function number_format (number, decimals, dec_point, thousands_sep) {
          // Strip all characters but numerical ones.
          number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
          var n = !isFinite(+number) ? 0 : +number,
              prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
              sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
              dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
              s = '',
              toFixedFix = function (n, prec) {
                  var k = Math.pow(10, prec);
                  return '' + Math.round(n * k) / k;
              };
          // Fix for IE parseFloat(0.55).toFixed(0) = 0;
          s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
          if (s[0].length > 3) {
              s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
          }
          if ((s[1] || '').length < prec) {
              s[1] = s[1] || '';
              s[1] += new Array(prec - s[1].length + 1).join('0');
          }
          return s.join(dec);
      }

        //   magnific popup activation
        $('.video-play-btn').magnificPopup({
            type: 'video'
        });

        var total = "";
        var mstock = $('.catalogItem-stock').val();
        var keys = "";
        var values = "";
        var prices = "";

        $('.mproduct-attr').on('change',function(){

                var total;
                total = mgetAmount();
                total = total.toFixed(2);
                var pos = $('#mcurr_pos').val();
                var sign = $('#mcurr_sign').val();
                if(pos == '0')
                {
                $('#msizeprice').html(sign+total);
                }
                else {
                $('#msizeprice').html(total+sign);
                }
        });


        function mgetAmount()
        {
          var total = 0;
          var value = parseFloat($('#mproduct_price').val());
          var datas = $(".mproduct-attr:checked").map(function() {
            return $(this).data('price');
          }).get();

          var data;
          for (data in datas) {
            total += parseFloat(datas[data]);
          }
          total += value;
          return total;
        }


        $('.modal-total').keypress(function(e){
          if (this.value.length == 0 && e.which == 48 ){
            return false;
         }
          if(e.which != 8 && e.which != 32){
            if(isNaN(String.fromCharCode(e.which))){
              e.preventDefault();
            }
          }
        });

        $('.modal-minus').on('click', function () {
            var el = $(this);
            var $tselector = el.parent().parent().find('.modal-total');
            total = $($tselector).val();
            if (total > 1) {
                total--;
            }
            $($tselector).val(total);
        });

        $('.modal-plus').on('click', function () {
            var el = $(this);
            var $tselector = el.parent().parent().find('.modal-total');
            total = $($tselector).val();
            if(mstock != "")
            {
                var stk = parseInt(mstock);
                if(total < stk)
                {
                    total++;
                    $($tselector).val(total);
                }
            }
            else {
                total++;
            }
            $($tselector).val(total);
        });

        // ============================================
        // DEPRECATED: #maddcrt and #mqaddcrt handlers
        // Now handled by merchant-cart-unified.js via .m-cart-add class
        // Buttons use: class="m-cart-add" with data-merchant-item-id, data-qty-input, data-redirect
        // ============================================

    })(jQuery);

    </script>
