<section class="product-details-area">
    <div id="quick-section">
    <div class="left-area-top-info">
        <div class="row">
          <div class="col-lg-5">
              <div class="xzoom-container">
                  <img class="xzoom5" id="xzoom-magnific"
                    src="{{ $product->photo ? \Illuminate\Support\Facades\Storage::url($product->photo) : asset('assets/images/noimage.png') }}"
                    xoriginal="{{ $product->photo ? \Illuminate\Support\Facades\Storage::url($product->photo) : asset('assets/images/noimage.png') }}" />
                  <div class="xzoom-thumbs">
                    <div class="all-slider">

                      <a href="{{ $product->photo ? \Illuminate\Support\Facades\Storage::url($product->photo) : asset('assets/images/noimage.png') }}">
                        <img class="xzoom-gallery5" width="80" src="{{ $product->photo ? \Illuminate\Support\Facades\Storage::url($product->photo) : asset('assets/images/noimage.png') }}">
                      </a>

                      @php
                        // Get vendor-specific galleries
                        $quickVendorId = request()->get('user', $product->user_id);
                        $vendorGalleries = $product->galleriesForVendor($quickVendorId, 4);
                      @endphp
                      @foreach($vendorGalleries as $gal)

                      <a href="{{asset('assets/images/galleries/'.$gal->photo)}}">
                        <img class="xzoom-gallery5" width="80" src="{{asset('assets/images/galleries/'.$gal->photo)}}" >
                      </a>

                      @endforeach

                    </div>
                  </div>
                </div>



          </div>
          <div class="col-lg-7">
            <div class="product-info">
              <h4 class="item-name">
                <x-product-name :product="$product" :vendor-id="request()->get('user', $product->user_id)" target="_blank" />
              </h4>

              <div class="top-meta">

                  {{-- STOCK SECTION  --}}

                  @if($product->type == 'Physical')
                      @if($product->emptyStock())
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
                            {{ $gs->show_stock == 0 ? '' : $product->vendorSizeStock() }} {{ __('In Stock') }}
                          </span>
                      </div>
                      @endif
                  @endif

                  {{-- STOCK SECTION ENDS  --}}

                  {{-- REVIEW SECTION  --}}

                    <div class="stars">
                        <div class="ratings">
                            <div class="empty-stars"></div>
                            <div class="full-stars" style="width:{{ App\Models\Rating::ratings($product->id) }}%"></div>
                          </div>
                    </div>

                    <div class="review">
                      <i class="far fa-comments"></i> {{ App\Models\Rating::ratingCount($product->id) }} {{ __('Review') }}
                    </div>

                  {{-- REVIEW SECTION ENDS  --}}

                  {{-- PRODUCT CONDITION SECTION  --}}

                  @if($product->product_condition != 0)

                    <div class="{{ $product->product_condition == 2 ? 'condition' : 'no-condition' }}">
                      <span>{{ $product->product_condition == 2 ?  __('New')  :  __('Used') }}</span>
                    </div>

                  @endif

                  {{-- PRODUCT CONDITION SECTION ENDS --}}

                  {{-- PRODUCT WISHLIST SECTION  --}}

                    <div class="wish">

                      @if(Auth::check())

                      <a class="add-to-wish" href="javascript:;" data-href="{{ route('user-wishlist-add',$product->id) }}" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('Wish') }}">
                        <i class="far fa-heart"></i>
                      </a>

                      @else

                      <a rel-toggle="tooltip" href="javascript:;" title="{{ __('Wish') }}" data-bs-placement="top" class="add-to-wish" data-bs-toggle="modal" data-bs-target="#user-login">
                        <i class="far fa-heart"></i>
                      </a>

                      @endif

                    </div>

                  {{-- PRODUCT WISHLIST SECTION ENDS --}}

                  {{-- PRODUCT COMPARE SECTION  --}}

                    <div class="compear">

                      <a class="add-to-compare" href="javascript:;" data-href="{{ route('product.compare.add',$product->id) }}" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('Compare') }}">
                        <i class="fas fa-random"></i>
                      </a>

                    </div>

                  {{-- PRODUCT COMPARE SECTION  --}}

                  {{-- PRODUCT VIDEO DISPLAY SECTION  --}}

                    @if($product->youtube != null)
                      <div class="play-video">
                        <a href="{{ $product->youtube }}" class="video-play-btn mfp-iframe"
                          data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('Play Video') }}">
                          <i class="fas fa-play"></i>
                        </a>
                      </div>
                    @endif

                  {{-- PRODUCT VIDEO DISPLAY SECTION ENDS  --}}

              </div>

              {{-- PRODUCT PRICE SECTION  --}}

              <div class="price-and-discount">
                <div class="price">
                  <div class="current-price" id="msizeprice">
                    {{ $product->vendorSizePrice() }}
                  </div>
                  <small>
                    <del>
                    @php
                        $vendorPrice = $product->vendorSizePrice();
                        $vendorPrevPrice = $product->vendorSizePreviousPrice();
                    @endphp
                    {{ $vendorPrevPrice ? $vendorPrevPrice : '' }}
                    </del>
                  </small>
                </div>
              </div>

              {{-- PRODUCT PRICE SECTION ENDS --}}

              {{-- PRODUCT SIZE SECTION  --}}

              @if ($product->stock_check == 1)

                  {{-- PRODUCT SIZE SECTION  --}}

                  @php
                    $vendorId = request()->get('user') ?? ($product->vendor_user_id ?? $product->user_id);
                    $vendorSizes = $product->getVendorSizes($vendorId);
                  @endphp
                  @if(!empty($vendorSizes))
                  <div class="mproduct-size">
                    <p class="title">{{ __('Size :') }}</p>
                    <ul class="siz-list">
                      @foreach($vendorSizes as $key => $data1)
                    <li class="{{ $loop->first ? 'active' : '' }}" data-key="{{ str_replace(' ','',$data1) }}">
                          <span class="box">
                            {{ $data1 }}

                            <input type="hidden" class="msize" value="{{ $data1 }}">
                            <input type="hidden" class="msize_key" value="{{$key}}">
                          </span>
                        </li>
                      @endforeach
                    </ul>
                  </div>

                  @endif

                  {{-- PRODUCT SIZE SECTION ENDS  --}}

                  {{-- PRODUCT COLOR SECTION  --}}

                  @if(!empty($product->getVendorColors()))

                  <div class="mproduct-color">
                    <div class="title">{{ __('Color :') }}</div>
                    <ul class="color-list">

                      @php
                        $vendorColors = $product->getVendorColors($vendorId);
                      @endphp
                      @foreach($vendorColors as $key => $data1)

                        <li class="{{ $loop->first ? 'active' : '' }} {{ $product->IsSizeColor($vendorSizes[$key] ?? '') ? str_replace(' ','',($vendorSizes[$key] ?? '')) : ''  }} {{ ($vendorSizes[$key] ?? '') == ($vendorSizes[0] ?? '') ? 'show-colors' : '' }}">
                          <span class="box" data-color="{{ $vendorColors[$key] }}" style="background-color: {{ $vendorColors[$key] }}">
                            @php
                                $vendorSizeQty = $product->getVendorSizeQty($vendorId, $key);
                                $vendorSizePrice = $product->getVendorSizePrice($vendorId, $key);
                            @endphp
                            <input type="hidden" class="msize" value="{{ $vendorSizes[$key] ?? '' }}">
                            <input type="hidden" class="msize_qty" value="{{ $vendorSizeQty }}">
                            <input type="hidden" class="msize_key" value="{{$key}}">
                            <input type="hidden" class="msize_price" value="{{ round($vendorSizePrice * $curr->value,2) }}">

                          </span>
                        </li>

                      @endforeach

                    </ul>
                  </div>

                  @endif

                  {{-- PRODUCT COLOR SECTION ENDS  --}}

                  @else
                  @php
                    $vendorSizeAll = $product->getVendorSizeAll($vendorId);
                @endphp
                @if(!empty($vendorSizeAll))
                  <div class="mproduct-size" data-key="false">
                    <p class="title">{{ __('Size :') }}</p>
                    <ul class="siz-list">
                      @foreach(array_unique(explode(',', $vendorSizeAll)) as $key => $data1)
                    <li class="{{ $loop->first ? 'active' : '' }}" data-key="{{ str_replace(' ','',$data1) }}">
                          <span class="box">
                            {{ $data1 }}
                            <input type="hidden" class="msize" value="{{$data1}}">
                            <input type="hidden" class="msize_key" value="{{$key}}">
                          </span>
                        </li>
                      @endforeach
                    </ul>
                  </div>
                  @endif
                  @php
                      $vendorColorAll = $product->getVendorColorAll($vendorId);
                  @endphp
                  @if(!empty($vendorColorAll))

                  <div class="mproduct-color" data-key="false">
                    <div class="title">{{ __('Color :') }}</div>
                    <ul class="color-list">

                      @foreach(explode(',', $vendorColorAll) as $key => $color1)

                        <li class="{{ $loop->first ? 'active' : '' }} show-colors">
                          <span class="box" data-color="{{ $color1 }}" style="background-color: {{ $color1 }}">
                            <input type="hidden" class="msize_price" value="0">

                          </span>
                        </li>

                      @endforeach

                    </ul>
                  </div>

                  @endif
                  @endif

              {{-- PRODUCT COLOR SECTION ENDS  --}}

              {{-- PRODUCT STOCK CONDITION SECTION  --}}

              @if(!empty($vendorSizes))

                @php
                    $firstSizeQty = $product->getVendorSizeQty($vendorId, 0);
                @endphp
                <input type="hidden" class="product-stock" value="{{ $firstSizeQty }}">

                @else

                @if(!$product->emptyStock())
                  <input type="hidden" class="product-stock" value="{{ $product->vendorSizeStock() }}">
                @elseif($product->type != 'Physical')
                  <input type="hidden" class="product-stock" value="0">
                @else
                  <input type="hidden" class="product-stock" value="">

                @endif

              @endif

              {{-- PRODUCT STOCK CONDITION SECTION ENDS --}}

              {{-- PRODUCT ATTRIBUTE SECTION  --}}

              @if (!empty($product->attributes))
                @php
                  $attrArr = json_decode($product->attributes, true);
                @endphp
              @endif
              @if (!empty($attrArr))
                <div class="product-attributes">
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

              {{-- PRODUCT ATTRIBUTE SECTION ENDS  --}}

              {{-- PRODUCT ADD CART SECTION --}}
              @php
                  $quickVendorId = request()->get('user', $product->user_id);
                  $quickMp = $product->merchantProducts()->where('user_id', $quickVendorId)->where('status', 1)->first();
                  $quickMinQty = $quickMp ? max(1, (int)($quickMp->minimum_qty ?? 1)) : max(1, (int)($product->minimum_qty ?? 1));
                  $quickStock = $quickMp ? (int)($quickMp->stock ?? 0) : (int)($product->stock ?? 0);
                  $quickPreordered = $quickMp ? (int)($quickMp->preordered ?? 0) : 0;
                  $quickCanBuy = $quickStock > 0 || $quickPreordered;
              @endphp

              <input type="hidden" id="mproduct_price" value="{{ round($product->vendorSizePrice() * $curr->value,2) }}">
              <input type="hidden" id="mproduct_id" value="{{ $product->id }}">
              <input type="hidden" id="mmerchant_product_id" value="{{ $quickMp->id ?? '' }}">
              <input type="hidden" id="mvendor_user_id" value="{{ $quickVendorId }}">
              <input type="hidden" id="mcurr_pos" value="{{ $gs->currency_format }}">
              <input type="hidden" id="mcurr_sign" value="{{ $curr->sign }}">

              @if($quickCanBuy && $quickMp)

                <div class="inner-box">
                  <div class="cart-btn">
                    <ul class="btn-list">

                      {{-- PRODUCT QUANTITY SECTION --}}

                      @if($product->product_type != "affiliate" && $product->type == 'Physical')

                          <li>
                            <div class="multiple-item-price">
                              <div class="qty">
                                <span class="modal-plus">
                                  <i class="fas fa-plus"></i>
                                </span>
                                <input class="modal-total" type="text" id="order-qty1" value="{{ $quickMinQty }}"
                                       data-min="{{ $quickMinQty }}" data-stock="{{ $quickStock }}" data-preordered="{{ $quickPreordered }}">
                                <input type="hidden" id="mproduct_minimum_qty" value="{{ $quickMinQty }}">
                                <span class="modal-minus">
                                  <i class="fas fa-minus"></i>
                                </span>
                              </div>
                            </div>
                          </li>

                      @endif

                      {{-- PRODUCT QUANTITY SECTION ENDS --}}

                      @if($product->product_type == "affiliate")

                      <li>
                        <a href="{{ route('affiliate.product', $product->slug) }}" target="_blank">
                          <i class="icofont-cart"></i>
                          {{ __('Purchase Now') }}
                        </a>
                      </li>

                      @else

                      {{-- UNIFIED: Use data attributes for cart-unified.js --}}
                      <li>
                        <button type="button" id="maddcrt"
                                data-merchant-product-id="{{ $quickMp->id }}"
                                data-vendor-id="{{ $quickVendorId }}"
                                data-min-qty="{{ $quickMinQty }}">
                          <i class="icofont-cart"></i>
                          {{ __('Add To Cart') }}
                        </button>
                      </li>

                      <li>
                        <button type="button" id="mqaddcrt"
                                data-merchant-product-id="{{ $quickMp->id }}"
                                data-vendor-id="{{ $quickVendorId }}"
                                data-min-qty="{{ $quickMinQty }}"
                                data-redirect="/cart">
                          <i class="icofont-cart"></i>
                          {{ __('Purchase Now') }}
                        </button>
                      </li>

                      @endif

                    </ul>
                  </div>
                </div>

              @endif

              {{-- PRODUCT ADD CART SECTION ENDS --}}

              {{-- PRODUCT OTHER DETAILS SECTION --}}

              @if($product->ship != null)

              <div class="shipping-time">
                {{ __('Estimated Shipping Time:') }}
                <span>{{ $product->ship }}</span>
              </div>

              @endif

              @if( $product->sku != null )

              <div class="product-id">
                {{ __('Product SKU:') }}
                <span>{{ $product->sku }}</span>
              </div>

              @endif

              @if($product->brand)
              <div class="product-id">
                {{ __('Brand:') }}
                <span>{{ Str::ucfirst(getLocalizedBrandName($product->brand)) }}</span>
              </div>
              @endif

              @php
                $quickVendorId = request()->get('user', $product->user_id);
                $quickMerchant = $product->merchantProducts()->where('user_id', $quickVendorId)->where('status', 1)->first();
              @endphp

              @if($quickMerchant && $quickMerchant->qualityBrand)
              <div class="product-id">
                {{ __('Brand qualities:') }}
                <span>{{ getLocalizedQualityName($quickMerchant->qualityBrand) }}</span>
              </div>
              @endif

              {{-- PRODUCT OTHER DETAILS SECTION ENDS --}}

              {{-- PRODUCT LICENSE SECTION --}}

              @if($product->type == 'License')

                @if($product->platform != null)
                  <div class="license-id">
                      {{ __('Platform:') }}
                      <span>{{ $product->platform }}</span>
                  </div>
                @endif

                @if($product->region != null)
                  <div class="license-id">
                      {{ __('Region:') }}
                      <span>{{ $product->region }}</span>
                  </div>
                @endif

                @if($product->licence_type != null)
                <div class="license-id">
                    {{ __('License Type:') }}
                    <span>{{ $product->licence_type }}</span>
                </div>
                @endif

              @endif

              <div class="mt-2">
                @php
                    $quickVendorId = request()->get('user', $product->user_id);
                    $quickMerchant = $product->merchantProducts()->where('user_id', $quickVendorId)->where('status', 1)->first();
                    $quickMerchantId = $quickMerchant->id ?? null;
                @endphp
                @if($quickMerchantId)
                    <a class="view_more_btn" href="{{ route('front.product', ['slug' => $product->slug, 'vendor_id' => $quickVendorId, 'merchant_product_id' => $quickMerchantId]) }}">{{__('Get More Details')}} <i class="fas fa-arrow-right"></i></a>
                @endif
              </div>


              {{-- PRODUCT LICENSE SECTION ENDS--}}


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

        var sizes = "";
        var size_qty = ($('.mproduct-color .color-list li.active').length > 0) ? parseFloat($('.mproduct-color .color-list li.active').find('.msize_qty').val()) : '';
        var size_price = "";
        var size_key = "";
        var colors = "";
        var total = "";
        var mstock = $('.product-stock').val();
        var keys = "";
        var values = "";
        var prices = "";

        $('.mproduct-attr').on('change',function(){

                var total;
                total = mgetAmount()+mgetSizePrice();
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


        function mgetSizePrice()
          {
            var total = 0;
            if($('.mproduct-color .color-list li.active').length > 0)
            {
              total = parseFloat($('.mproduct-color .color-list li.active').find('.msize_price').val());
            }
            return total;
          }


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

        // Product Details Product Size Active Js Code
        $('.mproduct-size .siz-list .box').on('click', function () {

            var parent = $(this).parent();
            $('.mproduct-size .siz-list li').removeClass('active');
            parent.addClass('active');

            sizes = $(this).find('input.msize').val();
            size_key = $(this).find('input.msize_key').val();
            $('.modal-total').val('1');

            if ($(this).parent().parent().parent().attr('data-key') != 'false') {
              $('.mproduct-color .color-list li').removeClass('show-colors');

            var size_color = $('.mproduct-color .color-list li.'+parent.data('key'));
            size_color.addClass('show-colors').first().addClass('active');
            colors = size_color.find('span.box').data('color');
            sizes = size_color.find('.msize').val();
            size_qty = size_color.find('.msize_qty').val();
            size_price = size_color.find('.msize_price').val();
            size_key = size_color.find('.msize_key').val();

            total = mgetAmount()+parseFloat(size_price);
            mstock = size_qty;
            total = total.toFixed(2);
            total = number_format(total, 2, gs.decimal_separator, gs.thousand_separator);
            var pos = $('#mcurr_pos').val();
            var sign = $('#mcurr_sign').val();
            if(pos == '0')
            {
             $('#msizeprice').html(sign+total);
            }
            else {
             $('#msizeprice').html(total+sign);
            }

          }



        });



        // Product Details Product Color Active Js Code
        $('.mproduct-color .color-list .box').on('click', function () {
            colors = $(this).data('color');
            var parent = $(this).parent();
            $('.mproduct-color .color-list li').removeClass('active');
            parent.addClass('active');

            $('.modal-total').html('1');

            if ($(this).parent().parent().parent().attr('data-key') != 'false') {

             size_qty = $(this).find('.msize_qty').val();
             size_price = $(this).find('.msize_price').val();
             size_key = $(this).find('.msize_key').val();
             sizes = $(this).find('.msize').val();
             total = mgetAmount()+parseFloat(size_price);
             mstock = size_qty;
             total = total.toFixed(2);
             total = number_format(total, 2, gs.decimal_separator, gs.thousand_separator);
             var pos = $('#mcurr_pos').val();
             var sign = $('#mcurr_sign').val();
             if(pos == '0')
             {
             $('#msizeprice').html(sign+total);
             }
             else {
             $('#msizeprice').html(total+sign);
             }
            }
        });


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

        // UNIFIED CART: Add to Cart
        $("#maddcrt").on("click", function(){
            var $btn = $(this);
            var mpId = $btn.data('merchant-product-id');
            var vendorId = $btn.data('vendor-id');
            var minQty = parseInt($btn.data('min-qty')) || 1;
            var qty = parseInt($('.modal-total').val()) || minQty;

            if (!mpId) {
                toastr.error("{{ __('Product not available') }}");
                return false;
            }

            if (qty < minQty) {
                toastr.error("{{ __('Minimum Quantity is:') }} " + minQty);
                return false;
            }

            // Build payload
            var payload = {
                merchant_product_id: mpId,
                vendor_id: vendorId,
                qty: qty,
                size: sizes || '',
                color: colors || ''
            };

            // Show loading
            var originalHtml = $btn.html();
            $btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

            $.ajax({
                type: "POST",
                url: "/cart/unified",
                data: JSON.stringify(payload),
                contentType: "application/json",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    if (data.success || data.message) {
                        toastr.success(data.message || data.success || "{{ __('Successfully Added To Cart.') }}");
                        if (data.cart_count !== undefined) {
                            $("#cart-count").html(data.cart_count);
                        }
                        $("#cart-items").load(mainurl+'/carts/view');
                    } else {
                        toastr.error(data.error || "{{ __('Error adding to cart') }}");
                    }
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON?.message || xhr.responseJSON?.error || "{{ __('Error adding to cart') }}";
                    toastr.error(msg);
                },
                complete: function() {
                    $btn.html(originalHtml).prop('disabled', false);
                }
            });
        });

        // UNIFIED CART: Buy Now
        $(document).on("click", "#mqaddcrt", function(){
            var $btn = $(this);
            var mpId = $btn.data('merchant-product-id');
            var vendorId = $btn.data('vendor-id');
            var minQty = parseInt($btn.data('min-qty')) || 1;
            var qty = parseInt($('.modal-total').val()) || minQty;

            if (!mpId) {
                toastr.error("{{ __('Product not available') }}");
                return false;
            }

            if (qty < minQty) {
                toastr.error("{{ __('Minimum Quantity is:') }} " + minQty);
                return false;
            }

            // Build payload
            var payload = {
                merchant_product_id: mpId,
                vendor_id: vendorId,
                qty: qty,
                size: sizes || '',
                color: colors || ''
            };

            // Show loading
            var originalHtml = $btn.html();
            $btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

            $.ajax({
                type: "POST",
                url: "/cart/unified",
                data: JSON.stringify(payload),
                contentType: "application/json",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    if (data.success || data.message) {
                        // Redirect to cart
                        window.location = '/cart';
                    } else {
                        toastr.error(data.error || "{{ __('Error adding to cart') }}");
                        $btn.html(originalHtml).prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON?.message || xhr.responseJSON?.error || "{{ __('Error adding to cart') }}";
                    toastr.error(msg);
                    $btn.html(originalHtml).prop('disabled', false);
                }
            });
        });

    })(jQuery);

    </script>
