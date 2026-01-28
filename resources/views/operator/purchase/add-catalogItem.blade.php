<div class="catalogItem-info text-center">

  <h4 class="item-name">
    {{ $catalogItem->name }}
  </h4>

  <div class="price-and-discount">
    <div class="price">
      <div class="current-price" id="sizeprice">
        {{ app(\App\Domain\Catalog\Services\CatalogItemDisplayService::class)->formatPrice($catalogItem, $catalogItem->lowest_price ?? 0) }}
      </div>
    </div>
  </div>

  {{-- CATALOGITEM STOCK CONDITION SECTION --}}

  @if(!$catalogItem->emptyStock())
  <input type="hidden" class="catalogItem-stock" value="{{ $catalogItem->stock }}">
  @else
  <input type="hidden" class="catalogItem-stock" value="">
  @endif

  {{-- CATALOGITEM STOCK CONDITION SECTION ENDS --}}

  {{-- CATALOGITEM ATTRIBUTE SECTION --}}

  @if (!empty($catalogItem->attributes))

  <div class="catalogItem-attributes mt-3 mb-3 text-left">
    <div class="row">
      @foreach ($catalogItem->attributes as $attrKey => $attrVal)
      @if (array_key_exists("details_status",$attrVal) && $attrVal['details_status'] == 1)

      <div class="col-lg-6 offset-lg-4">
        <div class="form-group mb-2">
          <strong for="" class="text-capitalize">{{ str_replace("_", " ", $attrKey) }} :</strong>
          <div class="">
            @foreach ($attrVal['values'] as $optionKey => $optionVal)
            <div class="custom-control custom-radio">
              <input type="hidden" class="keys" value="">
              <input type="hidden" class="values" value="">
              <input type="radio" id="{{$attrKey}}{{ $optionKey }}" name="{{ $attrKey }}"
                class="custom-control-input catalogItem-attr" data-key="{{ $attrKey }}"
                data-price="{{ $attrVal['prices'][$optionKey] * $curr->value }}" value="{{ $optionVal }}" {{
                $loop->first ? 'checked' : '' }}>
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

  {{-- CATALOGITEM ATTRIBUTE SECTION ENDS --}}

  <input type="hidden" id="catalogItem_price" value="{{ round($catalogItem->merchantPrice() * $curr->value,2) }}">
  <input type="hidden" id="catalogItem_id" value="{{ $catalogItem->id }}">
  <input type="hidden" id="curr_pos" value="{{ $gs->currency_format }}">
  <input type="hidden" id="curr_sign" value="{{ $curr->sign }}">


  <div class="inner-box">
    <div class="cart-btn">

      <div class="multiple-item-price">
        <div class="qty">
          <span class="qtplus">
            <i class="fas fa-plus"></i>
          </span>
          <input class="qttotal" type="text" value="1">
          <span class="qtminus">
            <i class="fas fa-minus"></i>
          </span>
        </div>
      </div>

      <button type="button" id="qaddcrt" class="btn btn-primary1" href="javascript:;">
        {{ __('Add') }}
      </button>

    </div>
  </div>

</div>

<script type="text/javascript">
  (function($) {
		"use strict";

    var order_id = $('#order_id').val();

    let gs = {
        decimal_separator: '{{ platformSettings()->get('decimal_separator', '.') }}',
        thousand_separator: '{{ platformSettings()->get('thousand_separator', ',') }}'
    };

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


      var mtotal = "";
      var mstock = $('.catalogItem-stock').val();
      var keys = "";
      var values = "";
      var prices = "";

      $('.catalogItem-attr').on('change',function(){

        var total = 0;
         total = mgetAmount();
         total = total.toFixed(2);

         total = number_format(total, 2, gs.decimal_separator, gs.thousand_separator);

         var pos = $('#curr_pos').val();
         var sign = $('#curr_sign').val();
         if(pos == '0')
         {
         $('#sizeprice').html(sign+total);
         }
         else {
         $('#sizeprice').html(total+sign);
         }
      });

      function mgetAmount()
      {
        var total = 0;
        var value = parseFloat($('#catalogItem_price').val());
        var datas = $(".catalogItem-attr:checked").map(function() {
          return $(this).data('price');
        }).get();

        var data;
        for (data in datas) {
          total += parseFloat(datas[data]);
        }
        total += value;
        return total;
      }

      $('.qttotal').keypress(function(e){
        if (this.value.length == 0 && e.which == 48 ){
          return false;
       }
        if(e.which != 8 && e.which != 32){
          if(isNaN(String.fromCharCode(e.which))){
            e.preventDefault();
          }
        }
      });

      $('.qtminus').on('click', function () {
        var total = 0;
          var el = $(this);
          var $tselector = el.parent().parent().find('.qttotal');
          total = $($tselector).val();
          if (total > 1) {
              total--;
          }
          $($tselector).val(total);
      });

      $('.qtplus').on('click', function () {
        var total = 0;
          var el = $(this);
          var $tselector = el.parent().parent().find('.qttotal');
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

      $(document).on("click", "#qaddcrt" , function(){
        var qty = $('.qttotal').val();
        var pid = $(this).parent().parent().parent().parent().parent().find("#catalogItem_id").val();

        if($('.catalogItem-attr').length > 0)
        {
        values = $(".catalogItem-attr:checked").map(function() {
        return $(this).val();
        }).get();

        keys = $(".catalogItem-attr:checked").map(function() {
        return $(this).data('key');
        }).get();

        prices = $(".catalogItem-attr:checked").map(function() {
        return $(this).data('price');
        }).get();

        }

       window.location = mainurl+"/admin/purchase/addcart/"+order_id+"?id="+pid+"&qty="+qty+"&keys="+keys+"&values="+values+"&prices="+prices;

       });

})(jQuery);

</script>
