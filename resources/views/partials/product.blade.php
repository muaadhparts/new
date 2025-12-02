{{-- resources/views/partials/product.blade.php --}}

@php
    /**
     * اختيار البائع لعمليات السلة/العرض في المودال:
     * - أولًا من ?user= في الاستعلام
     * - أو من product->vendor_user_id (إذا حقنه الكنترولر)
     * - أو من product->user_id كـ fallback أخير
     */
    $vendorId = (int) (request()->get('user') ?? ($product->vendor_user_id ?? $product->user_id ?? 0));

    // صورة أساسية
    $mainPhoto = filter_var($product->photo ?? '', FILTER_VALIDATE_URL)
        ? $product->photo
        : (($product->photo ?? null) ? \Illuminate\Support\Facades\Storage::url($product->photo) : asset('assets/images/noimage.png'));

    // حماية في حال عدم تمرير $gs من الـ View الأب
    $gs = $gs ?? (isset($__data['gs']) ? $__data['gs'] : null);

    // MerchantProduct من الكنترولر
    $mp = $mp ?? null;
    $brand = $brand ?? null;

    // --- 👇 أهم نقطة: إجبار القراءة من السعر المحقون مع البائع، وتجنّب showPrice() حين تتوفر هوية البائع ---
    $forceVendor = request()->has('user') || isset($product->vendor_user_id);

    $rawPrice = $product->price ?? null;
    $rawPrev  = $product->previous_price ?? null;

    // لو لدينا بائع محدد (forceVendor) نستخدم السعر المحقون مباشرةً
    if ($forceVendor) {
        $priceHtml = $rawPrice !== null ? \App\Models\Product::convertPrice($rawPrice) : '-';
        $prevHtml  = $rawPrev  !== null ? \App\Models\Product::convertPrice($rawPrev)  : null;
    } else {
        // خلاف ذلك: اسمح باستخدام showPrice() كالمعتاد
        $priceHtml = method_exists($product, 'showPrice')
            ? $product->showPrice()
            : (\App\Models\Product::convertPrice($rawPrice ?? 0));
        $prevHtml  = (method_exists($product, 'showPreviousPrice') && $product->showPreviousPrice())
            ? $product->showPreviousPrice()
            : ($rawPrev !== null ? \App\Models\Product::convertPrice($rawPrev) : null);
    }

    // تقييمات (اختياري)
    $avg   = $product->ratings_avg_rating ?? null;
    $count = class_exists('App\\Models\\Rating') && method_exists('App\\Models\\Rating', 'ratingCount')
        ? \App\Models\Rating::ratingCount($product->id)
        : null;

    // اسم عربي/إنجليزي اختياري - using centralized helper
    $secondaryLabel = getLocalizedProductName($product);

    // --- معلومات المنتج للعرض ---
    // Quality Brand
    $qualityBrand = $mp?->qualityBrand;

    // Vendor
    $vendor = $mp?->user;

    // الحد الأدنى للكمية
    $minQty = $mp ? (int)($mp->minimum_qty ?? 1) : 1;
    if ($minQty < 1) $minQty = 1;

    // المخزون
    $stock = $mp ? (int)($mp->stock ?? 999) : (int)($product->stock ?? 999);

    // Preorder
    $preordered = $mp ? (int)($mp->preordered ?? 0) : 0;
@endphp

