@php
  /** @var \App\Models\Product $productt */
  /** @var \App\Models\MerchantProduct $merchant */
  $vendorId = $vendorId ?? optional($merchant)->user_id ?? 0;
@endphp

<div class="product-top">
  <div class="row g-4 align-items-start">
    <div class="col-md-6">
      <div class="product-media">
        <img class="img-fluid"
             src="{{ $productt->photo ? \Illuminate\Support\Facades\Storage::url($productt->photo) : asset('assets/images/noimage.png') }}"
             alt="{{ $productt->showName() }}">
      </div>
    </div>

    <div class="col-md-6">
      <h1 class="h4 mb-1">{{ $productt->showName() }}</h1>
      @if(!empty($productt->sku))
        <p class="text-muted mb-2"><strong>SKU:</strong> {{ $productt->sku }}</p>
      @endif

      <div class="mb-3">
        <span class="h5 d-block">{{ $merchant ? $merchant->showPrice() : $productt->showPrice($vendorId) }}</span>
        @if($merchant && $merchant->previous_price)
          <small class="text-muted"><del>{{ \App\Models\Product::convertPrice($merchant->previous_price) }}</del></small>
        @endif
      </div>

      {{-- خيارات اللون/المقاس (إن لزم) --}}
      <form id="buy-form" class="mb-3">
        <input type="hidden" id="vendorId" name="user" value="{{ $vendorId }}">
        <input type="hidden" id="productId" name="product" value="{{ $productt->id }}">

        {{-- اختر مقاس (إن وجد) --}}
        @php
          $sizes = [];
          $qtys  = [];
          if ($merchant && !empty($merchant->size)) {
              $sizes = is_string($merchant->size) ? array_map('trim', explode(',', $merchant->size)) : (array) $merchant->size;
          }
          if ($merchant && !empty($merchant->size_qty)) {
              $qtys = is_string($merchant->size_qty) ? array_map('trim', explode(',', $merchant->size_qty)) : (array) $merchant->size_qty;
          }
        @endphp

        @if(!empty($sizes))
          <div class="mb-2">
            <label class="form-label">{{ __('Size') }}</label>
            <select class="form-select" id="sizeSelect" name="size">
              <option value="">{{ __('Select') }}</option>
              @foreach($sizes as $i => $sz)
                @php $q = (int)($qtys[$i] ?? 0); @endphp
                <option value="{{ $sz }}" data-size-qty="{{ $q }}">{{ $sz }} {{ $q>0 ? "($q)" : '(0)' }}</option>
              @endforeach
            </select>
          </div>
        @endif

        {{-- اللون (إن وجد على المنتج) --}}
        @php
          $colors = [];
          if (!empty($productt->color)) {
              $colors = is_string($productt->color) ? array_map('trim', explode(',', $productt->color)) : (array) $productt->color;
          }
        @endphp
        @if(!empty($colors))
          <div class="mb-2">
            <label class="form-label">{{ __('Color') }}</label>
            <select class="form-select" id="colorSelect" name="color">
              <option value="">{{ __('Select') }}</option>
              @foreach($colors as $c)
                <option value="{{ ltrim($c,'#') }}">{{ $c }}</option>
              @endforeach
            </select>
          </div>
        @endif

        <div class="d-flex align-items-center gap-2 my-3">
          <label class="form-label mb-0">{{ __('Quantity') }}</label>
          <div class="input-group" style="width:140px;">
            <button class="btn btn-outline-secondary" type="button" id="qtyDown">-</button>
            <input type="text" class="form-control text-center" id="qtyInput" name="qty" value="1" readonly>
            <button class="btn btn-outline-secondary" type="button" id="qtyUp">+</button>
          </div>
        </div>

        <div class="d-flex gap-2">
          {{-- زر إضافة للسلة (Ajax) --}}
          <a href="javascript:;" id="addToCartBtn"
             data-href="{{ route('product.cart.add', ['product'=>$productt->id,'user'=>$vendorId]) }}"
             data-cross-href="{{ route('front.show.cross.product', $productt->id) }}"
             class="btn btn-primary add_cart_click {{ $productt->cross_products ? 'view_cross_product' : '' }}">
            {{ __('Add To Cart') }}
          </a>

          {{-- شراء الآن: يعيد التوجيه إلى addtonumcart مع تمرير user --}}
          <a href="javascript:;" id="buyNowBtn" class="btn btn-outline-primary">
            {{ __('Buy Now') }}
          </a>
        </div>
      </form>

      <div class="mt-3">
        <a class="text-decoration-none" href="{{ route('front.product', ['slug'=>$productt->slug, 'user'=>$vendorId]) }}">
          {{ __('View Details') }}
        </a>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function(){
  const qtyInput = document.getElementById('qtyInput');
  document.getElementById('qtyUp')?.addEventListener('click', ()=>{
    let v = parseInt(qtyInput.value||'1',10); qtyInput.value = (v+1);
  });
  document.getElementById('qtyDown')?.addEventListener('click', ()=>{
    let v = parseInt(qtyInput.value||'1',10); qtyInput.value = Math.max(1, v-1);
  });

  const buyBtn = document.getElementById('buyNowBtn');
  buyBtn?.addEventListener('click', function(){
    const pid  = document.getElementById('productId').value;
    const user = document.getElementById('vendorId').value || 0;
    const size = document.getElementById('sizeSelect')?.value || '';
    const color= document.getElementById('colorSelect')?.value || '';
    const qty  = document.getElementById('qtyInput').value || 1;

    // هذه الحقول قد تكون غير موجودة في بعض القوالب
    const size_qty   = document.getElementById('sizeSelect')?.selectedOptions[0]?.getAttribute('data-size-qty') || '';
    const size_price = 0; // لو عندك حسابات فرق السعر للمقاس، احقنها هنا
    const color_price= 0;

    const params = new URLSearchParams({
      id: pid, qty: qty,
      size: size, color: color,
      size_qty: size_qty, size_price: size_price,
      color_price: color_price, size_key: size_qty,
      keys: '', values: '', prices: '',
      user: user
    });

    window.location.href = "{{ url('addtonumcart') }}?" + params.toString();
  });
})();
</script>
@endpush
