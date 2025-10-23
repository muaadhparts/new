@php
  /** @var \App\Models\Product $productt */
  /** @var \App\Models\MerchantProduct|null $merchant */
  $vendorId  = $vendorId ?? optional($merchant)->user_id ?? 0;
  $merchantProductId = $merchant->id ?? null;
  $hasVendor = $vendorId > 0;

  // حساب السعر/الخصم Vendor-aware
  $currentPrice = $merchant
      ? (method_exists($merchant, 'showPrice') ? $merchant->showPrice() : \App\Models\Product::convertPrice($merchant->price))
      : (method_exists($productt, 'showPrice') ? $productt->showPrice($vendorId) : $productt->showPrice());

  $previousPrice = ($merchant && $merchant->previous_price)
      ? \App\Models\Product::convertPrice($merchant->previous_price)
      : null;

  $offPercent = null;
  if ($merchant && $merchant->previous_price > 0 && $merchant->price > 0) {
      $offPercent = round((1 - ($merchant->price / $merchant->previous_price)) * 100);
  } elseif (method_exists($productt, 'offPercentage')) {
      $offPercent = (int) round($productt->offPercentage());
  }

  // مقاسات/كميات البائع
  $sizes = [];
  $qtys  = [];
  if ($merchant && !empty($merchant->size)) {
      $sizes = is_string($merchant->size) ? array_map('trim', explode(',', $merchant->size)) : (array) $merchant->size;
  }
  if ($merchant && !empty($merchant->size_qty)) {
      $qtys = is_string($merchant->size_qty) ? array_map('trim', explode(',', $merchant->size_qty)) : (array) $merchant->size_qty;
  }

  // ألوان الهوية العامة (من المنتج)
  $colors = [];
  if (!empty($productt->color)) {
      $colors = is_string($productt->color) ? array_map('trim', explode(',', $productt->color)) : (array) $productt->color;
  }
@endphp

<div class="product-top">
  <div class="row g-4 align-items-start">
    <div class="col-md-6">
      <div class="product-media position-relative">
        @if(!is_null($offPercent) && $offPercent > 0)
          <span class="badge bg-success position-absolute" style="top:10px;left:10px;">-{{ $offPercent }}%</span>
        @endif
        <img class="img-fluid"
             src="{{ $productt->photo ? \Illuminate\Support\Facades\Storage::url($productt->photo) : asset('assets/images/noimage.png') }}"
             alt="{{ $productt->name }}">
      </div>
    </div>

    <div class="col-md-6">
      <h1 class="h4 mb-1"><x-product-name :product="$productt" :vendor-id="$vendorId" target="_self" /></h1>
      @if(!empty($productt->sku))
        <p class="text-muted mb-2"><strong>SKU:</strong>
          <a href="{{ route('search.result', $productt->sku) }}" class="text-primary" target="_blank">{{ $productt->sku }}</a>
        </p>
      @endif

      <div class="mb-3">
        <span class="h5 d-block" id="top-current-price">{{ $currentPrice }}</span>
        @if($previousPrice)
          <small class="text-muted"><del id="top-previous-price">{{ $previousPrice }}</del></small>
        @endif>
      </div>

      {{-- خيارات اللون/المقاس (إن لزم) --}}
      <form id="buy-form" class="mb-3">
        <input type="hidden" id="vendorId"   name="user"    value="{{ $vendorId }}">
        <input type="hidden" id="productId"  name="product" value="{{ $productt->id }}">
        <input type="hidden" id="product_price"
               value="{{ round((($merchant && method_exists($merchant,'vendorSizePrice')) ? $merchant->vendorSizePrice() : $productt->vendorPrice()) * (isset($curr)?$curr->value:1), 2) }}">

        {{-- اختر مقاس (إن وجد) --}}
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

        {{-- التوفّر من عرض البائع --}}
        @php $mpStock = $merchant ? (int) $merchant->stock : null; @endphp
        <p class="mb-2">
          <strong>@lang('Availability'):</strong>
          @if (is_null($mpStock) || $mpStock <= 0)
            <span class="badge bg-warning">@lang('Out of Stock')</span>
          @else
            <span class="badge bg-success">{{ $mpStock }}</span>
          @endif
        </p>

        <div class="d-flex align-items-center gap-2 my-3">
          <label class="form-label mb-0">{{ __('Quantity') }}</label>
          <div class="input-group" style="width:140px;">
            <button class="btn btn-outline-secondary" type="button" id="qtyDown">-</button>
            <input type="text" class="form-control text-center" id="qtyInput" name="qty"
                   value="{{ $productt->minimum_qty == null ? '1' : (int) $productt->minimum_qty }}" readonly>
            <button class="btn btn-outline-secondary" type="button" id="qtyUp">+</button>
          </div>
        </div>

        <div class="d-flex gap-2">
          {{-- زر إضافة للسلة (Ajax) --}}
          <a href="javascript:;" id="addToCartBtn"
             data-href="{{ $merchantProductId ? route('merchant.cart.add', $merchantProductId) : 'javascript:;' }}"
             data-cross-href="{{ route('front.show.cross.product', $productt->id) }}"
             data-user="{{ $vendorId }}" data-product="{{ $productt->id }}"
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
        @if($hasVendor && $merchant)
          <a class="text-decoration-none"
             href="{{ route('front.product', ['slug'=>$productt->slug, 'vendor_id'=>$vendorId, 'merchant_product_id'=>$merchant->id]) }}">
            {{ __('View Details') }}
          </a>
        @endif
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
    const pid   = document.getElementById('productId').value;
    const user  = document.getElementById('vendorId').value || 0;
    const size  = document.getElementById('sizeSelect')?.value || '';
    const color = document.getElementById('colorSelect')?.value || '';
    const qty   = document.getElementById('qtyInput').value || 1;

    // استخراج size_qty من الـ option المحدد إن وُجد
    const size_qty   = document.getElementById('sizeSelect')?.selectedOptions[0]?.getAttribute('data-size-qty') || '';
    const size_price = 0; // إن أردت حساب فرق سعر المقاس، احقنه هنا لاحقًا
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