<div class="row gy-4 ill-product" data-product-id="{{ $product->id }}" data-user="{{ $vendorId }}">
    <div class="col-12 col-lg-6">
        {{-- Main image --}}
        @if($mainPhoto)
            <img src="{{ $mainPhoto }}" alt="{{ $product->name ?? $product->sku }}" class="img-fluid rounded w-100 mb-3">
        @endif

        {{-- Gallery --}}
        @if(!empty($product->galleries))
            <div class="d-flex flex-wrap gap-2">
                @foreach($product->galleries as $gallery)
                    @php
                        $url = filter_var($gallery->photo ?? '', FILTER_VALIDATE_URL)
                            ? $gallery->photo
                            : asset('assets/images/galleries/'.$gallery->photo);
                    @endphp
                    <img src="{{ $url }}" alt="{{ $product->name ?? '' }}" class="img-fluid rounded" style="width:75px;height:75px;object-fit:cover;">
                @endforeach
            </div>
        @endif
    </div>

    <div class="col-12 col-lg-6">
        {{-- عنوان المنتج --}}
        <h3 class="fw-bold mb-2">
            <x-product-name :product="$product" :vendor-id="$vendorId" target="_blank" />
        </h3>

        {{-- التقييم (اختياري) --}}
        @if(!empty($avg))
            <div class="mb-2">
                <span class="me-1 fw-semibold">{{ number_format($avg, 1) }}</span>
                @for($i = 1; $i <= 5; $i++)
                    <i class="fa{{ $i <= round($avg) ? 's' : 'r' }} fa-star text-warning"></i>
                @endfor
                @if($count !== null)
                    <span class="ms-1 text-muted">({{ $count }} @lang('Reviews'))</span>
                @endif
            </div>
        @endif

        {{-- السعر --}}
        <div class="mb-3">
            <span class="h4 text-primary">{!! $priceHtml !!}</span>
            @if($prevHtml)
                <del class="text-muted ms-2">{!! $prevHtml !!}</del>
            @endif
        </div>

        {{-- معلومات المنتج: SKU, Brand, Quality, Vendor --}}
        <x-product-info
            :product="$product"
            :mp="$mp"
            display-mode="modal"
            :show-sku="true"
            :show-brand="true"
            :show-quality-brand="true"
            :show-vendor="true"
            :show-stock="true"
        />

        {{-- الكمية مع احترام الحد الأدنى والمخزون --}}
        @if(($product->type ?? 'Physical') === 'Physical')
            <div class="d-flex align-items-center mb-3">
                <span class="me-2">@lang('Quantity'):</span>
                <button type="button" class="btn btn-outline-secondary btn-sm modal-qtminus"
                        data-min="{{ $minQty }}">−</button>
                <input type="number" name="quantity" value="{{ $minQty }}" min="{{ $minQty }}"
                       class="form-control mx-2 text-center ill-qty modal-qty-input"
                       data-min="{{ $minQty }}" data-stock="{{ $stock }}" data-preordered="{{ $preordered }}"
                       style="max-width:80px;" readonly>
                <button type="button" class="btn btn-outline-secondary btn-sm modal-qtplus"
                        data-stock="{{ $stock }}" data-preordered="{{ $preordered }}">+</button>
            </div>
            @if($minQty > 1)
                <small class="text-muted d-block mb-2">
                    <i class="fas fa-info-circle"></i> @lang('Minimum order quantity'): {{ $minQty }}
                </small>
            @endif
        @endif

        {{-- الأزرار --}}
        <div class="d-flex gap-2">
            {{-- إضافة للسلة (AJAX، يبقى داخل المودال) --}}
            @if($mp)
                <button
                    type="button"
                    class="btn btn-primary ill-add-to-cart"
                    data-id="{{ $product->id }}"
                    data-mp-id="{{ $mp->id }}"
                    data-user="{{ $vendorId }}"
                    data-addnum-url="{{ route('merchant.cart.add', $mp->id) }}"
                >@lang('Add To Cart')</button>

                {{-- شراء الآن: يضيف ثم يوجّه للسلة --}}
                <button
                    type="button"
                    class="btn btn-success ill-buy-now"
                    data-id="{{ $product->id }}"
                    data-mp-id="{{ $mp->id }}"
                    data-user="{{ $vendorId }}"
                    data-addtonum-url="{{ route('merchant.cart.add', $mp->id) }}"
                    data-carts-url="{{ url('/carts') }}"
                >@lang('buttons.purchase_now')</button>
            @else
                <button
                    type="button"
                    class="btn btn-primary ill-add-to-cart"
                    data-id="{{ $product->id }}"
                    data-user="{{ $vendorId }}"
                    data-addnum-url="{{ url('/addnumcart') }}"
                >@lang('Add To Cart')</button>

                <button
                    type="button"
                    class="btn btn-success ill-buy-now"
                    data-id="{{ $product->id }}"
                    data-user="{{ $vendorId }}"
                    data-addtonum-url="{{ url('/addtonumcart') }}"
                    data-carts-url="{{ url('/carts') }}"
                >@lang('buttons.purchase_now')</button>
            @endif
        </div>
    </div>
</div>

{{-- JavaScript للتحكم بالكمية في المودال --}}
<script>
(function() {
    // زيادة الكمية
    document.querySelectorAll('.modal-qtplus').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var input = this.parentNode.querySelector('.modal-qty-input');
            var stock = parseInt(this.dataset.stock) || 999;
            var preordered = parseInt(this.dataset.preordered) || 0;
            var current = parseInt(input.value) || 1;

            if (stock > 0 && current >= stock && preordered == 0) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('{{ __("Stock limit reached") }}: ' + stock);
                }
                return;
            }
            input.value = current + 1;
        });
    });

    // إنقاص الكمية
    document.querySelectorAll('.modal-qtminus').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var input = this.parentNode.querySelector('.modal-qty-input');
            var minQty = parseInt(this.dataset.min) || 1;
            var current = parseInt(input.value) || 1;

            if (current <= minQty) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('{{ __("Minimum quantity is") }} ' + minQty);
                }
                return;
            }
            input.value = current - 1;
        });
    });
})();
</script>
